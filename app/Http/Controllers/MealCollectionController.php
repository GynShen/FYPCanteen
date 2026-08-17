<?php

namespace App\Http\Controllers;

use App\Models\MealCollection;
use App\Models\OrderItem;
use App\Models\Student;
use App\Models\StudentMealEvent;
use App\Notifications\CustomerEventNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MealCollectionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManager($request);

        return inertia('Owner/MealCollection');
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorizeManager($request);
        $validated = $request->validate(['qr_code' => ['required', 'string', 'max:255']]);

        $student = Student::query()
            ->where('qr_code', trim($validated['qr_code']))
            ->with(['school:id,name,is_active', 'child.user'])
            ->first();

        if (! $student) {
            throw ValidationException::withMessages(['qr_code' => 'This student QR code is invalid.']);
        }

        $this->authorizeStudentAccess($request, $student);

        $now = Carbon::now('Asia/Kuala_Lumpur');
        $items = OrderItem::query()
            ->where('student_id', $student->id)
            ->whereDate('scheduled_time', $now->toDateString())
            ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
            ->with(['menuItem:id,name,image_url,available_start_time,available_end_time', 'collection.collector:id,name', 'studentMealEvents:id,order_item_id,event_type'])
            ->orderBy('scheduled_time')
            ->get();

        $collectedNow = [];
        DB::transaction(function () use ($items, $request, $now, &$collectedNow): void {
            foreach ($items as $item) {
                $availability = $this->availability($item, $now);
                $alreadyCollected = (int) ($item->collection?->collected_quantity ?? 0);

                if (! in_array($availability, ['open', 'grace_period'], true) || $alreadyCollected >= (int) $item->quantity) {
                    continue;
                }

                $lockedItem = OrderItem::query()->lockForUpdate()->findOrFail($item->id);
                $collection = MealCollection::query()->lockForUpdate()->firstOrNew(['order_item_id' => $lockedItem->id]);
                $previousQuantity = (int) ($collection->collected_quantity ?? 0);

                if ($previousQuantity >= (int) $lockedItem->quantity) {
                    continue;
                }

                $collection->fill([
                    'collected_quantity' => (int) $lockedItem->quantity,
                    'status' => 'collected',
                    'is_late_collection' => $collection->is_late_collection || $availability === 'grace_period',
                    'late_reason' => $collection->late_reason,
                    'collected_at' => $now,
                    'collected_by' => $request->user()->id,
                ])->save();

                $collectedNow[$item->id] = [
                    'quantity' => (int) $lockedItem->quantity - $previousQuantity,
                    'collection_id' => $collection->id,
                ];
            }
        });

        $items->each(function (OrderItem $item) use ($student, $collectedNow): void {
            $item->load('collection.collector:id,name');
            if (! isset($collectedNow[$item->id])) {
                return;
            }

            $student->child?->user?->notify(new CustomerEventNotification([
                'event' => 'meal_received',
                'title' => 'Meal received',
                'message' => $student->name.' collected '.$collectedNow[$item->id]['quantity'].' × '.$item->menuItem->name.'.',
                'student_id' => $student->id,
                'order_item_id' => $item->id,
                'meal_collection_id' => $collectedNow[$item->id]['collection_id'],
            ]));
        });

        $serializedItems = $items->map(fn (OrderItem $item) => $this->serializeItem($item, $now));
        $collectedQuantity = collect($collectedNow)->sum('quantity');

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'class' => $student->class,
                'school' => $student->school?->name,
            ],
            'date' => $now->format('d/m/Y'),
            'items' => $serializedItems,
            'collected_quantity' => $collectedQuantity,
            'message' => $collectedQuantity > 0
                ? $collectedQuantity.' meal(s) automatically marked as collected.'
                : 'No currently collectible meals were changed.',
        ]);
    }

    public function collect(Request $request, OrderItem $orderItem): JsonResponse
    {
        $this->authorizeManager($request);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $now = Carbon::now('Asia/Kuala_Lumpur');
        $orderItem->load(['student.child.user', 'menuItem', 'order.payment', 'collection', 'studentMealEvents:id,order_item_id,event_type']);
        $this->authorizeStudentAccess($request, $orderItem->student);

        abort_unless($orderItem->order?->payment?->status === 'paid', 422, 'Only paid meals can be collected.');
        abort_unless($orderItem->scheduled_time->toDateString() === $now->toDateString(), 422, 'This meal is not scheduled for today.');

        $availability = $this->availability($orderItem, $now);
        abort_unless(in_array($availability, ['open', 'grace_period'], true), 422, $availability === 'upcoming'
            ? 'This meal collection time has not started.'
            : 'This meal collection time and its 15-minute grace period have ended.');

        $collection = DB::transaction(function () use ($request, $orderItem, $validated, $now, $availability): MealCollection {
            $lockedItem = OrderItem::query()->lockForUpdate()->findOrFail($orderItem->id);
            $collection = MealCollection::query()->lockForUpdate()->firstOrNew([
                'order_item_id' => $lockedItem->id,
            ]);
            $alreadyCollected = (int) ($collection->collected_quantity ?? 0);
            $newQuantity = $alreadyCollected + (int) $validated['quantity'];

            if ($newQuantity > $lockedItem->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Only '.($lockedItem->quantity - $alreadyCollected).' meal(s) remain to be collected.',
                ]);
            }

            $collection->fill([
                'collected_quantity' => $newQuantity,
                'status' => $newQuantity === (int) $lockedItem->quantity ? 'collected' : 'partially_collected',
                'is_late_collection' => $collection->is_late_collection || $availability === 'grace_period',
                'late_reason' => $collection->late_reason,
                'collected_at' => $now,
                'collected_by' => $request->user()->id,
            ])->save();

            return $collection;
        });

        $orderItem->setRelation('collection', $collection);
        $parent = $orderItem->student->child?->user;
        $parent?->notify(new CustomerEventNotification([
            'event' => 'meal_received',
            'title' => 'Meal received',
            'message' => $orderItem->student->name.' collected '.$validated['quantity'].' × '.$orderItem->menuItem->name.'.',
            'student_id' => $orderItem->student_id,
            'order_item_id' => $orderItem->id,
            'meal_collection_id' => $collection->id,
        ]));

        return response()->json([
            'message' => 'Meal collection recorded successfully.',
            'item' => $this->serializeItem($orderItem, $now),
        ]);
    }

    public function stillHungry(Request $request, OrderItem $orderItem): JsonResponse
    {
        $this->authorizeManager($request);
        $orderItem->load(['student.child.user', 'menuItem', 'order.payment', 'collection']);
        $this->authorizeStudentAccess($request, $orderItem->student);

        abort_unless($orderItem->order?->payment?->status === 'paid', 422, 'This meal has not been paid.');
        abort_unless($orderItem->collection && $orderItem->collection->collected_quantity >= $orderItem->quantity, 422, 'Still-hungry feedback is available only after the full meal is collected.');

        $event = StudentMealEvent::firstOrCreate([
            'order_item_id' => $orderItem->id,
            'event_type' => 'still_hungry',
        ], [
            'student_id' => $orderItem->student_id,
            'meal_collection_id' => $orderItem->collection->id,
            'reported_by' => $request->user()->id,
            'reported_at' => Carbon::now('Asia/Kuala_Lumpur'),
        ]);

        if (! $event->wasRecentlyCreated) {
            throw ValidationException::withMessages(['feedback' => 'Still-hungry feedback was already sent for this meal.']);
        }

        $orderItem->student->child?->user?->notify(new CustomerEventNotification([
            'event' => 'still_hungry',
            'title' => 'Still hungry feedback',
            'message' => $orderItem->student->name.' reported still being hungry after '.$orderItem->menuItem->name.' (quantity '.$orderItem->quantity.'). Consider ordering an additional serving next time.',
            'student_id' => $orderItem->student_id,
            'order_item_id' => $orderItem->id,
            'meal_collection_id' => $orderItem->collection->id,
        ]));

        return response()->json(['message' => 'The parent has been notified.', 'feedback_sent' => true]);
    }

    private function serializeItem(OrderItem $item, Carbon $now): array
    {
        $collected = (int) ($item->collection?->collected_quantity ?? 0);
        $availability = $this->availability($item, $now);
        $status = $collected >= (int) $item->quantity
            ? ($item->collection?->is_late_collection ? 'late_collected' : 'collected')
            : ($availability === 'missed'
                ? 'missed'
                : ($collected > 0
                    ? ($item->collection?->is_late_collection ? 'late_partially_collected' : 'partially_collected')
                    : $availability));

        return [
            'id' => $item->id,
            'meal' => $item->menuItem?->name,
            'image_url' => $item->menuItem?->image_url,
            'ordered_quantity' => (int) $item->quantity,
            'collected_quantity' => $collected,
            'remaining_quantity' => max(0, (int) $item->quantity - $collected),
            'start_time' => $this->formatTime($item->menuItem?->available_start_time),
            'end_time' => $this->formatTime($item->menuItem?->available_end_time),
            'status' => $status,
            'can_collect' => in_array($availability, ['open', 'grace_period'], true) && $collected < (int) $item->quantity,
            'requires_late_reason' => false,
            'late_reason' => $item->collection?->late_reason,
            'collected_at' => $item->collection?->collected_at?->timezone('Asia/Kuala_Lumpur')->format('d/m/Y, g:i A'),
            'collected_by' => $item->collection?->collector?->name,
            'feedback_sent' => $item->studentMealEvents?->contains('event_type', 'still_hungry') ?? false,
        ];
    }

    private function availability(OrderItem $item, Carbon $now): string
    {
        $date = $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->toDateString();
        $startTime = $item->menuItem?->available_start_time;
        $endTime = $item->menuItem?->available_end_time;

        if (! $startTime || ! $endTime) {
            return 'open';
        }

        $start = Carbon::parse($date.' '.$startTime, 'Asia/Kuala_Lumpur');
        $end = Carbon::parse($date.' '.$endTime, 'Asia/Kuala_Lumpur');

        if ($now->lt($start)) {
            return 'upcoming';
        }

        if ($now->lte($end)) {
            return 'open';
        }

        return $now->lte($end->copy()->addMinutes(15)) ? 'grace_period' : 'missed';
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? Carbon::parse($time)->format('g:i A') : null;
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->role === 'operator' && $request->user()->school_id, 403);
    }

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        abort_unless((int) $request->user()->school_id === (int) $student->school_id, 403);
    }
}
