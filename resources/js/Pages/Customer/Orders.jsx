import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Orders({ orders }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Orders
                </h2>
            }
        >
            <Head title="Orders" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    {orders.map((order) => (
                        <section
                            key={order.id}
                            className="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800"
                        >
                            <div className="flex flex-col justify-between gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-center">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        Order #{order.id}
                                    </h3>
                                    <p className="text-sm text-gray-500">
                                        {new Date(
                                            order.created_at,
                                        ).toLocaleString('en-MY')}
                                    </p>
                                </div>
                                <div className="text-left sm:text-right">
                                    <p className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                        RM{' '}
                                        {Number(order.total_amount).toFixed(2)}
                                    </p>
                                    <p className="text-sm capitalize text-green-700">
                                        {order.order_status} ·{' '}
                                        {order.payment?.method ?? 'No payment'}
                                    </p>
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-slate-100">
                                        <tr>
                                            {[
                                                'Child',
                                                'School',
                                                'Meal',
                                                'Collection Date',
                                                'Unit Price',
                                                'Quantity',
                                                'Subtotal',
                                            ].map((heading) => (
                                                <th
                                                    key={heading}
                                                    className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700"
                                                >
                                                    {heading}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {order.order_items.map((item) => (
                                            <tr key={item.id}>
                                                <td className="px-4 py-3 text-center text-sm">
                                                    {item.student.name}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm">
                                                    {item.student.school
                                                        ?.name ?? '-'}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm">
                                                    {item.menu_item.name}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm">
                                                    {new Date(
                                                        item.scheduled_time,
                                                    ).toLocaleDateString(
                                                        'en-MY',
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm">
                                                    RM{' '}
                                                    {Number(
                                                        item.unit_price,
                                                    ).toFixed(2)}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm">
                                                    {item.quantity}
                                                </td>
                                                <td className="px-4 py-3 text-center text-sm font-medium">
                                                    RM{' '}
                                                    {(
                                                        Number(
                                                            item.unit_price,
                                                        ) * item.quantity
                                                    ).toFixed(2)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    ))}

                    {orders.length === 0 && (
                        <div className="rounded-lg bg-white p-10 text-center text-gray-500 shadow dark:bg-gray-800">
                            You have not placed any orders yet.
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
