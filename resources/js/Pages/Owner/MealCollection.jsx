import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { useEffect, useState } from 'react';

const statusStyles = {
    open: 'bg-blue-100 text-blue-800',
    upcoming: 'bg-amber-100 text-amber-800',
    partially_collected: 'bg-orange-100 text-orange-800',
    grace_period: 'bg-amber-100 text-amber-800',
    late_partially_collected: 'bg-orange-100 text-orange-800',
    late_collected: 'bg-purple-100 text-purple-800',
    collected: 'bg-emerald-100 text-emerald-800',
    missed: 'bg-red-100 text-red-800',
};

const statusLabels = {
    open: 'Ready to collect',
    upcoming: 'Upcoming',
    partially_collected: 'Partially collected',
    grace_period: '15-minute grace period',
    late_partially_collected: 'Late · partially collected',
    late_collected: 'Late collected',
    collected: 'Collected',
    missed: 'Missed',
};

export default function MealCollection() {
    const [result, setResult] = useState(null);
    const [scannerKey, setScannerKey] = useState(0);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [quantities, setQuantities] = useState({});
    const [lateReasons, setLateReasons] = useState({});

    const lookupStudent = async (qrCode) => {
        setLoading(true);
        setError('');
        setSuccess('');

        try {
            const response = await axios.post(route('meal-collection.scan'), { qr_code: qrCode });
            setResult(response.data);
            setQuantities(Object.fromEntries(response.data.items.map((item) => [item.id, 1])));
            setLateReasons({});
        } catch (requestError) {
            setError(requestError.response?.data?.errors?.qr_code?.[0] || requestError.response?.data?.message || 'The QR code could not be checked.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (result || loading) return undefined;

        const scanner = new Html5QrcodeScanner('qr-reader', {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true,
        }, false);

        scanner.render((decodedText) => {
            scanner.clear().catch(() => {}).finally(() => lookupStudent(decodedText));
        }, () => {});

        return () => { scanner.clear().catch(() => {}); };
    }, [result, scannerKey]);

    const scanAgain = () => {
        setResult(null);
        setError('');
        setSuccess('');
        setScannerKey((current) => current + 1);
    };

    const collect = async (item) => {
        setLoading(true);
        setError('');
        setSuccess('');

        try {
            const response = await axios.post(route('meal-collection.collect', item.id), {
                quantity: Number(quantities[item.id] || 1),
                late_reason: lateReasons[item.id] || null,
            });
            setResult((current) => ({
                ...current,
                items: current.items.map((currentItem) => currentItem.id === item.id ? response.data.item : currentItem),
            }));
            setQuantities((current) => ({ ...current, [item.id]: 1 }));
            setLateReasons((current) => ({ ...current, [item.id]: '' }));
            setSuccess(response.data.message);
        } catch (requestError) {
            const errors = requestError.response?.data?.errors;
            setError(errors?.late_reason?.[0] || errors?.quantity?.[0] || requestError.response?.data?.message || 'Collection could not be recorded.');
        } finally {
            setLoading(false);
        }
    };

    const reportStillHungry = async (item) => {
        if (!window.confirm(`Notify the parent that ${result.student.name} is still hungry after ${item.meal}?`)) return;
        setLoading(true);
        setError('');
        setSuccess('');

        try {
            const response = await axios.post(route('meal-collection.still-hungry', item.id));
            setResult((current) => ({ ...current, items: current.items.map((currentItem) => currentItem.id === item.id ? { ...currentItem, feedback_sent: true } : currentItem) }));
            setSuccess(response.data.message);
        } catch (requestError) {
            setError(requestError.response?.data?.errors?.feedback?.[0] || requestError.response?.data?.message || 'Feedback could not be sent.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-100">Meal Collection</h2>}>
            <Head title="Meal Collection" />
            <div className="py-6 sm:py-10">
                <div className="mx-auto max-w-3xl px-4 sm:px-6">
                    <div className="rounded-xl bg-white p-5 shadow dark:bg-slate-800 sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Scan Student QR</h1>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">Scan the student’s permanent QR code to find paid meals for today.</p>
                            </div>
                            {(result || error) && <button type="button" onClick={scanAgain} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Scan Another</button>}
                        </div>

                        {error && <div className="mt-5 rounded-lg border border-red-300 bg-red-50 p-4 text-sm font-medium text-red-800">{error}</div>}
                        {success && <div className="mt-5 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{success}</div>}
                        {loading && <div className="mt-5 rounded-lg bg-slate-100 p-4 text-center font-medium text-slate-700">Processing…</div>}

                        {!result && !loading && <div key={scannerKey} id="qr-reader" className="mt-6 overflow-hidden rounded-lg bg-white text-slate-900" />}

                        {result && (
                            <div className="mt-6">
                                <div className="rounded-lg border border-slate-300 bg-slate-50 p-4">
                                    <p className="text-lg font-bold text-slate-900">{result.student.name}</p>
                                    <p className="mt-1 text-sm text-slate-700">{result.student.student_code} · Class {result.student.class}</p>
                                    <p className="text-sm text-slate-700">{result.student.school} · {result.date}</p>
                                </div>

                                {result.items.length === 0 ? (
                                    <div className="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-5 text-center text-amber-900">No paid meals are scheduled for this student today.</div>
                                ) : (
                                    <div className="mt-4 space-y-4">
                                        {result.items.map((item) => (
                                            <div key={item.id} className="rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
                                                <div className="flex gap-3">
                                                    {item.image_url && <img src={item.image_url} alt="" className="h-20 w-20 rounded-lg object-cover" />}
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                                            <h3 className="text-lg font-bold text-slate-900">{item.meal}</h3>
                                                            <span className={`rounded-full px-3 py-1 text-xs font-bold ${statusStyles[item.status]}`}>{statusLabels[item.status]}</span>
                                                        </div>
                                                        <p className="mt-1 text-sm text-slate-700">Size: {item.size || 'M'}</p>
                                                        <p className="text-sm text-slate-700">Collection: {item.start_time && item.end_time ? `${item.start_time} – ${item.end_time}` : 'Any time today'}</p>
                                                        <p className="mt-1 text-sm font-semibold text-slate-900">Collected {item.collected_quantity} of {item.ordered_quantity} · Remaining {item.remaining_quantity}</p>
                                                        {item.late_reason && <p className="mt-1 text-sm text-purple-700">Late reason: {item.late_reason}</p>}
                                                    </div>
                                                </div>

                                                {item.can_collect && (
                                                    <div className="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:flex-wrap sm:items-end">
                                                        <label className="flex-1 text-sm font-semibold text-slate-800">Quantity to collect
                                                            <input type="number" min="1" max={item.remaining_quantity} value={quantities[item.id] || 1} onChange={(event) => setQuantities((current) => ({ ...current, [item.id]: event.target.value }))} className="mt-1 block w-full rounded-md border-slate-300 text-slate-900" />
                                                        </label>
                                                        {item.requires_late_reason && (
                                                            <label className="w-full text-sm font-semibold text-slate-800">Reason for late collection
                                                                <textarea required rows="2" value={lateReasons[item.id] || ''} onChange={(event) => setLateReasons((current) => ({ ...current, [item.id]: event.target.value }))} placeholder="Example: Student arrived 2 minutes late" className="mt-1 block w-full rounded-md border-slate-300 text-slate-900" />
                                                            </label>
                                                        )}
                                                        <button type="button" disabled={loading} onClick={() => collect(item)} className="rounded-md bg-emerald-600 px-5 py-2.5 font-bold text-white hover:bg-emerald-700 disabled:opacity-50">Confirm Collection</button>
                                                    </div>
                                                )}
                                                {(item.status === 'collected' || item.status === 'late_collected') && (
                                                    <div className="mt-4 border-t border-slate-200 pt-4">
                                                        <button type="button" disabled={loading || item.feedback_sent} onClick={() => reportStillHungry(item)} className="w-full rounded-md bg-amber-500 px-5 py-2.5 font-bold text-slate-900 hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">
                                                            {item.feedback_sent ? 'Still Hungry Feedback Sent' : 'Student Is Still Hungry'}
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
