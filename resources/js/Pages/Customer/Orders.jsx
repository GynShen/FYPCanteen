import Modal from '@/Components/Modal';
import TablePagination from '@/Components/TablePagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import useTablePagination from '@/Hooks/useTablePagination';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const dayName = (day) => day.charAt(0).toUpperCase() + day.slice(1);
const dayForDate = (value) => {
    const day = new Date(value).getDay();
    return weekDays[day === 0 ? 6 : day - 1];
};
const paymentBadge = {
    paid: { backgroundColor: '#d1fae5', borderColor: '#6ee7b7', color: '#065f46' },
    pending: { backgroundColor: '#fef3c7', borderColor: '#fcd34d', color: '#92400e' },
    failed: { backgroundColor: '#fee2e2', borderColor: '#fca5a5', color: '#991b1b' },
    cancelled: { backgroundColor: '#fee2e2', borderColor: '#fca5a5', color: '#991b1b' },
};
const defaultPaymentBadge = { backgroundColor: '#e2e8f0', borderColor: '#94a3b8', color: '#334155' };

export default function Orders({ orders, menuItems }) {
    const errors = usePage().props.errors;
    const [editingItem, setEditingItem] = useState(null);
    const [replacementId, setReplacementId] = useState('');
    const [processing, setProcessing] = useState(false);
    const [expandedOrders, setExpandedOrders] = useState({});
    const { currentPage, setCurrentPage, paginatedItems } =
        useTablePagination(orders);

    const replacements = useMemo(() => {
        if (!editingItem) return [];
        const weekday = dayForDate(editingItem.scheduled_time);
        return menuItems.filter(
            (meal) =>
                Number(meal.price) === Number(editingItem.unit_price) &&
                meal.schools.some((school) => school.id === editingItem.student.school_id) &&
                meal.days.some((day) => day.day_of_week === weekday),
        );
    }, [editingItem, menuItems]);

    const openEdit = (item) => {
        if (!item.can_edit) return;
        setEditingItem(item);
        setReplacementId(String(item.menu_item_id));
    };
    const closeEdit = () => {
        setEditingItem(null);
        setReplacementId('');
    };
    const updateMeal = () => {
        router.patch(
            route('customer.orders.items.update', { orderItem: editingItem.id }),
            { menu_item_id: Number(replacementId) },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onSuccess: closeEdit,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">Ordered Meal Timetable</h2>}>
            <Head title="Orders" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800">
                        Meal changes are allowed until Saturday. Click an ordered meal to replace it with another same-price meal for that child and weekday.
                    </div>

                    {paginatedItems.map((order) => {
                        const paymentStatus = order.payment?.status ?? 'unpaid';
                        const expanded = expandedOrders[order.id] ?? false;
                        const childGroups = [...new Map(order.order_items.map((item) => [item.student_id, item.student])).entries()];

                        return (
                            <section key={order.id} className="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-lg">
                                <button
                                    type="button"
                                    onClick={() => setExpandedOrders((current) => ({ ...current, [order.id]: !expanded }))}
                                    className="flex w-full flex-col justify-between gap-4 border-b border-slate-300 bg-slate-100 p-5 text-left transition-colors hover:bg-slate-200 sm:flex-row sm:items-center"
                                    aria-expanded={expanded}
                                >
                                    <div><h3 className="text-lg font-bold text-slate-900">Order #{order.id}</h3><p className="mt-1 text-sm text-slate-600">{new Date(order.created_at).toLocaleString('en-MY')}</p></div>
                                    <div className="flex items-center gap-4 text-left sm:text-right">
                                        <div>
                                        <p className="text-2xl font-bold text-slate-900">RM {Number(order.total_amount).toFixed(2)}</p>
                                        <div className="mt-2 flex flex-wrap items-center gap-2 sm:justify-end">
                                            <span className="rounded-full border px-3 py-1 text-xs font-bold capitalize" style={paymentBadge[paymentStatus] ?? defaultPaymentBadge}>Payment: {paymentStatus}</span>
                                            <span className="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold capitalize text-slate-700">{order.payment?.method ?? 'No payment method'}</span>
                                        </div>
                                        </div>
                                        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-300 text-xl font-bold text-slate-700">{expanded ? '−' : '+'}</span>
                                    </div>
                                </button>
                                {expanded && <div className="space-y-5 p-5">
                                    {childGroups.map(([studentId, student], childIndex) => {
                                        const items = order.order_items.filter((item) => item.student_id === studentId);
                                        return (
                                            <div
                                                key={studentId}
                                                className="overflow-hidden rounded-lg border border-slate-300 shadow-sm"
                                                style={{ marginTop: childIndex === 0 ? 0 : '24px' }}
                                            >
                                                <div
                                                    className="flex items-center justify-between border-b border-slate-300 px-4 py-3"
                                                    style={{ backgroundColor: '#e2e8f0', color: '#0f172a' }}
                                                >
                                                    <div><h4 className="font-bold" style={{ color: '#0f172a' }}>{student.name}</h4><p className="text-xs font-medium" style={{ color: '#475569' }}>{student.school?.name ?? '-'}</p></div>
                                                    <span className="text-xs font-semibold" style={{ color: '#334155' }}>{items.length} meal selection(s)</span>
                                                </div>
                                                <div className="overflow-x-auto">
                                                    <table className="w-full min-w-[900px] table-fixed border-collapse">
                                                        <thead><tr>{weekDays.map((day) => <th key={day} className="border border-slate-300 bg-slate-200 px-2 py-3 text-center text-xs font-bold uppercase text-slate-800">{dayName(day)}</th>)}</tr></thead>
                                                        <tbody><tr>{weekDays.map((day) => {
                                                            const dayItems = items.filter((item) => dayForDate(item.scheduled_time) === day);
                                                            return (
                                                                <td key={day} className={`h-32 border border-slate-300 p-2 align-top ${dayItems.length ? 'bg-emerald-50' : 'bg-slate-50'}`}>
                                                                    {dayItems.map((item) => (
                                                                        <button key={item.id} type="button" disabled={!item.can_edit} onClick={() => openEdit(item)} className={`mb-2 block w-full rounded-md border p-2 text-left ${item.can_edit ? 'border-emerald-300 bg-white hover:border-indigo-500 hover:shadow' : 'cursor-default border-slate-300 bg-white'}`}>
                                                                            <span className="block text-sm font-bold text-indigo-700">{item.menu_item.name}</span>
                                                                            <span className="mt-1 block text-xs text-slate-600">{new Date(item.scheduled_time).toLocaleDateString('en-MY')}</span>
                                                                            <span className="block text-xs font-semibold text-slate-700">Quantity: {item.quantity}</span>
                                                                            {item.deadline_locked && (
                                                                                <span className="mt-2 block rounded border border-amber-300 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800">
                                                                                    Saturday deadline passed. This meal cannot be changed.
                                                                                </span>
                                                                            )}
                                                                        </button>
                                                                    ))}
                                                                    {dayItems.length === 0 && <span className="flex h-full items-center justify-center text-xs font-medium text-slate-400">No meal ordered</span>}
                                                                </td>
                                                            );
                                                        })}</tr></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>}
                            </section>
                        );
                    })}
                    <TablePagination totalItems={orders.length} currentPage={currentPage} onPageChange={setCurrentPage} />

                    {orders.length === 0 && <div className="rounded-xl bg-white p-10 text-center text-slate-600 shadow">You have not placed any orders yet.</div>}
                </div>
            </div>

            <Modal show={editingItem !== null} maxWidth="lg" onClose={closeEdit}>
                {editingItem && (
                    <div className="bg-white p-6 text-slate-900">
                        <h3 className="text-xl font-bold text-slate-900">Change Ordered Meal</h3>
                        <p className="mt-1 text-sm font-medium text-slate-600">{editingItem.student.name} · {new Date(editingItem.scheduled_time).toLocaleDateString('en-MY')} · Quantity {editingItem.quantity}</p>
                        <div className="mt-5">
                            <label htmlFor="replacement-meal" className="block text-sm font-semibold text-slate-700">Replacement meal</label>
                            <select id="replacement-meal" value={replacementId} onChange={(event) => setReplacementId(event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-slate-800">
                                {replacements.map((meal) => <option key={meal.id} value={meal.id}>{meal.name} — RM {Number(meal.price).toFixed(2)}</option>)}
                            </select>
                            {errors.menu_item_id && <p className="mt-2 text-sm text-red-600">{errors.menu_item_id}</p>}
                            {errors.order_item && <p className="mt-2 text-sm text-red-600">{errors.order_item}</p>}
                            <p className="mt-3 rounded-md border border-blue-200 bg-blue-50 p-3 text-xs font-medium text-blue-900">The child, collection date, quantity, and paid total remain unchanged. No payment or refund is created.</p>
                        </div>
                        <div className="mt-6 flex justify-end gap-3"><button type="button" onClick={closeEdit} className="rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button><button type="button" onClick={updateMeal} disabled={processing || !replacementId} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">{processing ? 'Saving...' : 'Change Meal'}</button></div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
