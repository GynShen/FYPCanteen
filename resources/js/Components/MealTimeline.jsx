import { useMemo } from 'react';

const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const dayName = (day) => day.charAt(0).toUpperCase() + day.slice(1);
const hourWidth = 120;

const minutesFor = (value) => {
    if (!value) return null;
    const [hour, minute] = value.slice(0, 5).split(':').map(Number);
    return hour * 60 + minute;
};

const timeLabel = (minutes) => {
    const hour = Math.floor(minutes / 60);
    const minute = minutes % 60;
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${String(minute).padStart(2, '0')} ${hour < 12 ? 'AM' : 'PM'}`;
};

const layoutMeals = (meals) => {
    const laneEnds = [];
    return meals
        .sort((a, b) => a.start - b.start || a.end - b.end)
        .map((entry) => {
            let lane = laneEnds.findIndex((end) => end <= entry.start);
            if (lane === -1) lane = laneEnds.length;
            laneEnds[lane] = entry.end;
            return { ...entry, lane };
        });
};

export default function MealTimeline({ menuItems, onMealClick, getSelection, dayDates = {} }) {
    const configuredMeals = menuItems.filter(
        (meal) => meal.available_start_time && meal.available_end_time,
    );
    const unconfiguredMeals = menuItems.filter(
        (meal) => !meal.available_start_time || !meal.available_end_time,
    );
    const bounds = useMemo(() => {
        if (configuredMeals.length === 0) return { start: 8 * 60, end: 18 * 60 };
        const starts = configuredMeals.map((meal) => minutesFor(meal.available_start_time));
        const ends = configuredMeals.map((meal) => minutesFor(meal.available_end_time));
        return {
            start: Math.floor(Math.min(...starts) / 60) * 60,
            end: Math.ceil(Math.max(...ends) / 60) * 60,
        };
    }, [menuItems]);
    const hours = Array.from(
        { length: Math.max(1, (bounds.end - bounds.start) / 60) },
        (_, index) => bounds.start + index * 60,
    );
    const canvasWidth = hours.length * hourWidth;

    return (
        <div className="space-y-4">
            <div className="overflow-x-auto rounded-lg border border-slate-300 bg-white">
                <div style={{ minWidth: canvasWidth + 112 }}>
                    <div className="flex border-b border-slate-300 bg-sky-600 text-white">
                        <div className="sticky left-0 z-30 flex w-28 shrink-0 items-center justify-center border-r border-sky-400 bg-sky-700 px-2 py-3 text-xs font-bold uppercase">Day / Time</div>
                        <div className="relative h-12" style={{ width: canvasWidth }}>
                            {hours.map((hour, index) => (
                                <div key={hour} className="absolute inset-y-0 border-r border-sky-400 px-2 py-2 text-xs font-semibold" style={{ left: index * hourWidth, width: hourWidth }}>
                                    {timeLabel(hour)}
                                </div>
                            ))}
                        </div>
                    </div>
                    {weekDays.map((day) => {
                        const entries = layoutMeals(configuredMeals
                            .filter((meal) => meal.days.some((item) => item.day_of_week === day))
                            .map((meal) => ({ meal, start: minutesFor(meal.available_start_time), end: minutesFor(meal.available_end_time) })));
                        const laneCount = Math.max(1, ...entries.map((entry) => entry.lane + 1));
                        const rowHeight = Math.max(72, laneCount * 70 + 8);

                        return (
                            <div key={day} className="flex border-b border-slate-300 last:border-b-0">
                                <div className="sticky left-0 z-20 flex w-28 shrink-0 flex-col items-center justify-center border-r border-slate-300 bg-amber-50 px-2 text-center text-sm font-bold text-slate-800" style={{ height: rowHeight }}>
                                    <span>{dayName(day)}</span>
                                    {dayDates[day] && <span className="mt-1 text-xs font-semibold text-slate-600">{dayDates[day]}</span>}
                                </div>
                                <div className="relative bg-slate-50" style={{ width: canvasWidth, height: rowHeight }}>
                                    {hours.map((hour, index) => <span key={hour} className="absolute inset-y-0 border-r border-dashed border-slate-300" style={{ left: (index + 1) * hourWidth }} />)}
                                    {entries.map(({ meal, start, end, lane }) => {
                                        const selection = getSelection?.(meal, day);
                                        const left = ((start - bounds.start) / 60) * hourWidth;
                                        const width = Math.max(88, ((end - start) / 60) * hourWidth);
                                        return (
                                            <button
                                                key={meal.id}
                                                type="button"
                                                onClick={() => onMealClick?.(meal, day)}
                                                className={`absolute overflow-hidden rounded-md border px-2 py-1.5 text-left shadow-sm transition hover:z-10 hover:border-indigo-500 hover:shadow-md ${selection ? 'border-indigo-400 bg-indigo-100' : meal.is_available ? 'border-emerald-300 bg-emerald-100' : 'border-slate-300 bg-slate-200'}`}
                                                style={{ left, top: lane * 70 + 5, width, height: 60 }}
                                            >
                                                <span className="block truncate text-xs font-bold text-slate-900">{meal.name}</span>
                                                <span className="block truncate text-[10px] font-medium text-slate-600">{meal.available_start_time.slice(0, 5)}–{meal.available_end_time.slice(0, 5)}</span>
                                                {selection && <span className="block truncate text-[10px] font-bold text-indigo-700">Qty {selection.quantity} · {selection.size ?? 'M'}</span>}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
            {unconfiguredMeals.length > 0 && (
                <div className="rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <p className="text-sm font-semibold text-amber-900">Collection time not set</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                        {unconfiguredMeals.map((meal) => <button key={meal.id} type="button" onClick={() => onMealClick?.(meal, meal.days[0]?.day_of_week)} className="rounded-md border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-slate-800 hover:border-indigo-400">{meal.name}</button>)}
                    </div>
                </div>
            )}
        </div>
    );
}
