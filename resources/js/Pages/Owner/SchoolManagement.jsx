import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import ManagementFilters from '@/Components/ManagementFilters';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import TablePagination from '@/Components/TablePagination';
import useTablePagination from '@/Hooks/useTablePagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function SchoolManagement({ schools, filters }) {
    const [editingId, setEditingId] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const { currentPage, setCurrentPage, paginatedItems } =
        useTablePagination(schools);

    const {
        data,
        setData,
        post,
        patch,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        name: '',
        address: '',
    });

    const startEdit = (school) => {
        setShowForm(true);
        clearErrors();
        setEditingId(school.id);
        setData({
            name: school.name,
            address: school.address,
        });
        window.setTimeout(() => document.getElementById('school-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    };

    const cancelEdit = () => {
        clearErrors();
        setEditingId(null);
        reset();
    };

    const clearField = (field) => {
        setData(field, '');
    };

    const submit = (e) => {
        e.preventDefault();
        clearErrors();

        if (editingId) {
            patch(route('owner.school.update', { school: editingId }), {
                preserveScroll: true,
                onSuccess: () => cancelEdit(),
                onError: () => {
                    cancelEdit();
                    window.alert(
                        'This school has been deleted or no longer exists. Please refresh the page.',
                    );
                },
            });

            return;
        }

        post(route('owner.school.store'), {
            preserveScroll: true,
            onSuccess: () => {
                clearErrors();
                reset();
            },
        });
    };

    const toggleStatus = (school) => {
        const action = school.is_active ? 'deactivate' : 'activate';

        if (!window.confirm(`Are you sure you want to ${action} ${school.name}?`)) {
            return;
        }

        router.patch(
            route('owner.school.status', { school: school.id }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    School Management
                </h2>
            }
        >
            <Head title="School Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <ManagementFilters routeName="owner.school" filters={filters} fields={[{ name: 'search', label: 'Search', placeholder: 'School name or address' }]} />
                    <button type="button" onClick={() => setShowForm((current) => !current)} className="flex w-full items-center justify-between rounded-lg bg-white px-6 py-4 text-left font-bold text-slate-900 shadow dark:bg-gray-800 dark:text-white">
                        <span>{editingId ? `Edit School #${editingId}` : 'Add School'}</span><span className="text-xl">{showForm ? '⌃' : '⌄'}</span>
                    </button>
                    <div id="school-form" className={`${showForm ? '!mt-0 block rounded-t-none' : 'hidden'} scroll-mt-6 bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800`}>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <InputLabel
                                    htmlFor="name"
                                    value="School Name"
                                    className="text-gray-800 dark:text-gray-200"
                                />
                                <div className="relative mt-1">
                                    <TextInput
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        className="mt-1 block w-full pr-10 text-gray-900"
                                        required
                                    />
                                    {data.name && (
                                        <button
                                            type="button"
                                            onClick={() => clearField('name')}
                                            className="absolute inset-y-0 right-2 flex items-center text-sm text-gray-500 hover:text-gray-700"
                                        >
                                            Clear
                                        </button>
                                    )}
                                </div>
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="address"
                                    value="Address"
                                    className="text-gray-800 dark:text-gray-200"
                                />
                                <div className="relative mt-1">
                                    <TextInput
                                        id="address"
                                        value={data.address}
                                        onChange={(e) =>
                                            setData('address', e.target.value)
                                        }
                                        className="mt-1 block w-full pr-10 text-gray-900"
                                        required
                                    />
                                    {data.address && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                clearField('address')
                                            }
                                            className="absolute inset-y-0 right-2 flex items-center text-sm text-gray-500 hover:text-gray-700"
                                        >
                                            Clear
                                        </button>
                                    )}
                                </div>
                                <InputError
                                    message={errors.address}
                                    className="mt-2"
                                />
                            </div>

                            <div className="space-y-3">
                                {editingId && (
                                    <p className="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        Editing School ID: {editingId}
                                    </p>
                                )}

                                <div className="flex items-center gap-4">
                                    <PrimaryButton disabled={processing}>
                                        {editingId
                                            ? 'Update School'
                                            : 'Add School'}
                                    </PrimaryButton>

                                    {editingId && (
                                        <button
                                            type="button"
                                            onClick={cancelEdit}
                                            className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-gray-600 text-white transition hover:bg-red-700"
                                        >
                                            Cancel
                                        </button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-left">
                                <thead className="bg-slate-100">
                                    <tr>
                                        <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            ID
                                        </th>
                                        <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            Address
                                        </th>
                                        <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            Created At
                                        </th>
                                        <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            Updated At
                                        </th>
                                        <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            Status
                                        </th>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            Operation
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {paginatedItems.map((school) => (
                                        <tr key={school.id}>
                                            <td className="px-6 py-4 text-center text-sm text-gray-900">
                                                {school.id}
                                            </td>
                                            <td className="px-6 py-4 text-center text-sm text-gray-900">
                                                {school.name}
                                            </td>
                                            <td className="px-6 py-4 text-center text-sm text-gray-900">
                                                {school.address}
                                            </td>
                                            <td className="px-6 py-4 text-center text-sm text-gray-900">
                                                {new Date(
                                                    school.created_at,
                                                ).toLocaleString('en-SG')}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${school.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`}>
                                                    {school.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-center text-sm text-gray-900">
                                                {new Date(
                                                    school.updated_at,
                                                ).toLocaleString('en-SG')}
                                            </td>
                      <td className="px-6 py-4 text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            startEdit(school)
                                                        }
                                                        className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleStatus(school)}
                                                        className={`rounded-md px-3 py-2 text-sm font-medium text-white transition ${school.is_active ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700'}`}
                                                    >
                                                        {school.is_active ? 'Deactivate' : 'Activate'}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <TablePagination totalItems={schools.length} currentPage={currentPage} onPageChange={setCurrentPage} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
