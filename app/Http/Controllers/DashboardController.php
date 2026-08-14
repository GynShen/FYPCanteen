<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\MealPreparation;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function togglePreparation(Request $request)
    {
        abort_unless($request->user()->hasRole('operator'), 403);
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'preparation_date' => ['required', 'date'],
        ]);
        $preparation = MealPreparation::query()->firstOrNew([
            'school_id' => $request->user()->school_id,
            'menu_item_id' => $validated['menu_item_id'],
            'preparation_date' => $validated['preparation_date'],
        ]);
        $preparation->fill($preparation->completed_at
            ? ['completed_at' => null, 'completed_by' => null]
            : ['completed_at' => now(), 'completed_by' => $request->user()->id]);
        $preparation->save();

        return back();
    }

    public function index(Request $request)
    {
        if ($request->user()->hasRole('operator')) {
            return $this->operatorDashboard($request);
        }

        if ($request->user()->hasRole('customer')) {
            return $this->customerDashboard($request);
        }

        if (! $request->user()->hasRole('owner')) {
            return inertia('Dashboard', ['report' => null]);
        }

        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'week', 'month', 'custom'])],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $period = $validated['period'] ?? 'month';
        [$start, $end] = $this->dateRange($period, $validated);
        $schoolId = isset($validated['school_id']) ? (int) $validated['school_id'] : null;

        $paidItems = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('payments as p', 'p.order_id', '=', 'o.id')
            ->join('students as s', 's.id', '=', 'oi.student_id')
            ->where('p.status', 'paid')
            ->whereBetween('oi.scheduled_time', [$start, $end])
            ->when($schoolId, fn ($query) => $query->where('s.school_id', $schoolId));

        $summary = (clone $paidItems)->selectRaw('COALESCE(SUM(oi.unit_price * oi.quantity), 0) as revenue, COALESCE(SUM(oi.quantity), 0) as ordered')->first();
        [$collected, $missed] = $this->collectionTotals($start, $end, $schoolId);
        $collectionBase = $collected + $missed;

        $revenueTrend = (clone $paidItems)
            ->selectRaw('DATE(oi.scheduled_time) as report_date, SUM(oi.unit_price * oi.quantity) as revenue')
            ->groupByRaw('DATE(oi.scheduled_time)')
            ->orderBy('report_date')
            ->get()
            ->map(fn ($row) => ['date' => $row->report_date, 'revenue' => round((float) $row->revenue, 2)]);

        $mealOccurrences = (clone $paidItems)
            ->join('menu_items as mi', 'mi.id', '=', 'oi.menu_item_id')
            ->selectRaw('mi.id, mi.name, SUM(oi.quantity) as quantity, SUM(oi.unit_price * oi.quantity) as revenue')
            ->groupBy('mi.id', 'mi.name')
            ->get();

        $stillHungry = DB::table('student_meal_events as sme')
            ->join('order_items as oi', 'oi.id', '=', 'sme.order_item_id')
            ->join('students as s', 's.id', '=', 'sme.student_id')
            ->join('menu_items as mi', 'mi.id', '=', 'oi.menu_item_id')
            ->where('sme.event_type', 'still_hungry')
            ->whereBetween('sme.reported_at', [$start, $end])
            ->when($schoolId, fn ($query) => $query->where('s.school_id', $schoolId))
            ->selectRaw('mi.id, mi.name, COUNT(*) as reports')
            ->groupBy('mi.id', 'mi.name')
            ->orderByDesc('reports')
            ->limit(10)
            ->get();

        $today = Carbon::now('Asia/Kuala_Lumpur');
        $todayBatches = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('payments as p', 'p.order_id', '=', 'o.id')
            ->join('students as s', 's.id', '=', 'oi.student_id')
            ->where('p.status', 'paid')
            ->whereBetween('oi.scheduled_time', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->when($schoolId, fn ($query) => $query->where('s.school_id', $schoolId))
            ->selectRaw('s.school_id, oi.menu_item_id, SUM(oi.quantity) as portions')
            ->groupBy('s.school_id', 'oi.menu_item_id')
            ->get();
        $preparedBatchKeys = MealPreparation::query()
            ->whereDate('preparation_date', $today->toDateString())
            ->whereNotNull('completed_at')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->get(['school_id', 'menu_item_id'])
            ->mapWithKeys(fn ($preparation) => [$preparation->school_id.'-'.$preparation->menu_item_id => true]);
        $preparationSchools = School::query()
            ->when($schoolId, fn ($query) => $query->whereKey($schoolId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (School $school) use ($todayBatches, $preparedBatchKeys): array {
                $batches = $todayBatches->where('school_id', $school->id);
                $prepared = $batches->filter(fn ($batch) => $preparedBatchKeys->has($school->id.'-'.$batch->menu_item_id))->count();
                $totalBatches = $batches->count();

                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'portions' => (int) $batches->sum('portions'),
                    'batches' => $totalBatches,
                    'prepared' => $prepared,
                    'remaining' => $totalBatches - $prepared,
                    'status' => $totalBatches === 0 ? 'no_meals' : ($prepared === $totalBatches ? 'completed' : ($prepared > 0 ? 'in_progress' : 'not_started')),
                ];
            });

        $nextWeekStart = $today->copy()->addWeek()->startOfWeek()->startOfDay();
        $nextWeekEnd = $nextWeekStart->copy()->endOfWeek()->endOfDay();
        $nextWeekPreparation = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('payments as p', 'p.order_id', '=', 'o.id')
            ->join('students as s', 's.id', '=', 'oi.student_id')
            ->join('schools as sc', 'sc.id', '=', 's.school_id')
            ->join('menu_items as mi', 'mi.id', '=', 'oi.menu_item_id')
            ->where('p.status', 'paid')
            ->whereBetween('oi.scheduled_time', [$nextWeekStart, $nextWeekEnd])
            ->when($schoolId, fn ($query) => $query->where('s.school_id', $schoolId))
            ->get([
                's.school_id', 'sc.name as school_name', 'oi.menu_item_id', 'mi.name as meal_name',
                'mi.available_start_time', 'mi.available_end_time', 'oi.scheduled_time', 'oi.size', 'oi.quantity',
            ])
            ->groupBy(function ($item): string {
                $date = Carbon::parse($item->scheduled_time, 'Asia/Kuala_Lumpur')->toDateString();

                return $item->school_id.'-'.$date.'-'.$item->menu_item_id;
            })
            ->map(function ($items): array {
                $first = $items->first();
                $date = Carbon::parse($first->scheduled_time, 'Asia/Kuala_Lumpur');
                $formatTime = fn ($time) => $time ? Carbon::parse($time)->format('g:i A') : '-';

                return [
                    'key' => $first->school_id.'-'.$date->toDateString().'-'.$first->menu_item_id,
                    'school' => $first->school_name,
                    'date' => $date->toDateString(),
                    'day' => $date->format('l'),
                    'time' => $formatTime($first->available_start_time).' – '.$formatTime($first->available_end_time),
                    'meal' => $first->meal_name,
                    'S' => (int) $items->where('size', 'S')->sum('quantity'),
                    'M' => (int) $items->where('size', 'M')->sum('quantity'),
                    'L' => (int) $items->where('size', 'L')->sum('quantity'),
                    'total' => (int) $items->sum('quantity'),
                ];
            })
            ->sortBy(fn ($item) => $item['date'].'-'.$item['school'].'-'.$item['time'].'-'.$item['meal'])
            ->values();

        return inertia('Dashboard', [
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'period' => $period,
                'school_id' => $schoolId,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'report' => [
                'type' => 'owner',
                'revenue' => round((float) $summary->revenue, 2),
                'ordered' => (int) $summary->ordered,
                'collected' => $collected,
                'missed' => $missed,
                'collection_rate' => $collectionBase > 0 ? round($collected / $collectionBase * 100, 1) : 0,
                'revenue_trend' => $revenueTrend,
                'meal_rankings' => $mealOccurrences->sortByDesc('quantity')->values(),
                'still_hungry' => $stillHungry,
                'preparation_status' => $preparationSchools,
                'preparation_date' => $today->toDateString(),
                'next_week_preparation' => $nextWeekPreparation,
                'next_week_start' => $nextWeekStart->toDateString(),
                'next_week_end' => $nextWeekEnd->toDateString(),
            ],
        ]);
    }

    private function customerDashboard(Request $request)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'child_id' => ['nullable', 'integer'],
        ]);
        $userId = (int) $request->user()->id;
        $linkedChildren = DB::table('children as c')
            ->join('students as s', 's.id', '=', 'c.student_id')
            ->where('c.user_id', $userId)
            ->orderBy('s.name')
            ->get(['s.id', 's.name']);
        $selectedChildId = isset($validated['child_id']) && $linkedChildren->contains('id', (int) $validated['child_id'])
            ? (int) $validated['child_id']
            : null;
        $base = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('payments as p', 'p.order_id', '=', 'o.id')
            ->join('students as s', 's.id', '=', 'oi.student_id')
            ->join('children as c', 'c.student_id', '=', 's.id')
            ->where('c.user_id', $userId)
            ->where('p.status', 'paid');

        $availableMonths = (clone $base)
            ->selectRaw("DATE_FORMAT(oi.scheduled_time, '%Y-%m') as value")
            ->distinct()->orderByDesc('value')->pluck('value');
        $currentMonth = Carbon::now('Asia/Kuala_Lumpur')->format('Y-m');
        $month = $validated['month'] ?? ($availableMonths->contains($currentMonth) ? $currentMonth : ($availableMonths->first() ?? $currentMonth));
        $monthStart = Carbon::createFromFormat('Y-m', $month, 'Asia/Kuala_Lumpur')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $items = OrderItem::query()
            ->whereBetween('scheduled_time', [$monthStart, $monthEnd])
            ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
            ->whereHas('student.child', fn ($query) => $query->where('user_id', $userId))
            ->with(['student:id,name,student_code,school_id', 'student.school:id,name', 'menuItem:id,name,available_end_time', 'collection', 'studentMealEvents'])
            ->orderBy('scheduled_time')->get();
        $now = Carbon::now('Asia/Kuala_Lumpur');

        $mapItem = function (OrderItem $item) use ($now): array {
            $quantity = (int) $item->quantity;
            $collected = min($quantity, (int) ($item->collection?->collected_quantity ?? 0));
            $date = $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->toDateString();
            $deadline = Carbon::parse($date.' '.($item->menuItem?->available_end_time ?: '23:59:59'), 'Asia/Kuala_Lumpur')->addMinutes(15);
            $missed = $now->gt($deadline) ? max(0, $quantity - $collected) : 0;

            return [
                'id' => $item->id, 'student_id' => $item->student_id,
                'student_name' => $item->student?->name, 'school_name' => $item->student?->school?->name,
                'meal_id' => $item->menu_item_id, 'meal_name' => $item->menuItem?->name,
                'date' => $date, 'time' => $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->format('g:i A'),
                'scheduled_at' => $item->scheduled_time->toIso8601String(), 'quantity' => $quantity,
                'size' => strtoupper($item->size ?: 'M'), 'spent' => round((float) $item->unit_price * $quantity, 2),
                'collected' => $collected, 'missed' => $missed,
                'upcoming' => $now->lte($deadline) ? max(0, $quantity - $collected) : 0,
                'still_hungry' => $item->studentMealEvents->contains('event_type', 'still_hungry'),
            ];
        };
        $rows = $items->map($mapItem);
        $upcomingMeals = OrderItem::query()
            ->where('scheduled_time', '>=', $now)
            ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
            ->whereHas('student.child', fn ($query) => $query->where('user_id', $userId))
            ->with(['student:id,name,student_code,school_id', 'student.school:id,name', 'menuItem:id,name,available_end_time', 'collection', 'studentMealEvents'])
            ->orderBy('scheduled_time')
            ->get()
            ->map($mapItem)
            ->filter(fn ($row) => $row['upcoming'] > 0)
            ->groupBy('student_id')
            ->map(fn ($childRows) => $childRows->first())
            ->values();

        $children = $rows->groupBy('student_id')->map(function ($childRows): array {
            $first = $childRows->first();
            return [
                'id' => $first['student_id'], 'name' => $first['student_name'], 'school' => $first['school_name'],
                'spent' => round($childRows->sum('spent'), 2), 'ordered' => $childRows->sum('quantity'),
                'collected' => $childRows->sum('collected'), 'missed' => $childRows->sum('missed'),
                'upcoming' => $childRows->sum('upcoming'),
                'still_hungry' => $childRows->where('still_hungry', true)->count(),
            ];
        })->values();
        $calendarRows = $selectedChildId ? $rows->where('student_id', $selectedChildId) : $rows;
        $mostOrdered = $calendarRows->groupBy('meal_id')->map(function ($mealRows): array {
            return [
                'id' => $mealRows->first()['meal_id'],
                'name' => $mealRows->first()['meal_name'],
                'quantity' => $mealRows->sum('quantity'),
                'order_days' => $mealRows->pluck('date')->unique()->count(),
            ];
        })->sortByDesc('quantity')->take(5)->values();
        $calendarDays = $calendarRows->groupBy('date')->map(function ($dayRows, $date): array {
            return [
                'date' => $date,
                'meals' => $dayRows->groupBy('meal_id')->map(function ($mealRows): array {
                    return [
                        'id' => $mealRows->first()['meal_id'],
                        'name' => $mealRows->first()['meal_name'],
                        'quantity' => $mealRows->sum('quantity'),
                        'sizes' => $mealRows->groupBy('size')->map(fn ($sizeRows, $size) => [
                            'size' => $size,
                            'quantity' => $sizeRows->sum('quantity'),
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values();
        return inertia('Dashboard', [
            'filters' => ['month' => $month, 'child_id' => $selectedChildId],
            'report' => [
                'type' => 'customer',
                'linked_children' => $linkedChildren,
                'available_months' => $availableMonths->map(fn ($value) => ['value' => $value, 'label' => Carbon::createFromFormat('Y-m', $value)->format('F Y')]),
                'month_label' => $monthStart->format('F Y'), 'spent' => round($rows->sum('spent'), 2),
                'ordered' => $rows->sum('quantity'), 'collected' => $rows->sum('collected'),
                'missed' => $rows->sum('missed'), 'upcoming_count' => $rows->sum('upcoming'),
                'children' => $children, 'most_ordered' => $mostOrdered, 'calendar_days' => $calendarDays,
                'still_hungry' => $rows->where('still_hungry', true)->values(),
                'upcoming_meals' => $upcomingMeals,
            ],
        ]);
    }

    private function operatorDashboard(Request $request)
    {
        $validated = $request->validate([
            'week_date' => ['nullable', 'date'],
        ]);
        $period = 'week';
        $weekStart = Carbon::now('Asia/Kuala_Lumpur')->startOfWeek();
        $weekEnd = Carbon::now('Asia/Kuala_Lumpur')->endOfWeek();
        $start = $weekStart->copy()->startOfDay();
        $end = $weekEnd->copy()->endOfDay();
        $weekDate = null;
        if (! empty($validated['week_date'])) {
            $selectedDate = Carbon::parse($validated['week_date'], 'Asia/Kuala_Lumpur');
            if ($selectedDate->betweenIncluded($weekStart, $weekEnd)) {
            $weekDate = $selectedDate->toDateString();
            $start = $selectedDate->copy()->startOfDay();
            $end = $selectedDate->copy()->endOfDay();
            }
        }
        $schoolId = (int) $request->user()->school_id;

        if (! $schoolId) {
            return inertia('Dashboard', [
                'filters' => ['period' => $period, 'week_date' => $weekDate],
                'report' => [
                    'type' => 'operator',
                    'school' => null,
                    'ordered' => 0,
                    'collected' => 0,
                    'remaining' => 0,
                    'missed' => 0,
                    'days' => [],
                    'time_slots' => [],
                    'meal_workload' => [],
                    'size_preparation' => [],
                    'student_workload' => [],
                    'next_week_preparation' => [],
                    'next_week_start' => null,
                    'next_week_end' => null,
                ],
            ]);
        }

        $now = Carbon::now('Asia/Kuala_Lumpur');
        $items = OrderItem::query()
            ->whereBetween('scheduled_time', [$start, $end])
            ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
            ->whereHas('student', fn ($query) => $query->where('school_id', $schoolId))
            ->with(['student:id,student_code,name,class,school_id', 'menuItem:id,name,available_start_time,available_end_time', 'collection'])
            ->orderBy('scheduled_time')
            ->get()
            ->map(function (OrderItem $item) use ($now): array {
                $quantity = (int) $item->quantity;
                $collected = min($quantity, (int) ($item->collection?->collected_quantity ?? 0));
                $uncollected = max(0, $quantity - $collected);
                $date = $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->toDateString();
                $startTime = $item->menuItem?->available_start_time ?: $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->format('H:i:s');
                $endTime = $item->menuItem?->available_end_time ?: '23:59:59';
                $sessionStart = Carbon::parse($date.' '.$startTime, 'Asia/Kuala_Lumpur');
                $deadline = Carbon::parse($date.' '.$endTime, 'Asia/Kuala_Lumpur')->addMinutes(15);
                $missed = $now->gt($deadline) ? $uncollected : 0;

                return [
                    'id' => $item->id,
                    'date' => $date,
                    'day' => $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->format('l'),
                    'time' => $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->format('g:i A'),
                    'start_time' => Carbon::parse($startTime)->format('g:i A'),
                    'end_time' => Carbon::parse($endTime)->format('g:i A'),
                    'session_start' => $sessionStart->toIso8601String(),
                    'session_end' => Carbon::parse($date.' '.$endTime, 'Asia/Kuala_Lumpur')->toIso8601String(),
                    'grace_end' => $deadline->toIso8601String(),
                    'student_id' => $item->student_id,
                    'student_code' => $item->student?->student_code,
                    'student_name' => $item->student?->name,
                    'class' => $item->student?->class,
                    'meal_id' => $item->menu_item_id,
                    'meal_name' => $item->menuItem?->name,
                    'size' => strtoupper($item->size ?: 'M'),
                    'ordered' => $quantity,
                    'collected' => $collected,
                    'remaining' => $now->lte($deadline) ? $uncollected : 0,
                    'missed' => $missed,
                ];
            });

        $aggregate = fn ($rows) => [
            'ordered' => $rows->sum('ordered'),
            'collected' => $rows->sum('collected'),
            'remaining' => $rows->sum('remaining'),
            'missed' => $rows->sum('missed'),
        ];

        $days = $items->groupBy('date')->map(function ($rows, $date) use ($aggregate): array {
            return array_merge(['date' => $date, 'day' => $rows->first()['day']], $aggregate($rows));
        })->values();

        $meals = $items->groupBy(fn ($item) => $item['date'].'-'.$item['meal_id'])->map(function ($rows) use ($aggregate): array {
            $first = $rows->first();

            return array_merge([
                'key' => $first['date'].'-'.$first['meal_id'],
                'menu_item_id' => $first['meal_id'],
                'date' => $first['date'],
                'day' => $first['day'],
                'time' => $first['time'],
                'meal_name' => $first['meal_name'],
                'students' => $rows->unique('student_id')->count(),
            ], $aggregate($rows));
        })->values();

        $timeSlots = $items->groupBy(fn ($item) => $item['date'].'-'.$item['session_start'].'-'.$item['session_end'])->map(function ($rows) use ($aggregate): array {
            $first = $rows->first();

            return array_merge([
                'key' => $first['date'].'-'.$first['session_start'].'-'.$first['session_end'],
                'date' => $first['date'],
                'day' => $first['day'],
                'start_time' => $first['start_time'],
                'end_time' => $first['end_time'],
                'session_start' => $first['session_start'],
                'session_end' => $first['session_end'],
                'grace_end' => $first['grace_end'],
                'meals' => $rows->unique('meal_id')->count(),
                'students' => $rows->unique('student_id')->count(),
            ], $aggregate($rows));
        })->values();

        $sizePreparation = $items->groupBy(fn ($item) => $item['date'].'-'.$item['meal_id'])->map(function ($rows): array {
            $first = $rows->first();

            return [
                'key' => $first['date'].'-'.$first['meal_id'],
                'menu_item_id' => $first['meal_id'],
                'date' => $first['date'],
                'day' => $first['day'],
                'time' => $first['start_time'].' – '.$first['end_time'],
                'meal_name' => $first['meal_name'],
                'S' => $rows->where('size', 'S')->sum('ordered'),
                'M' => $rows->where('size', 'M')->sum('ordered'),
                'L' => $rows->where('size', 'L')->sum('ordered'),
                'total' => $rows->sum('ordered'),
            ];
        })->values();
        $completedPreparations = MealPreparation::query()
            ->where('school_id', $schoolId)
            ->whereBetween('preparation_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('completed_at')
            ->get()
            ->keyBy(fn ($item) => $item->preparation_date->toDateString().'-'.$item->menu_item_id);
        $sizePreparation = $sizePreparation->map(function ($meal) use ($completedPreparations): array {
            $meal['is_completed'] = $completedPreparations->has($meal['key']);
            return $meal;
        });

        $nextWeekStart = $now->copy()->addWeek()->startOfWeek()->startOfDay();
        $nextWeekEnd = $nextWeekStart->copy()->endOfWeek()->endOfDay();
        $nextWeekPreparation = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('payments as p', 'p.order_id', '=', 'o.id')
            ->join('students as s', 's.id', '=', 'oi.student_id')
            ->join('menu_items as mi', 'mi.id', '=', 'oi.menu_item_id')
            ->where('p.status', 'paid')
            ->where('s.school_id', $schoolId)
            ->whereBetween('oi.scheduled_time', [$nextWeekStart, $nextWeekEnd])
            ->get(['oi.menu_item_id', 'mi.name as meal_name', 'mi.available_start_time', 'mi.available_end_time', 'oi.scheduled_time', 'oi.size', 'oi.quantity'])
            ->groupBy(function ($item): string {
                $date = Carbon::parse($item->scheduled_time, 'Asia/Kuala_Lumpur')->toDateString();

                return $date.'-'.$item->menu_item_id;
            })
            ->map(function ($rows): array {
                $first = $rows->first();
                $date = Carbon::parse($first->scheduled_time, 'Asia/Kuala_Lumpur');
                $formatTime = fn ($time) => $time ? Carbon::parse($time)->format('g:i A') : '-';

                return [
                    'key' => $date->toDateString().'-'.$first->menu_item_id,
                    'date' => $date->toDateString(),
                    'day' => $date->format('l'),
                    'time' => $formatTime($first->available_start_time).' – '.$formatTime($first->available_end_time),
                    'meal' => $first->meal_name,
                    'S' => (int) $rows->where('size', 'S')->sum('quantity'),
                    'M' => (int) $rows->where('size', 'M')->sum('quantity'),
                    'L' => (int) $rows->where('size', 'L')->sum('quantity'),
                    'total' => (int) $rows->sum('quantity'),
                ];
            })
            ->sortBy(fn ($item) => $item['date'].'-'.$item['time'].'-'.$item['meal'])
            ->values();

        return inertia('Dashboard', [
            'filters' => ['period' => $period, 'week_date' => $weekDate],
            'report' => array_merge([
                'type' => 'operator',
                'school' => School::query()->find($schoolId, ['id', 'name']),
                'days' => $days,
                'time_slots' => $timeSlots,
                'meal_workload' => $meals,
                'size_preparation' => $sizePreparation,
                'student_workload' => $items->values(),
                'next_week_preparation' => $nextWeekPreparation,
                'next_week_start' => $nextWeekStart->toDateString(),
                'next_week_end' => $nextWeekEnd->toDateString(),
            ], $aggregate($items)),
        ]);
    }

    private function dateRange(string $period, array $validated): array
    {
        $now = Carbon::now('Asia/Kuala_Lumpur');

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'custom' => [
                isset($validated['start_date'])
                    ? Carbon::parse($validated['start_date'], 'Asia/Kuala_Lumpur')->startOfDay()
                    : $now->copy()->startOfDay(),
                isset($validated['end_date'])
                    ? Carbon::parse($validated['end_date'], 'Asia/Kuala_Lumpur')->endOfDay()
                    : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function collectionTotals(Carbon $start, Carbon $end, ?int $schoolId): array
    {
        $now = Carbon::now('Asia/Kuala_Lumpur');
        $collected = 0;
        $missed = 0;

        OrderItem::query()
            ->whereBetween('scheduled_time', [$start, $end])
            ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
            ->when($schoolId, fn ($query) => $query->whereHas('student', fn ($query) => $query->where('school_id', $schoolId)))
            ->with(['menuItem:id,available_end_time', 'collection'])
            ->chunkById(200, function ($items) use ($now, &$collected, &$missed): void {
                foreach ($items as $item) {
                    $itemCollected = min((int) $item->quantity, (int) ($item->collection?->collected_quantity ?? 0));
                    $collected += $itemCollected;
                    $date = $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->toDateString();
                    $endTime = $item->menuItem?->available_end_time ?: '23:59:59';
                    $deadline = Carbon::parse($date.' '.$endTime, 'Asia/Kuala_Lumpur')->addMinutes(15);

                    if ($now->gt($deadline)) {
                        $missed += max(0, (int) $item->quantity - $itemCollected);
                    }
                }
            });

        return [$collected, $missed];
    }
}
