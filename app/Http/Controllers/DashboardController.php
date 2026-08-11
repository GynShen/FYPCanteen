<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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

        return inertia('Dashboard', [
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'period' => $period,
                'school_id' => $schoolId,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'report' => [
                'revenue' => round((float) $summary->revenue, 2),
                'ordered' => (int) $summary->ordered,
                'collected' => $collected,
                'missed' => $missed,
                'collection_rate' => $collectionBase > 0 ? round($collected / $collectionBase * 100, 1) : 0,
                'revenue_trend' => $revenueTrend,
                'most_ordered' => $mealOccurrences->sortByDesc('quantity')->take(5)->values(),
                'least_ordered' => $mealOccurrences->sortBy('quantity')->take(5)->values(),
                'still_hungry' => $stillHungry,
            ],
        ]);
    }

    private function dateRange(string $period, array $validated): array
    {
        $now = Carbon::now('Asia/Kuala_Lumpur');

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'custom' => [
                Carbon::parse($validated['start_date'] ?? $now->toDateString(), 'Asia/Kuala_Lumpur')->startOfDay(),
                Carbon::parse($validated['end_date'] ?? $now->toDateString(), 'Asia/Kuala_Lumpur')->endOfDay(),
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
