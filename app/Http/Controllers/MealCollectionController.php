<?php

namespace App\Http\Controllers;

use App\Models\MealCollection;
use App\Models\OrderItem;
use App\Models\Student;
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

        return inertia('Owner/ScannerTest');
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorizeManager($request);
        $validated = $request->validate(['qr_code' => ['required', 'string', 'max:255']]);

        $student = Student::query()
            ->where('qr_code', trim($validated['qr_code']))
            ->with('school:id,name,is_active')
            ->first();

        if (! $student) {
            throw ValidationException::withMessages(['qr_code' => 'This student QR code is invalid.']);
        }

        $this->authorizeStudentAccess($request, $student);

        if ($student->approval_status !== 'approved') {
            throw ValidationException::withMessages(['qr_code' => 'This student has not been approved.']);
        }

        $now = Carbon::now('Asia/Kuala_Lumpur');
        $items = OrderItem::query()
            ->where('student_id', $student->id)
            ->whereDate('scheduled_time', $now->toDateString())
            ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
            ->with(['menuItem:id,name,image_url,available_start_time,available_end_time', 'collection'])
            ->orderBy('scheduled_time')
            ->get()
            ->map(fn (OrderItem $item) => $this->serializeItem($item, $now));

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'name' => $student->name,
                'class' => $student->class,
                'school' => $student->school?->name,
            ],
            'date' => $now->format('d/m/Y'),
            'items' => $items,
        ]);
    }

    public function collect(Request $request, OrderItem $orderItem): JsonResponse
    {
        $this->authorizeManager($request);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $now = Carbon::now('Asia/Kuala_Lumpur');
        $orderItem->load(['student', 'menuItem', 'order.payment', 'collection']);
        $this->authorizeStudentAccess($request, $orderItem->student);

        abort_unless($orderItem->order?->payment?->status === 'paid', 422, 'Only paid meals can be collected.');
        abort_unless($orderItem->scheduled_time->toDateString() === $now->toDateString(), 422, 'This meal is not scheduled for today.');

        $availability = $this->availability($orderItem, $now);
        abort_unless($availability === 'open', 422, $availability === 'upcoming'
            ? 'This meal collection time has not started.'
            : 'This meal collection time has ended.');

        $collection = DB::transaction(function () use ($request, $orderItem, $validated, $now): MealCollection {
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
                'collected_at' => $now,
                'collected_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ])->save();

            return $collection;
        });

        $orderItem->setRelation('collection', $collection);

        return response()->json([
            'message' => 'Meal collection recorded successfully.',
            'item' => $this->serializeItem($orderItem, $now),
        ]);
    }

    private function serializeItem(OrderItem $item, Carbon $now): array
    {
        $collected = (int) ($item->collection?->collected_quantity ?? 0);
        $availability = $this->availability($item, $now);
        $status = $collected >= (int) $item->quantity
            ? 'collected'
            : ($availability === 'missed' ? 'missed' : ($collected > 0 ? 'partially_collected' : $availability));

        return [
            'id' => $item->id,
            'meal' => $item->menuItem?->name,
            'image_url' => $item->menuItem?->image_url,
            'size' => $item->size,
            'ordered_quantity' => (int) $item->quantity,
            'collected_quantity' => $collected,
            'remaining_quantity' => max(0, (int) $item->quantity - $collected),
            'start_time' => $this->formatTime($item->menuItem?->available_start_time),
            'end_time' => $this->formatTime($item->menuItem?->available_end_time),
            'status' => $status,
            'can_collect' => $status === 'open' || $status === 'partially_collected',
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

        return $now->lt($start) ? 'upcoming' : ($now->gt($end) ? 'missed' : 'open');
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? Carbon::parse($time)->format('g:i A') : null;
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['owner', 'operator'], true), 403);
        abort_if($request->user()->hasRole('operator') && ! $request->user()->school_id, 403);
    }

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        if ($request->user()->hasRole('operator')) {
            abort_unless((int) $request->user()->school_id === (int) $student->school_id, 403);
        }
    }
}
