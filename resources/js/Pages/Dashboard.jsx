import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const money = (value) => `RM ${Number(value).toFixed(2)}`;

function MealTable({ title, rows }) {
    return (
        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">{title}</h3>
            <div className="max-h-[307px] overflow-auto"><table className="w-full">
                <thead className="sticky top-0 z-10 bg-slate-700 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-5 py-3 text-left">Meal</th><th className="px-5 py-3 text-right">Quantity</th><th className="px-5 py-3 text-right">Revenue</th></tr></thead>
                <tbody className="divide-y divide-slate-700">
                    {rows.map((meal) => <tr key={meal.id} className="bg-slate-800 text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-5 py-4 font-semibold text-white">{meal.name}</td><td className="px-5 py-4 text-right">{meal.quantity}</td><td className="px-5 py-4 text-right font-semibold text-emerald-400">{money(meal.revenue)}</td></tr>)}
                    {rows.length === 0 && <tr><td colSpan="3" className="px-4 py-10 text-center text-sm text-slate-400">No paid meal orders in this period.</td></tr>}
                </tbody>
            </table></div>
        </section>
    );
}

function OperatorReport({ report, weekDate, startDate, endDate, update, updateRange }) {
    const cards = [
        ['Meals to collect', report.ordered, '#818cf8'],
        ['Collected', report.collected, '#34d399'],
        ['Still remaining', report.remaining, '#fbbf24'],
        ['Missed', report.missed, '#f87171'],
    ];
    const today = new Date();
    const mondayOffset = (today.getDay() + 6) % 7;
    const weekMonday = new Date(today);
    weekMonday.setHours(0, 0, 0, 0);
    weekMonday.setDate(today.getDate() - mondayOffset);
    const localDate = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const todayValue = localDate(today);
    const weekDates = Array.from({ length: 7 }, (_, index) => {
        const date = new Date(weekMonday);
        date.setDate(weekMonday.getDate() + index);
        return { value: localDate(date), label: date.toLocaleDateString('en-MY', { weekday: 'long', day: '2-digit', month: 'short' }) };
    });
    return <>
        <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            <div className="grid items-end gap-4 lg:grid-cols-[1fr_240px_220px_220px]">
                <div>
                    <h2 className="text-lg font-bold text-white">Collection workload</h2>
                    <p className="mt-1 text-sm text-slate-400">{report.school ? `${report.school.name} only` : 'No school has been assigned to this operator.'}</p>
                </div>
                <label className="text-sm font-semibold text-slate-200">Week date
                    <select value={weekDate} onChange={(event) => update('week_date', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900">
                        <option value="">Overall — whole week</option>
                        {weekDates.map((date) => <option key={date.value} value={date.value}>{date.label}{date.value === todayValue ? ' (Today)' : ''}</option>)}
                        <option value="custom">Custom date range</option>
                    </select>
                </label>
                <label className="text-sm font-semibold text-slate-200">Start date<input type="date" value={startDate} max={endDate || undefined} onChange={(event) => updateRange('start_date', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900" /></label>
                <label className="text-sm font-semibold text-slate-200">End date<input type="date" value={endDate} min={startDate || undefined} onChange={(event) => updateRange('end_date', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900" /></label>
            </div>
        </section>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {cards.map(([label, value, accent]) => <div key={label} className="rounded-2xl border border-slate-700 border-t-4 bg-slate-800 p-5 text-center shadow-lg" style={{ borderTopColor: accent }}><p className="text-sm font-semibold text-slate-300">{label}</p><p className="mt-2 text-3xl font-bold" style={{ color: accent }}>{value}</p></div>)}
        </div>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Daily collection summary</h3>
            <div className="max-h-[420px] overflow-auto"><table className="w-full min-w-[700px] [&_td:nth-child(n+3)]:text-center [&_th:nth-child(n+3)]:text-center">
                <thead className="sticky top-0 z-10 bg-slate-700 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-5 py-3 text-left">Day</th><th className="px-5 py-3 text-left">Date</th><th className="px-5 py-3 text-right">Required</th><th className="px-5 py-3 text-right">Collected</th><th className="px-5 py-3 text-right">Remaining</th><th className="px-5 py-3 text-right">Missed</th></tr></thead>
                <tbody className="divide-y divide-slate-700">{report.days.map((day) => <tr key={day.date} className="text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-5 py-4 font-semibold text-white">{day.day}</td><td className="px-5 py-4">{day.date}</td><td className="px-5 py-4 text-right">{day.ordered}</td><td className="px-5 py-4 text-right text-emerald-400">{day.collected}</td><td className="px-5 py-4 text-right text-amber-400">{day.remaining}</td><td className="px-5 py-4 text-right text-red-400">{day.missed}</td></tr>)}{report.days.length === 0 && <tr><td colSpan="6" className="px-5 py-10 text-center text-sm text-slate-400">No paid meals require collection in this period.</td></tr>}</tbody>
            </table></div>
        </section>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <div className="border-b border-slate-700 px-5 py-4"><h3 className="text-lg font-bold text-white">Latest collection status</h3><p className="mt-1 text-sm text-slate-400">Latest collected and missed meal updates for the selected day or week.</p></div>
            <div className="max-h-[420px] overflow-auto"><table className="w-full min-w-[760px]">
                <thead className="sticky top-0 z-10 bg-slate-700 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-4 py-3 text-left">Updated</th><th className="px-4 py-3 text-left">Student</th><th className="px-4 py-3 text-left">Class</th><th className="px-4 py-3 text-left">Meal</th><th className="px-4 py-3 text-center">Status</th><th className="px-4 py-3 text-center">Quantity</th></tr></thead>
                <tbody className="divide-y divide-slate-700">{report.latest_collection_status.map((item) => { const event = new Date(item.event_at); const collected = item.status === 'collected'; return <tr key={item.key} className="text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-4 py-4">{event.toLocaleDateString('en-MY')}<br/><span className="text-xs text-slate-400">{event.toLocaleTimeString('en-MY', { hour: 'numeric', minute: '2-digit' })}</span></td><td className="px-4 py-4 font-semibold text-white">{item.student_name}</td><td className="px-4 py-4">{item.class}</td><td className="px-4 py-4 font-medium text-white">{item.meal_name}</td><td className="px-4 py-4 text-center"><span className={`inline-flex rounded-full border px-3 py-1 text-xs font-bold ${collected ? 'border-emerald-500 bg-emerald-500/15 text-emerald-300' : 'border-red-500 bg-red-500/15 text-red-300'}`}>{collected ? 'Collected' : 'Missed'}</span></td><td className="px-4 py-4 text-center font-bold">{item.quantity}</td></tr>; })}{report.latest_collection_status.length === 0 && <tr><td colSpan="6" className="px-5 py-10 text-center text-sm text-slate-400">No collected or missed meal updates in this period.</td></tr>}</tbody>
            </table></div>
        </section>
    </>;
}

function CustomerReport({ report, month, childId, update }) {
    const monthDate = new Date(`${month}-01T00:00:00`);
    const daysInMonth = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).getDate();
    const mondayOffset = (monthDate.getDay() + 6) % 7;
    const calendarEntries = new Map(report.calendar_days.map((entry) => [entry.date, entry.meals]));
    const mealColors = ['#818cf8', '#34d399', '#fbbf24', '#f472b6', '#22d3ee', '#c084fc', '#fb7185'];
    const colorForMeal = (mealId) => mealColors[Number(mealId) % mealColors.length];
    const calendarCells = [
        ...Array.from({ length: mondayOffset }, () => null),
        ...Array.from({ length: daysInMonth }, (_, index) => index + 1),
    ];

    return <>
        <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            <h3 className="text-lg font-bold text-white">Upcoming meals by child</h3>
            <p className="mt-1 text-sm text-slate-400">The next paid meal for each child, independent of the monthly report filter.</p>
            {report.upcoming_meals.length > 0 ? <div className="mt-4 space-y-3">{report.upcoming_meals.map((meal) => <article key={meal.id} className="grid gap-4 rounded-xl border border-indigo-500/50 bg-indigo-500/10 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-center"><div><p className="text-xs uppercase text-slate-400">Child</p><p className="text-lg font-bold text-white">{meal.student_name}</p><p className="text-xs text-slate-400">{meal.school_name}</p></div><div><p className="text-xs uppercase text-slate-400">Meal</p><p className="font-bold text-white">{meal.meal_name}</p></div><div><p className="text-xs uppercase text-slate-400">Collection</p><p className="font-bold text-white">{meal.date}</p><p className="text-xs text-slate-300">{meal.time}</p></div><div><p className="text-xs uppercase text-slate-400">Order</p><p className="font-bold text-white">Qty {meal.quantity}</p></div></article>)}</div> : <p className="mt-4 rounded-xl bg-slate-700/50 p-6 text-center text-sm text-slate-400">No upcoming paid meals.</p>}
        </section>
        <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            <div className="grid items-end gap-4 sm:grid-cols-2">
                <div><h2 className="text-xl font-bold text-white">Family Meal Summary</h2><p className="mt-1 text-sm text-slate-400">Review spending and meal collection for your children.</p></div>
                <label className="text-sm font-semibold text-slate-200 sm:w-64 sm:justify-self-end">Month
                    <select value={month} onChange={(event) => update('month', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900">
                        {report.available_months.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                        {report.available_months.length === 0 && <option value={month}>{report.month_label}</option>}
                    </select>
                </label>
            </div>
        </section>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            {[
                ['Total paid', money(report.spent), '#34d399'], ['Meals ordered', report.ordered, '#818cf8'],
                ['Received', report.collected, '#22d3ee'], ['Missed', report.missed, '#f87171'], ['Upcoming', report.upcoming_count, '#fbbf24'],
            ].map(([label, value, accent]) => <div key={label} className="rounded-2xl border border-slate-700 border-t-4 bg-slate-800 p-5 shadow-lg" style={{ borderTopColor: accent }}><p className="text-sm font-semibold text-slate-300">{label}</p><p className="mt-2 text-3xl font-bold" style={{ color: accent }}>{value}</p></div>)}
        </div>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Summary by child</h3>
            <div className="overflow-x-auto"><table className="w-full min-w-[850px]"><thead className="bg-slate-700 text-xs uppercase text-slate-200"><tr><th className="px-5 py-3 text-left">Child</th><th className="px-5 py-3 text-left">School</th><th className="px-5 py-3 text-right">Paid</th><th className="px-5 py-3 text-right">Ordered</th><th className="px-5 py-3 text-right">Received</th><th className="px-5 py-3 text-right">Missed</th><th className="px-5 py-3 text-right">Upcoming</th><th className="px-5 py-3 text-right">Still Hungry</th></tr></thead><tbody className="divide-y divide-slate-700">{report.children.map((child) => <tr key={child.id} className="text-sm text-slate-200"><td className="px-5 py-4 font-bold text-white">{child.name}</td><td className="px-5 py-4">{child.school}</td><td className="px-5 py-4 text-right text-emerald-400">{money(child.spent)}</td><td className="px-5 py-4 text-right">{child.ordered}</td><td className="px-5 py-4 text-right text-cyan-400">{child.collected}</td><td className="px-5 py-4 text-right text-red-400">{child.missed}</td><td className="px-5 py-4 text-right text-amber-400">{child.upcoming}</td><td className="px-5 py-4 text-right font-bold text-orange-400">{child.still_hungry}</td></tr>)}{report.children.length === 0 && <tr><td colSpan="8" className="px-5 py-10 text-center text-slate-400">No paid meals in this month.</td></tr>}</tbody></table></div>
        </section>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg"><h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Still-hungry history</h3><div className="divide-y divide-slate-700">{report.still_hungry.map((item) => <div key={item.id} className="grid gap-2 px-5 py-4 text-sm sm:grid-cols-4"><span className="font-bold text-white">{item.student_name}</span><span className="text-slate-200">{item.meal_name}</span><span className="text-slate-300">{item.date} · {item.time}</span><span className="font-semibold text-amber-400 sm:text-right">Qty {item.quantity}</span></div>)}{report.still_hungry.length === 0 && <p className="px-5 py-10 text-center text-sm text-slate-400">No still-hungry feedback in this month.</p>}</div></section>

        <div className="grid gap-6 lg:grid-cols-2">
            <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg lg:col-span-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div><h3 className="text-lg font-bold text-white">Monthly Meal Calendar</h3><p className="mt-1 text-sm text-slate-400">Repeated colours help you notice when the same meal is ordered frequently.</p></div>
                    <div className="flex flex-wrap items-end gap-3">
                        <label className="text-xs font-semibold text-slate-300">Child
                            <select value={childId} onChange={(event) => update('child_id', event.target.value)} className="mt-1 block w-52 rounded-md border-slate-300 bg-white py-1.5 text-sm text-slate-900">
                                <option value="">All children</option>
                                {report.linked_children.map((child) => <option key={child.id} value={child.id}>{child.name}</option>)}
                            </select>
                        </label>
                        <span className="mb-1 rounded-full bg-indigo-500/20 px-3 py-1 text-sm font-bold text-indigo-300">{report.month_label}</span>
                    </div>
                </div>
                <div className="mt-5 overflow-x-auto"><div className="min-w-[760px] overflow-hidden rounded-xl border border-slate-600">
                    <div className="grid grid-cols-7 bg-slate-700">{['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].map((day) => <div key={day} className="border-r border-slate-600 px-2 py-3 text-center text-xs font-bold uppercase text-slate-200 last:border-r-0">{day}</div>)}</div>
                    <div className="grid grid-cols-7 bg-slate-900/30">{calendarCells.map((day, index) => {
                        if (!day) return <div key={`empty-${index}`} className="min-h-28 border-r border-t border-slate-700 bg-slate-900/20" />;
                        const date = `${month}-${String(day).padStart(2, '0')}`;
                        const meals = calendarEntries.get(date) ?? [];
                        return <div key={date} className="min-h-28 border-r border-t border-slate-700 p-2"><p className="text-sm font-bold text-slate-300">{day}</p><div className="mt-2 space-y-1.5">{meals.map((meal) => <div key={meal.id} className="rounded-md px-2 py-1.5 text-xs font-semibold text-slate-950" style={{ backgroundColor: colorForMeal(meal.id) }}><span className="block truncate" title={meal.name}>{meal.name}</span><span className="text-[10px] opacity-80">Qty {meal.quantity}</span></div>)}</div></div>;
                    })}</div>
                </div></div>
                {report.most_ordered.length > 0 && <div className="mt-4 flex flex-wrap gap-2">{report.most_ordered.map((meal) => <span key={meal.id} className="rounded-full border border-slate-600 px-3 py-1 text-xs font-semibold text-slate-200"><span className="mr-2 inline-block h-2.5 w-2.5 rounded-full" style={{ backgroundColor: colorForMeal(meal.id) }} />{meal.name}: {meal.quantity} portions / {meal.order_days} days</span>)}</div>}
                {report.most_ordered[0]?.order_days >= 3 && <p className="mt-4 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm font-semibold text-amber-300">{report.most_ordered[0].name} was ordered repeatedly this month. Consider choosing a wider variety of meals.</p>}
            </section>
        </div>

    </>;
}

export default function Dashboard({ report, schools = [], filters = {} }) {
    const user = usePage().props.auth.user;
    const [form, setForm] = useState({
        period: filters.period ?? 'month',
        school_id: filters.school_id ?? '',
        start_date: filters.start_date ?? '',
        end_date: filters.end_date ?? '',
        month: filters.month ?? '',
        child_id: filters.child_id ?? '',
        week_date: filters.week_date ?? '',
    });

    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));
    const updateOperatorRange = (name, value) => setForm((current) => ({ ...current, week_date: 'custom', [name]: value }));
    const updateExactPeriod = (name, value) => setForm((current) => ({
        ...current,
        period: 'custom',
        [name]: value,
    }));

    useEffect(() => {
        const shouldSyncOwner = report?.type === 'owner' && form.period !== 'custom';
        const shouldSyncOperator = report?.type === 'operator' && form.week_date !== 'custom';
        if (!shouldSyncOwner && !shouldSyncOperator) return;
        setForm((current) => ({
            ...current,
            start_date: filters.start_date ?? current.start_date,
            end_date: filters.end_date ?? current.end_date,
        }));
    }, [filters.start_date, filters.end_date]);

    useEffect(() => {
        if (!report) return undefined;

        const timer = window.setTimeout(() => {
            router.get(route('dashboard'), form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['report', 'filters'],
            });
        }, 500);

        return () => window.clearTimeout(timer);
    }, [form.period, form.school_id, form.start_date, form.end_date, form.month, form.child_id, form.week_date]);

    const isOwnerReport = report?.type === 'owner';
    const maxRevenue = isOwnerReport ? Math.max(1, ...report.revenue_trend.map((point) => Number(point.revenue))) : 1;
    const collectionTotal = isOwnerReport ? report.collected + report.missed : 0;
    const sortedMealRankings = isOwnerReport ? [...report.meal_rankings].sort((first, second) => Number(second.quantity) - Number(first.quantity)) : [];
    const rankingSplit = Math.ceil(sortedMealRankings.length / 2);
    const famousMeals = sortedMealRankings.slice(0, rankingSplit);
    const lessFamousMeals = sortedMealRankings.slice(rankingSplit).reverse();

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard</h2>}>
            <Head title="Dashboard" />
            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Welcome, {user.name}!</h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{report ? 'Review canteen performance and meal activity.' : 'You are logged in to FYPCanteen.'}</p>
                    </div>

                    {report?.type === 'operator' && <OperatorReport report={report} weekDate={form.week_date} startDate={form.start_date} endDate={form.end_date} update={update} updateRange={updateOperatorRange} />}
                    {report?.type === 'customer' && <CustomerReport report={report} month={form.month} childId={form.child_id} update={update} />}

                    {isOwnerReport && <>
                        <section className="rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-sm">
                            <div className="mb-4">
                                <h2 className="text-lg font-bold text-white">Report filters</h2>
                                <p className="text-sm text-slate-400">The report refreshes automatically after you change a filter.</p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <label className="text-sm font-semibold text-slate-200">School<select value={form.school_id} onChange={(event) => update('school_id', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900"><option value="">All schools</option>{schools.map((school) => <option key={school.id} value={school.id}>{school.name}</option>)}</select></label>
                                <label className="text-sm font-semibold text-slate-200">Period<select value={form.period} onChange={(event) => update('period', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900"><option value="today">Today</option><option value="week">This week</option><option value="month">This month</option><option value="custom">Exact period</option></select></label>
                                <label className="text-sm font-semibold text-slate-200">Start date<input type="date" value={form.start_date} onChange={(event) => updateExactPeriod('start_date', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900" /></label><label className="text-sm font-semibold text-slate-200">End date<input type="date" value={form.end_date} onChange={(event) => updateExactPeriod('end_date', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900" /></label>
                            </div>
                        </section>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                            {[
                                ['Revenue', money(report.revenue), '#34d399'],
                                ['Meals ordered', report.ordered, '#818cf8'],
                                ['Meals collected', report.collected, '#22d3ee'],
                                ['Meals missed', report.missed, '#f87171'],
                                ['Collection rate', `${report.collection_rate}%`, '#a78bfa'],
                            ].map(([label, value, accent]) => <div key={label} className="rounded-2xl border border-slate-700 border-t-4 bg-slate-800 p-5 shadow-lg" style={{ borderTopColor: accent }}><p className="text-sm font-semibold text-slate-300">{label}</p><p className="mt-2 text-3xl font-bold" style={{ color: accent }}>{value}</p></div>)}
                        </div>

                        <section className="flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-indigo-500/40 bg-slate-800 p-5 shadow-lg"><div><h3 className="text-lg font-bold text-white">Preparation overview</h3><p className="mt-1 text-sm text-slate-400">Next week: <span className="font-bold text-indigo-300">{report.next_week_preparation.reduce((total, meal) => total + meal.total, 0)} portions</span> across {new Set(report.next_week_preparation.map((meal) => meal.school)).size} schools.</p></div><Link href={route('owner.preparation-overview')} className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">View Preparation Overview →</Link></section>

                        <div className="grid gap-6 lg:grid-cols-3">
                            <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg lg:col-span-2">
                                <h3 className="text-lg font-bold text-white">Revenue trend</h3>
                                <div className="mt-5 flex h-72 items-end gap-3 overflow-x-auto border-b border-slate-600 px-2 pb-1">
                                    {report.revenue_trend.map((point) => <div key={point.date} className="flex h-full min-w-16 flex-1 flex-col justify-end text-center"><span className="mb-2 whitespace-nowrap text-[11px] font-bold" style={{ color: '#6ee7b7' }}>{money(point.revenue)}</span><div className="mx-auto w-10 rounded-t-md" style={{ height: `${Math.max(5, Number(point.revenue) / maxRevenue * 205)}px`, minHeight: '5px', background: 'linear-gradient(to top, #059669, #34d399)' }} /><span className="mt-2 text-xs font-medium text-slate-300">{point.date.slice(5)}</span></div>)}
                                    {report.revenue_trend.length === 0 && <p className="m-auto text-sm text-slate-400">No revenue in this period.</p>}
                                </div>
                            </section>

                            <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
                                <h3 className="text-lg font-bold text-white">Collection status</h3>
                                <div className="mt-8 space-y-6">
                                    <div><div className="mb-2 flex justify-between text-sm font-semibold text-slate-200"><span>Collected</span><span style={{ color: '#34d399' }}>{report.collected}</span></div><div className="h-5 overflow-hidden rounded-full bg-slate-700"><div className="h-full rounded-full" style={{ width: `${collectionTotal ? report.collected / collectionTotal * 100 : 0}%`, backgroundColor: '#10b981' }} /></div></div>
                                    <div><div className="mb-2 flex justify-between text-sm font-semibold text-slate-200"><span>Missed</span><span style={{ color: '#f87171' }}>{report.missed}</span></div><div className="h-5 overflow-hidden rounded-full bg-slate-700"><div className="h-full rounded-full" style={{ width: `${collectionTotal ? report.missed / collectionTotal * 100 : 0}%`, backgroundColor: '#ef4444' }} /></div></div>
                                    <p className="rounded-xl bg-slate-700 p-4 text-center text-2xl font-bold text-white"><span className="text-violet-400">{report.collection_rate}%</span> collected</p>
                                </div>
                            </section>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2"><MealTable title="Famous meals" rows={famousMeals} /><MealTable title="Less-famous meals" rows={lessFamousMeals} /></div>

                        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
                            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Still-hungry reports</h3>
                            <div className="max-h-[420px] overflow-auto"><table className="w-full"><thead className="sticky top-0 z-10 bg-amber-950 text-xs uppercase tracking-wide text-amber-300"><tr><th className="px-5 py-3 text-left">Meal</th><th className="px-5 py-3 text-right">Reports</th></tr></thead><tbody className="divide-y divide-slate-700">{report.still_hungry.map((meal) => <tr key={meal.id} className="bg-slate-800 text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-5 py-4 font-semibold text-white">{meal.name}</td><td className="px-5 py-4 text-right font-bold text-amber-400">{meal.reports}</td></tr>)}{report.still_hungry.length === 0 && <tr><td colSpan="2" className="px-4 py-10 text-center text-sm text-slate-400">No still-hungry feedback in this period.</td></tr>}</tbody></table></div>
                        </section>
                    </>}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
