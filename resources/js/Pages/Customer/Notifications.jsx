import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

const styles = {
    payment_completed: 'border-emerald-300 bg-emerald-50',
    meal_received: 'border-blue-300 bg-blue-50',
    meal_missed: 'border-red-300 bg-red-50',
    still_hungry: 'border-amber-300 bg-amber-50',
};

export default function Notifications({ notifications }) {
    const markRead = (notification) => {
        if (!notification.read_at) router.patch(route('customer.notifications.read', notification.id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-100">Notifications</h2>}>
            <Head title="Notifications" />
            <div className="py-10">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-5 flex items-center justify-between">
                        <p className="text-sm text-slate-300">Payment, collection and meal feedback updates.</p>
                        <button type="button" onClick={() => router.patch(route('customer.notifications.read-all'), {}, { preserveScroll: true })} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Mark all as read</button>
                    </div>

                    {notifications.data.length === 0 ? (
                        <div className="rounded-xl bg-white p-10 text-center text-slate-600 shadow">No notifications yet.</div>
                    ) : (
                        <div className="space-y-3">
                            {notifications.data.map((notification) => (
                                <button key={notification.id} type="button" onClick={() => markRead(notification)} className={`block w-full rounded-xl border p-5 text-left shadow-sm ${styles[notification.type] || 'border-slate-300 bg-white'} ${notification.read_at ? 'opacity-70' : 'ring-2 ring-indigo-400'}`}>
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="font-bold text-slate-900">{notification.title}</p>
                                            <p className="mt-1 text-sm text-slate-700">{notification.message}</p>
                                            <p className="mt-2 text-xs text-slate-500">{new Date(notification.created_at).toLocaleString()}</p>
                                        </div>
                                        {!notification.read_at && <span className="rounded-full bg-indigo-600 px-2.5 py-1 text-xs font-bold text-white">New</span>}
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    {notifications.links?.length > 3 && (
                        <div className="mt-6 flex flex-wrap justify-center gap-2">
                            {notifications.links.map((link, index) => <button key={index} type="button" disabled={!link.url} onClick={() => link.url && router.get(link.url)} className={`rounded px-3 py-2 text-sm ${link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700'} disabled:opacity-40`} dangerouslySetInnerHTML={{ __html: link.label }} />)}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
