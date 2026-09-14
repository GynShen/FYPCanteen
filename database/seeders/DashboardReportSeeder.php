<?php

namespace Database\Seeders;

use App\Models\MealCollection;
use App\Models\MealPreparation;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentMealEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardReportSeeder extends Seeder
{
    private const REPORT_START = '2026-07-01';

    private const REPORT_END = '2026-08-31';

    /**
     * Seed realistic July and August data for owner reports and preparation.
     */
    public function run(): void
    {
        $parents = User::query()
            ->where('role', 'customer')
            ->whereHas('children')
            ->with(['children.student.school'])
            ->orderBy('id')
            ->get();
        $operators = User::query()
            ->where('role', 'operator')
            ->whereNotNull('school_id')
            ->get()
            ->keyBy('school_id');
        $menus = MenuItem::query()
            ->where('is_available', true)
            ->with(['days:id,menu_item_id,day_of_week', 'schools:id'])
            ->get();

        if ($parents->isEmpty() || $operators->isEmpty() || $menus->isEmpty()) {
            throw new RuntimeException(
                'Run SchoolSeeder, OperatorSeeder, LinkedStudentsParentsSeeder and MenuSeeder before DashboardReportSeeder.',
            );
        }

        $menusBySchoolAndDay = $this->menusBySchoolAndDay($menus);

        DB::transaction(function () use ($parents, $operators, $menusBySchoolAndDay): void {
            $batches = [];
            $periodStart = Carbon::parse(self::REPORT_START, 'Asia/Kuala_Lumpur')->startOfDay();
            $periodEnd = Carbon::parse(self::REPORT_END, 'Asia/Kuala_Lumpur')->endOfDay();
            $firstWeek = $periodStart->copy()->startOfWeek();
            $lastWeek = $periodEnd->copy()->startOfWeek();

            foreach ($parents as $parentIndex => $parent) {
                $weekIndex = 0;

                for ($weekStart = $firstWeek->copy(); $weekStart->lte($lastWeek); $weekStart->addWeek()) {
                    $sessionId = sprintf(
                        'cs_test_owner_report_%d_%s',
                        $parent->id,
                        $weekStart->format('Ymd'),
                    );

                    if (Payment::query()->where('stripe_session_id', $sessionId)->exists()) {
                        $weekIndex++;

                        continue;
                    }

                    $specifications = $this->weeklySelections(
                        $parent->children,
                        $weekStart,
                        $weekIndex,
                        $parentIndex,
                        $periodStart,
                        $periodEnd,
                        $menusBySchoolAndDay,
                    );

                    if ($specifications->isEmpty()) {
                        $weekIndex++;

                        continue;
                    }

                    $paymentDate = $specifications->min('scheduled_at')->copy()
                        ->subDays(3 + $this->score($sessionId.'-payment') % 5)
                        ->setTime(19, 10 + $this->score($sessionId.'-minute') % 40);
                    $total = $specifications->sum(
                        fn (array $item) => (float) $item['menu']->price * $item['quantity'],
                    );
                    $order = Order::query()->create([
                        'user_id' => $parent->id,
                        'total_amount' => $total,
                        'order_status' => 'paid',
                    ]);
                    $this->setTimestamps($order, $paymentDate);

                    $payment = Payment::query()->create([
                        'order_id' => $order->id,
                        'user_id' => $parent->id,
                        'amount' => $total,
                        'payment_date' => $paymentDate,
                        'method' => 'stripe',
                        'status' => 'paid',
                        'stripe_session_id' => $sessionId,
                        'stripe_payment_intent_id' => 'pi_test_owner_report_'.$order->id,
                    ]);
                    $this->setTimestamps($payment, $paymentDate);

                    foreach ($specifications as $specification) {
                        $menu = $specification['menu'];
                        $student = $specification['student'];
                        $scheduledAt = $specification['scheduled_at'];
                        $operator = $operators->get($student->school_id);

                        if (! $operator) {
                            throw new RuntimeException('Every seeded school requires an operator.');
                        }

                        $orderItem = $order->orderItems()->create([
                            'student_id' => $student->id,
                            'menu_item_id' => $menu->id,
                            'unit_price' => $menu->price,
                            'scheduled_time' => $scheduledAt,
                            'quantity' => $specification['quantity'],
                        ]);
                        $this->setTimestamps($orderItem, $paymentDate);

                        $this->seedCollection($orderItem, $menu, $student, $operator, $scheduledAt);

                        $batchKey = $student->school_id.'-'.$menu->id.'-'.$scheduledAt->toDateString();
                        $batches[$batchKey] = [
                            'school_id' => $student->school_id,
                            'menu_item_id' => $menu->id,
                            'date' => $scheduledAt->toDateString(),
                            'operator_id' => $operator->id,
                            'start_time' => $menu->available_start_time,
                        ];
                    }

                    $weekIndex++;
                }
            }

            foreach ($batches as $key => $batch) {
                $completed = $this->score('preparation-'.$key) < 90;
                $completedAt = null;

                if ($completed) {
                    $hour = str_starts_with((string) $batch['start_time'], '14:') ? 13 : 8;
                    $completedAt = Carbon::parse($batch['date'], 'Asia/Kuala_Lumpur')
                        ->setTime($hour, 5 + $this->score($key.'-minute') % 35);
                }

                MealPreparation::query()->updateOrCreate(
                    [
                        'school_id' => $batch['school_id'],
                        'menu_item_id' => $batch['menu_item_id'],
                        'preparation_date' => $batch['date'],
                    ],
                    [
                        'completed_at' => $completedAt,
                        'completed_by' => $completed ? $batch['operator_id'] : null,
                    ],
                );
            }
        });
    }

    /**
     * @return array<int, array<string, Collection<int, MenuItem>>>
     */
    private function menusBySchoolAndDay(Collection $menus): array
    {
        $grouped = [];

        foreach ($menus as $menu) {
            foreach ($menu->schools as $school) {
                foreach ($menu->days as $day) {
                    $grouped[$school->id][$day->day_of_week] ??= collect();
                    $grouped[$school->id][$day->day_of_week]->push($menu);
                }
            }
        }

        return $grouped;
    }

    /**
     * @return Collection<int, array{student: mixed, menu: MenuItem, scheduled_at: Carbon, quantity: int}>
     */
    private function weeklySelections(
        Collection $children,
        Carbon $weekStart,
        int $weekIndex,
        int $parentIndex,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $menusBySchoolAndDay,
    ): Collection {
        $selections = collect();

        foreach ($children->values() as $childIndex => $child) {
            $student = $child->student;
            $firstDayOffset = ($parentIndex + $childIndex + $weekIndex) % 5;
            $dayOffsets = [$firstDayOffset, ($firstDayOffset + 2) % 5];

            foreach ($dayOffsets as $selectionIndex => $dayOffset) {
                $date = $weekStart->copy()->addDays($dayOffset);

                if (! $date->betweenIncluded($periodStart, $periodEnd)) {
                    continue;
                }

                $day = strtolower($date->format('l'));
                $candidates = $menusBySchoolAndDay[$student->school_id][$day] ?? collect();

                if ($candidates->isEmpty()) {
                    continue;
                }

                $candidateIndex = ($parentIndex + $childIndex + $weekIndex + $selectionIndex) % $candidates->count();
                $menu = $candidates->values()->get($candidateIndex);
                $scheduledAt = $date->copy()->setTimeFromTimeString($menu->available_start_time);
                $quantity = $this->score($student->qr_code.'-'.$date->toDateString()) < 7 ? 2 : 1;

                $selections->push([
                    'student' => $student,
                    'menu' => $menu,
                    'scheduled_at' => $scheduledAt,
                    'quantity' => $quantity,
                ]);
            }
        }

        return $selections;
    }

    private function seedCollection(OrderItem $orderItem, MenuItem $menu, Student $student, User $operator, Carbon $scheduledAt): void
    {
        $score = $this->score('collection-'.$student->qr_code.'-'.$scheduledAt->toDateString().'-'.$menu->id);

        // Scores 94-99 represent meals that were not collected.
        if ($score >= 94) {
            return;
        }

        $start = $scheduledAt->copy()->setTimeFromTimeString($menu->available_start_time);
        $end = $scheduledAt->copy()->setTimeFromTimeString($menu->available_end_time);
        $isLate = $score >= 84;
        $collectedAt = $isLate
            ? $end->copy()->addMinutes(2 + $score % 12)
            : $start->copy()->addMinutes(5 + $score % 46);
        $collection = MealCollection::query()->create([
            'order_item_id' => $orderItem->id,
            'collected_quantity' => $orderItem->quantity,
            'status' => 'collected',
            'is_late_collection' => $isLate,
            'collected_at' => $collectedAt,
            'collected_by' => $operator->id,
        ]);
        $this->setTimestamps($collection, $collectedAt);

        if ($score % 11 === 0) {
            $event = StudentMealEvent::query()->create([
                'student_id' => $student->id,
                'order_item_id' => $orderItem->id,
                'meal_collection_id' => $collection->id,
                'event_type' => 'still_hungry',
                'reported_by' => $operator->id,
                'reported_at' => $collectedAt->copy()->addMinutes(2),
            ]);
            $this->setTimestamps($event, $collectedAt->copy()->addMinutes(2));
        }
    }

    private function score(string $key): int
    {
        return hexdec(substr(hash('sha256', $key), 0, 8)) % 100;
    }

    private function setTimestamps(Model $model, Carbon $timestamp): void
    {
        $model->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveQuietly();
    }
}
