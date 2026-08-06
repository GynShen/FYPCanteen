import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const paymentBadge = {
    paid: { backgroundColor: '#d1fae5', borderColor: '#6ee7b7', color: '#065f46' },
    pending: { backgroundColor: '#fef3c7', borderColor: '#fcd34d', color: '#92400e' },
    failed: { backgroundColor: '#fee2e2', borderColor: '#fca5a5', color: '#991b1b' },
    cancelled: { backgroundColor: '#fee2e2', borderColor: '#fca5a5', color: '#991b1b' },
};
const defaultPaymentBadge = {
    backgroundColor: '#e2e8f0',
    borderColor: '#94a3b8',
    color: '#334155',
};

export default function Orders({ orders }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    Orders
                </h2>
            }
        >
            <Head title="Orders" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    {orders.map((order) => {
                        const paymentStatus = order.payment?.status ?? 'unpaid';

                        return (
                            <section
                                key={order.id}
                                className="overflow-hidden rounded-xl border border-slate-700 bg-white shadow-xl"
                            >
                                <div className="flex flex-col justify-between gap-4 border-b border-slate-300 bg-slate-100 p-5 sm:flex-row sm:items-center">
                                    <div>
                                        <h3 className="text-lg font-bold text-slate-900">
                                            Order #{order.id}
                                        </h3>
                                        <p className="mt-1 text-sm text-slate-600">
                                            {new Date(order.created_at).toLocaleString('en-MY')}
                                        </p>
                                    </div>

                                    <div className="text-left sm:text-right">
                                        <p className="text-2xl font-bold text-slate-900">
                                            RM {Number(order.total_amount).toFixed(2)}
                                        </p>
                                        <div className="mt-2 flex flex-wrap items-center gap-2 sm:justify-end">
                                            <span
                                                className="rounded-full border px-3 py-1 text-xs font-bold capitalize"
                                                style={paymentBadge[paymentStatus] ?? defaultPaymentBadge}
                                            >
                                                Payment: {paymentStatus}
                                            </span>
                                            <span className="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold capitalize text-slate-700">
                                                {order.payment?.method ?? 'No payment method'}
                                            </span>
                                            <span className="text-xs font-medium capitalize text-slate-600">
                                                Order: {order.order_status}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="min-w-full border-collapse bg-white">
                                        <thead>
                                            <tr className="bg-slate-200">
                                                {['Child', 'School', 'Meal', 'Collection Date', 'Unit Price', 'Quantity', 'Subtotal'].map((heading) => (
                                                    <th
                                                        key={heading}
                                                        className="border-b border-slate-300 px-4 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-800"
                                                    >
                                                        {heading}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {order.order_items.map((item, index) => (
                                                <tr
                                                    key={item.id}
                                                    className={`border-b border-slate-200 last:border-0 hover:bg-indigo-50 ${index % 2 === 0 ? 'bg-white' : 'bg-slate-50'}`}
                                                >
                                                    <td className="px-4 py-3 text-center text-sm font-medium text-slate-800">{item.student.name}</td>
                                                    <td className="px-4 py-3 text-center text-sm text-slate-700">{item.student.school?.name ?? '-'}</td>
                                                    <td className="px-4 py-3 text-center text-sm font-semibold text-indigo-700">{item.menu_item.name}</td>
                                                    <td className="px-4 py-3 text-center text-sm text-slate-700">{new Date(item.scheduled_time).toLocaleDateString('en-MY')}</td>
                                                    <td className="px-4 py-3 text-center text-sm text-slate-700">RM {Number(item.unit_price).toFixed(2)}</td>
                                                    <td className="px-4 py-3 text-center text-sm">
                                                        <span className="inline-flex min-w-8 justify-center rounded-md bg-slate-200 px-2 py-1 font-bold text-slate-800">{item.quantity}</span>
                                                    </td>
                                                    <td className="px-4 py-3 text-center text-sm font-bold text-slate-900">RM {(Number(item.unit_price) * item.quantity).toFixed(2)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        );
                    })}

                    {orders.length === 0 && (
                        <div className="rounded-xl bg-white p-10 text-center text-slate-600 shadow">
                            You have not placed any orders yet.
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
