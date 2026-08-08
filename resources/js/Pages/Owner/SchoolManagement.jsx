import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function SchoolManagement({ schools }) {
    const [editingId, setEditingId] = useState(null);

    const {
        data,
        setData,
        post,
        patch,
        delete: destroy,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        name: '',
        address: '',
    });

    const isEditingDeletedSchool =
        editingId !== null &&
        !schools.some((school) => school.id === editingId);

    const startEdit = (school) => {
        clearErrors();
        setEditingId(school.id);
        setData({
            name: school.name,
            address: school.address,
        });
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

    const remove = (id) => {
        const confirmed = window.confirm(
            'Are you sure you want to delete this school?',
        );

        if (!confirmed) {
            return;
        }

        destroy(route('owner.school.destroy', { school: id }), {
            preserveScroll: true,
        });
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
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
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

                                {isEditingDeletedSchool && (
                                    <p className="text-sm font-medium text-red-600">
                                        This school has been deleted or no
                                        longer exists.
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
                                            Operation
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {schools.map((school) => (
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
                                                        onClick={() =>
                                                            remove(school.id)
                                                        }
                                                        className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
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
