<?php

namespace App\Http\Controllers;

use App\Models\MealPreparation;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreparationPlanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('operator'), 403);
        $validated = $request->validate([
            'week' => ['nullable', Rule::in(['this', 'next', 'custom'])],
            'start_date' => ['nullable', 'date', 'required_if:week,custom'],
            'end_date' => ['nullable', 'date', 'required_if:week,custom', 'after_or_equal:start_date'],
        ]);
        $week = $validated['week'] ?? (Carbon::now('Asia/Kuala_Lumpur')->isSunday() ? 'next' : 'this');
        $schoolId = (int) $request->user()->school_id;
        $start = Carbon::now('Asia/Kuala_Lumpur')->startOfWeek()->startOfDay();
        if ($week === 'next') $start->addWeek();
        if ($week === 'custom') {
            $start = Carbon::parse($validated['start_date'], 'Asia/Kuala_Lumpur')->startOfDay();
            $end = Carbon::parse($validated['end_date'], 'Asia/Kuala_Lumpur')->endOfDay();
        } else {
            $end = $start->copy()->endOfWeek()->endOfDay();
        }
        $now = Carbon::now('Asia/Kuala_Lumpur');

        $items = $schoolId ? OrderItem::query()
            ->whereBetween('scheduled_time', [$start, $end])
            ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
            ->whereHas('student', fn ($query) => $query->where('school_id', $schoolId))
            ->with(['student:id,school_id', 'menuItem:id,name,available_start_time,available_end_time', 'collection'])
            ->orderBy('scheduled_time')->get()
            ->map(function (OrderItem $item) use ($now): array {
                $quantity = (int) $item->quantity;
                $collected = min($quantity, (int) ($item->collection?->collected_quantity ?? 0));
                $date = $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->toDateString();
                $startTime = $item->menuItem?->available_start_time ?: $item->scheduled_time->format('H:i:s');
                $endTime = $item->menuItem?->available_end_time ?: '23:59:59';
                $sessionStart = Carbon::parse($date.' '.$startTime, 'Asia/Kuala_Lumpur');
                $sessionEnd = Carbon::parse($date.' '.$endTime, 'Asia/Kuala_Lumpur');
                $graceEnd = $sessionEnd->copy()->addMinutes(15);
                $uncollected = max(0, $quantity - $collected);
                return ['date' => $date, 'day' => $item->scheduled_time->format('l'), 'menu_item_id' => $item->menu_item_id,
                    'meal' => $item->menuItem?->name, 'size' => strtoupper($item->size ?: 'M'), 'quantity' => $quantity,
                    'collected' => $collected, 'remaining' => $now->lte($graceEnd) ? $uncollected : 0,
                    'missed' => $now->gt($graceEnd) ? $uncollected : 0, 'student_id' => $item->student_id,
                    'start_time' => $sessionStart->format('g:i A'), 'end_time' => $sessionEnd->format('g:i A'),
                    'session_start' => $sessionStart->toIso8601String(), 'session_end' => $sessionEnd->toIso8601String(),
                    'grace_end' => $graceEnd->toIso8601String()];
            }) : collect();

        $completed = MealPreparation::query()->where('school_id', $schoolId)
            ->whereBetween('preparation_date', [$start->toDateString(), $end->toDateString()])->whereNotNull('completed_at')
            ->get()->keyBy(fn ($item) => $item->preparation_date->toDateString().'-'.$item->menu_item_id);
        $meals = $items->groupBy(fn ($item) => $item['date'].'-'.$item['menu_item_id'])->map(function ($rows) use ($completed): array {
            $first = $rows->first(); $key = $first['date'].'-'.$first['menu_item_id'];
            return ['key' => $key, 'menu_item_id' => $first['menu_item_id'], 'date' => $first['date'], 'day' => $first['day'],
                'time' => $first['start_time'].' – '.$first['end_time'], 'meal' => $first['meal'],
                'S' => $rows->where('size', 'S')->sum('quantity'), 'M' => $rows->where('size', 'M')->sum('quantity'),
                'L' => $rows->where('size', 'L')->sum('quantity'), 'total' => $rows->sum('quantity'),
                'students' => $rows->unique('student_id')->count(), 'collected' => $rows->sum('collected'),
                'remaining' => $rows->sum('remaining'), 'missed' => $rows->sum('missed'), 'is_completed' => $completed->has($key)];
        })->sortBy(fn ($item) => $item['date'].'-'.$item['time'].'-'.$item['meal'])->values();
        $sessions = $items->groupBy(fn ($item) => $item['date'].'-'.$item['session_start'])->map(function ($rows): array {
            $first = $rows->first();
            return ['key' => $first['date'].'-'.$first['session_start'], 'date' => $first['date'], 'day' => $first['day'],
                'start_time' => $first['start_time'], 'end_time' => $first['end_time'], 'session_start' => $first['session_start'],
                'session_end' => $first['session_end'], 'grace_end' => $first['grace_end'], 'ordered' => $rows->sum('quantity'),
                'collected' => $rows->sum('collected'), 'remaining' => $rows->sum('remaining'), 'missed' => $rows->sum('missed'),
                'students' => $rows->unique('student_id')->count(), 'meals' => $rows->unique('menu_item_id')->count()];
        })->values();

        return inertia('Operator/PreparationPlan', ['school' => $request->user()->school?->only(['id', 'name']),
            'week' => $week, 'weekStart' => $start->toDateString(), 'weekEnd' => $end->toDateString(),
            'startDate' => $start->toDateString(), 'endDate' => $end->toDateString(), 'meals' => $meals, 'sessions' => $sessions]);
    }
}
