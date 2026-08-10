import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { useEffect, useState } from 'react';

export default function ScannerTest() {
    const [scanResult, setScanResult] = useState(null);
    const [scannerKey, setScannerKey] = useState(0);

    useEffect(() => {
        if (scanResult) return undefined;

        const scanner = new Html5QrcodeScanner(
            'qr-reader',
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                rememberLastUsedCamera: true,
            },
            false,
        );

        scanner.render(
            (decodedText) => {
                scanner.clear()
                    .catch(() => {})
                    .finally(() => setScanResult(decodedText));
            },
            () => {},
        );

        return () => {
            scanner.clear().catch(() => {});
        };
    }, [scanResult, scannerKey]);

    const scanAgain = () => {
        setScanResult(null);
        setScannerKey((current) => current + 1);
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-gray-100">QR Scanner Test</h2>}
        >
            <Head title="QR Scanner Test" />
            <div className="py-12">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">QR Code Scanner</h1>
                        <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">
                            Allow camera access, select the rear camera on a phone, and scan a student QR code.
                        </p>

                        {!scanResult && (
                            <div key={scannerKey} id="qr-reader" className="mt-6 overflow-hidden rounded-lg bg-white text-slate-900" />
                        )}

                        {scanResult && (
                            <div className="mt-6 rounded-lg border border-emerald-300 bg-emerald-50 p-5">
                                <p className="font-semibold text-emerald-800">QR code scanned successfully</p>
                                <p className="mt-2 break-all rounded bg-white p-3 font-mono text-sm text-slate-800">{scanResult}</p>
                                <button type="button" onClick={scanAgain} className="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Scan Another QR
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
