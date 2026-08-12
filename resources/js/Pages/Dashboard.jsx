import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const money = (value) => `RM ${Number(value).toFixed(2)}`;

function MealTable({ title, rows }) {
    return (
        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">{title}</h3>
            <table className="w-full">
                <thead className="bg-slate-700/80 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-5 py-3 text-left">Meal</th><th className="px-5 py-3 text-right">Quantity</th><th className="px-5 py-3 text-right">Revenue</th></tr></thead>
                <tbody className="divide-y divide-slate-700">
                    {rows.map((meal) => <tr key={meal.id} className="bg-slate-800 text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-5 py-4 font-semibold text-white">{meal.name}</td><td className="px-5 py-4 text-right">{meal.quantity}</td><td className="px-5 py-4 text-right font-semibold text-emerald-400">{money(meal.revenue)}</td></tr>)}
                    {rows.length === 0 && <tr><td colSpan="3" className="px-4 py-10 text-center text-sm text-slate-400">No paid meal orders in this period.</td></tr>}
                </tbody>
            </table>
        </section>
    );
}

function OperatorReport({ report, period, update }) {
    const [now, setNow] = useState(Date.now());

    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), 1000);
        return () => window.clearInterval(timer);
    }, []);

    const sessionState = (slot) => {
        const start = new Date(slot.session_start).getTime();
        const end = new Date(slot.session_end).getTime();
        const graceEnd = new Date(slot.grace_end).getTime();
        if (now < start) return { label: `Starts in ${formatDuration(start - now)}`, color: '#60a5fa' };
        if (now <= end) return { label: `Open · ${formatDuration(end - now)} remaining`, color: '#34d399' };
        if (now <= graceEnd) return { label: `Grace period · ${formatDuration(graceEnd - now)} remaining`, color: '#fbbf24' };
        return { label: 'Closed', color: '#f87171' };
    };

    const formatDuration = (milliseconds) => {
        const seconds = Math.max(0, Math.floor(milliseconds / 1000));
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainder = seconds % 60;
        return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m ${String(remainder).padStart(2, '0')}s`;
    };

    const cards = [
        ['Meals to collect', report.ordered, '#818cf8'],
        ['Collected', report.collected, '#34d399'],
        ['Still remaining', report.remaining, '#fbbf24'],
        ['Missed', report.missed, '#f87171'],
    ];

    return <>
        <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            <div className="grid items-end gap-4 sm:grid-cols-2">
                <div>
                    <h2 className="text-lg font-bold text-white">Collection workload</h2>
                    <p className="mt-1 text-sm text-slate-400">{report.school ? `${report.school.name} only` : 'No school has been assigned to this operator.'}</p>
                </div>
                <label className="text-sm font-semibold text-slate-200 sm:justify-self-end sm:w-64">Period
                    <select value={period} onChange={(event) => update('period', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900">
                        <option value="today">Today</option>
                        <option value="week">This week</option>
                    </select>
                </label>
            </div>
        </section>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {cards.map(([label, value, accent]) => <div key={label} className="rounded-2xl border border-slate-700 border-t-4 bg-slate-800 p-5 shadow-lg" style={{ borderTopColor: accent }}><p className="text-sm font-semibold text-slate-300">{label}</p><p className="mt-2 text-3xl font-bold" style={{ color: accent }}>{value}</p></div>)}
        </div>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Collection progress by time slot</h3>
            <div className="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                {report.time_slots.map((slot) => {
                    const state = sessionState(slot);
                    const progress = slot.ordered ? Math.min(100, slot.collected / slot.ordered * 100) : 0;
                    return <article key={slot.key} className="rounded-xl border border-slate-600 bg-slate-900/40 p-4">
                        <div className="flex items-start justify-between gap-3"><div><p className="font-bold text-white">{slot.day}, {slot.date}</p><p className="mt-1 text-sm text-slate-300">{slot.start_time} – {slot.end_time}</p></div><span className="rounded-full px-3 py-1 text-xs font-bold" style={{ color: state.color, backgroundColor: `${state.color}20`, border: `1px solid ${state.color}` }}>{state.label}</span></div>
                        <div className="mt-4 flex justify-between text-sm text-slate-200"><span>{slot.collected} / {slot.ordered} collected</span><span>{Math.round(progress)}%</span></div>
                        <div className="mt-2 h-3 overflow-hidden rounded-full bg-slate-700"><div className="h-full rounded-full" style={{ width: `${progress}%`, backgroundColor: '#10b981' }} /></div>
                        <div className="mt-3 flex gap-4 text-xs text-slate-400"><span>{slot.students} students</span><span>{slot.meals} meal types</span><span className="text-red-400">{slot.missed} missed</span></div>
                    </article>;
                })}
                {report.time_slots.length === 0 && <p className="py-6 text-center text-sm text-slate-400 md:col-span-2 xl:col-span-3">No collection sessions in this period.</p>}
            </div>
        </section>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Meal preparation by size</h3>
            <div className="overflow-x-auto"><table className="w-full min-w-[750px]">
                <thead className="bg-slate-700 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-5 py-3 text-left">Date</th><th className="px-5 py-3 text-left">Collection time</th><th className="px-5 py-3 text-left">Meal</th><th className="px-5 py-3 text-right">Small</th><th className="px-5 py-3 text-right">Medium</th><th className="px-5 py-3 text-right">Large</th><th className="px-5 py-3 text-right">Total</th></tr></thead>
                <tbody className="divide-y divide-slate-700">{report.size_preparation.map((meal) => <tr key={meal.key} className="text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-5 py-4">{meal.day}<br/><span className="text-xs text-slate-400">{meal.date}</span></td><td className="px-5 py-4">{meal.time}</td><td className="px-5 py-4 font-semibold text-white">{meal.meal_name}</td><td className="px-5 py-4 text-right">{meal.S}</td><td className="px-5 py-4 text-right">{meal.M}</td><td className="px-5 py-4 text-right">{meal.L}</td><td className="px-5 py-4 text-right font-bold text-indigo-300">{meal.total}</td></tr>)}{report.size_preparation.length === 0 && <tr><td colSpan="7" className="px-5 py-10 text-center text-sm text-slate-400">No meals need preparation in this period.</td></tr>}</tbody>
            </table></div>
        </section>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Daily collection summary</h3>
            <div className="overflow-x-auto"><table className="w-full min-w-[700px]">
                <thead className="bg-slate-700 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-5 py-3 text-left">Day</th><th className="px-5 py-3 text-left">Date</th><th className="px-5 py-3 text-right">Required</th><th className="px-5 py-3 text-right">Collected</th><th className="px-5 py-3 text-right">Remaining</th><th className="px-5 py-3 text-right">Missed</th></tr></thead>
                <tbody className="divide-y divide-slate-700">{report.days.map((day) => <tr key={day.date} className="text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-5 py-4 font-semibold text-white">{day.day}</td><td className="px-5 py-4">{day.date}</td><td className="px-5 py-4 text-right">{day.ordered}</td><td className="px-5 py-4 text-right text-emerald-400">{day.collected}</td><td className="px-5 py-4 text-right text-amber-400">{day.remaining}</td><td className="px-5 py-4 text-right text-red-400">{day.missed}</td></tr>)}{report.days.length === 0 && <tr><td colSpan="6" className="px-5 py-10 text-center text-sm text-slate-400">No paid meals require collection in this period.</td></tr>}</tbody>
            </table></div>
        </section>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Meals to prepare and collect</h3>
            <div className="overflow-x-auto"><table className="w-full min-w-[850px]">
                <thead className="bg-slate-700 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-4 py-3 text-left">Date</th><th className="px-4 py-3 text-left">Time</th><th className="px-4 py-3 text-left">Meal</th><th className="px-4 py-3 text-right">Students</th><th className="px-4 py-3 text-right">Required</th><th className="px-4 py-3 text-right">Collected</th><th className="px-4 py-3 text-right">Remaining</th><th className="px-4 py-3 text-right">Missed</th></tr></thead>
                <tbody className="divide-y divide-slate-700">{report.meal_workload.map((meal) => <tr key={meal.key} className="text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-4 py-4">{meal.day}<br/><span className="text-xs text-slate-400">{meal.date}</span></td><td className="px-4 py-4">{meal.time}</td><td className="px-4 py-4 font-semibold text-white">{meal.meal_name}</td><td className="px-4 py-4 text-right">{meal.students}</td><td className="px-4 py-4 text-right">{meal.ordered}</td><td className="px-4 py-4 text-right text-emerald-400">{meal.collected}</td><td className="px-4 py-4 text-right text-amber-400">{meal.remaining}</td><td className="px-4 py-4 text-right text-red-400">{meal.missed}</td></tr>)}{report.meal_workload.length === 0 && <tr><td colSpan="8" className="px-5 py-10 text-center text-sm text-slate-400">No meal workload in this period.</td></tr>}</tbody>
            </table></div>
        </section>

        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Collection by student</h3>
            <div className="max-h-[560px] overflow-auto"><table className="w-full min-w-[950px]">
                <thead className="sticky top-0 bg-slate-700 text-xs uppercase tracking-wide text-slate-200"><tr><th className="px-4 py-3 text-left">Date / Time</th><th className="px-4 py-3 text-left">Student</th><th className="px-4 py-3 text-left">Class</th><th className="px-4 py-3 text-left">Meal</th><th className="px-4 py-3 text-right">Required</th><th className="px-4 py-3 text-right">Collected</th><th className="px-4 py-3 text-right">Remaining</th><th className="px-4 py-3 text-right">Missed</th></tr></thead>
                <tbody className="divide-y divide-slate-700">{report.student_workload.map((item) => <tr key={item.id} className="text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-4 py-4">{item.date}<br/><span className="text-xs text-slate-400">{item.time}</span></td><td className="px-4 py-4 font-semibold text-white">{item.student_name}<br/><span className="text-xs font-normal text-slate-400">{item.student_code}</span></td><td className="px-4 py-4">{item.class}</td><td className="px-4 py-4">{item.meal_name}</td><td className="px-4 py-4 text-right">{item.ordered}</td><td className="px-4 py-4 text-right text-emerald-400">{item.collected}</td><td className="px-4 py-4 text-right text-amber-400">{item.remaining}</td><td className="px-4 py-4 text-right text-red-400">{item.missed}</td></tr>)}{report.student_workload.length === 0 && <tr><td colSpan="8" className="px-5 py-10 text-center text-sm text-slate-400">No students require meal collection in this period.</td></tr>}</tbody>
            </table></div>
        </section>
    </>;
}

export default function Dashboard({ report, schools = [], filters = {} }) {
    const user = usePage().props.auth.user;
    const [form, setForm] = useState({
        period: filters.period ?? 'month',
        school_id: filters.school_id ?? '',
        start_date: filters.start_date ?? '',
        end_date: filters.end_date ?? '',
    });

    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));

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
    }, [form.period, form.school_id, form.start_date, form.end_date]);

    const isOwnerReport = report?.type === 'owner';
    const maxRevenue = isOwnerReport ? Math.max(1, ...report.revenue_trend.map((point) => Number(point.revenue))) : 1;
    const collectionTotal = isOwnerReport ? report.collected + report.missed : 0;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard</h2>}>
            <Head title="Dashboard" />
            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Welcome, {user.name}!</h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{report ? 'Review canteen performance and meal activity.' : 'You are logged in to FYPCanteen.'}</p>
                    </div>

                    {report?.type === 'operator' && <OperatorReport report={report} period={form.period} update={update} />}

                    {isOwnerReport && <>
                        <section className="rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-sm">
                            <div className="mb-4">
                                <h2 className="text-lg font-bold text-white">Report filters</h2>
                                <p className="text-sm text-slate-400">The report refreshes automatically after you change a filter.</p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <label className="text-sm font-semibold text-slate-200">Period<select value={form.period} onChange={(event) => update('period', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900"><option value="today">Today</option><option value="week">This week</option><option value="month">This month</option><option value="custom">Custom dates</option></select></label>
                                <label className="text-sm font-semibold text-slate-200">School<select value={form.school_id} onChange={(event) => update('school_id', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900"><option value="">All schools</option>{schools.map((school) => <option key={school.id} value={school.id}>{school.name}</option>)}</select></label>
                                {form.period === 'custom' && <><label className="text-sm font-semibold text-slate-200">Start date<input type="date" value={form.start_date} onChange={(event) => update('start_date', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-slate-900" /></label><label className="text-sm font-semibold text-slate-200">End date<input type="date" value={form.end_date} onChange={(event) => update('end_date', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-slate-900" /></label></>}
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

                        <div className="grid gap-6 lg:grid-cols-2"><MealTable title="Most ordered meals" rows={report.most_ordered} /><MealTable title="Least ordered meals" rows={report.least_ordered} /></div>

                        <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
                            <h3 className="border-b border-slate-700 px-5 py-4 text-lg font-bold text-white">Still-hungry reports</h3>
                            <table className="w-full"><thead className="bg-amber-500/15 text-xs uppercase tracking-wide text-amber-300"><tr><th className="px-5 py-3 text-left">Meal</th><th className="px-5 py-3 text-right">Reports</th></tr></thead><tbody className="divide-y divide-slate-700">{report.still_hungry.map((meal) => <tr key={meal.id} className="bg-slate-800 text-sm text-slate-200 hover:bg-slate-700/50"><td className="px-5 py-4 font-semibold text-white">{meal.name}</td><td className="px-5 py-4 text-right font-bold text-amber-400">{meal.reports}</td></tr>)}{report.still_hungry.length === 0 && <tr><td colSpan="2" className="px-4 py-10 text-center text-sm text-slate-400">No still-hungry feedback in this period.</td></tr>}</tbody></table>
                        </section>
                    </>}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
