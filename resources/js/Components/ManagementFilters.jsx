import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function ManagementFilters({ routeName, filters = {}, fields = [] }) {
    const [values, setValues] = useState(() => Object.fromEntries(fields.map((field) => [field.name, filters[field.name] ?? ''])));
    const firstRender = useRef(true);
    const searchFields = fields.filter((field) => field.type !== 'select');
    const filterFields = fields.filter((field) => field.type === 'select');

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return undefined;
        }

        const timer = window.setTimeout(() => {
            router.get(route(routeName), Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '')), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 500);

        return () => window.clearTimeout(timer);
    }, [values]);

    const change = (name, value) => setValues((current) => ({ ...current, [name]: value }));

    return (
        <div className="mb-6 rounded-lg border border-slate-700 bg-slate-800 p-4">
            <div className="space-y-4">
                {searchFields.map((field) => (
                    <label key={field.name} className="block text-sm font-semibold text-slate-200">{field.label}
                        <input value={values[field.name]} onChange={(event) => change(field.name, event.target.value)} type="search" placeholder={field.placeholder} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900" />
                    </label>
                ))}
                {filterFields.length > 0 && <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {filterFields.map((field) => (
                        <label key={field.name} className="text-sm font-semibold text-slate-200">{field.label}
                            <select value={values[field.name]} onChange={(event) => change(field.name, event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 bg-white text-slate-900">
                                <option value="">All</option>
                                {field.options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                            </select>
                        </label>
                    ))}
                </div>}
                <p className="text-xs text-slate-400">Results update automatically after 0.5 seconds.</p>
            </div>
        </div>
    );
}
