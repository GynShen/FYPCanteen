import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function SchoolManagement({ schools }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    School Management
                </h2>
            }
        >
            <Head title="School Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-left">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                                            Address
                                        </th>
                                        <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                                            Created At (DD/MM/YYYY, HH:mm:ss)
                                        </th>
                                        <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                                            Updated At (DD/MM/YYYY, HH:mm:ss)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {schools.map((school) => (
                                        <tr key={school.id}>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {school.name}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {school.address}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {new Date(school.created_at).toLocaleString('en-SG')}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {new Date(school.updated_at).toLocaleString('en-SG')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
