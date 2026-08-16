<?php

namespace App\Http\Controllers;

use App\Models\MealPreparation;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PreparationOverviewController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['this', 'next', 'custom'])],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:start_date'],
        ]);
        $period = $validated['period'] ?? 'next';
        $schoolId = isset($validated['school_id']) ? (int) $validated['school_id'] : null;
        $start = Carbon::now('Asia/Kuala_Lumpur')->startOfWeek()->startOfDay();
        if ($period === 'next') $start->addWeek();
        $end = $start->copy()->endOfWeek()->endOfDay();
        if ($period === 'custom') {
            $start = Carbon::parse($validated['start_date'], 'Asia/Kuala_Lumpur')->startOfDay();
            $end = Carbon::parse($validated['end_date'], 'Asia/Kuala_Lumpur')->endOfDay();
        }

        $rows = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('payments as p', 'p.order_id', '=', 'o.id')->join('students as s', 's.id', '=', 'oi.student_id')
            ->join('schools as sc', 'sc.id', '=', 's.school_id')->join('menu_items as mi', 'mi.id', '=', 'oi.menu_item_id')
            ->where('p.status', 'paid')->whereBetween('oi.scheduled_time', [$start, $end])
            ->when($schoolId, fn ($query) => $query->where('s.school_id', $schoolId))
            ->get(['s.school_id', 'sc.name as school_name', 'oi.menu_item_id', 'mi.name as meal_name', 'mi.available_start_time', 'mi.available_end_time', 'oi.scheduled_time', 'oi.size', 'oi.quantity']);
        $completed = MealPreparation::query()->whereBetween('preparation_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('completed_at')->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))->get()
            ->keyBy(fn ($item) => $item->school_id.'-'.$item->preparation_date->toDateString().'-'.$item->menu_item_id);
        $batches = $rows->groupBy(fn ($item) => $item->school_id.'-'.Carbon::parse($item->scheduled_time)->toDateString().'-'.$item->menu_item_id)
            ->map(function ($items, $key) use ($completed): array { $first = $items->first(); $date = Carbon::parse($first->scheduled_time, 'Asia/Kuala_Lumpur'); $time = fn ($value) => $value ? Carbon::parse($value)->format('g:i A') : '-'; return [
                'key' => $key, 'school' => $first->school_name, 'date' => $date->toDateString(), 'day' => $date->format('l'),
                'time' => $time($first->available_start_time).' – '.$time($first->available_end_time), 'meal' => $first->meal_name,
                'S' => (int) $items->where('size', 'S')->sum('quantity'), 'M' => (int) $items->where('size', 'M')->sum('quantity'),
                'L' => (int) $items->where('size', 'L')->sum('quantity'), 'total' => (int) $items->sum('quantity'), 'prepared' => $completed->has($key),
            ]; })->sortBy(fn ($item) => $item['date'].'-'.$item['school'].'-'.$item['time'])->values();

        return inertia('Owner/PreparationOverview', ['schools' => School::orderBy('name')->get(['id', 'name']),
            'filters' => ['period' => $period, 'school_id' => $schoolId, 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
            'batches' => $batches, 'summary' => ['portions' => $batches->sum('total'), 'batches' => $batches->count(),
                'prepared' => $batches->where('prepared', true)->count(), 'schools' => $rows->pluck('school_id')->unique()->count()]]);
    }
}
