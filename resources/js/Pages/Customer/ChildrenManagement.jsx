import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import TablePagination from '@/Components/TablePagination';
import useTablePagination from '@/Hooks/useTablePagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function ChildrenManagement({ children }) {
    const [editingId, setEditingId] = useState(null);
    const { currentPage, setCurrentPage, paginatedItems } =
        useTablePagination(children);

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
        student_id: '',
    });

    const clearForm = () => {
        setEditingId(null);
        clearErrors();
        reset();
    };

    const startEdit = (child) => {
        clearErrors();
        setEditingId(child.id);
        setData('student_id', child.student_id);
    };

    const submit = (event) => {
        event.preventDefault();
        clearErrors();

        if (editingId) {
            patch(route('customer.children.update', { child: editingId }), {
                preserveScroll: true,
                onSuccess: clearForm,
            });
            return;
        }

        post(route('customer.children.store'), {
            preserveScroll: true,
            onSuccess: clearForm,
        });
    };

    const remove = (child) => {
        if (
            !window.confirm(
                `Remove ${child.student.name} from your account? The student record itself will not be deleted.`,
            )
        ) {
            return;
        }

        destroy(route('customer.children.destroy', { child: child.id }), {
            preserveScroll: true,
            onSuccess: () => {
                if (editingId === child.id) {
                    clearForm();
                }
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Children Management
                </h2>
            }
        >
            <Head title="Children Management" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {editingId ? 'Change Linked Student' : 'Add Child'}
                        </h3>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Enter the Student ID supplied by the school. You can
                            only add a student who already exists and is not
                            linked to another customer.
                        </p>

                        {editingId && (
                            <p className="mt-4 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                Editing child link ID: {editingId}
                            </p>
                        )}

                        <form onSubmit={submit} className="mt-6 max-w-xl">
                            <InputLabel
                                htmlFor="student_id"
                                value="Student ID"
                            />
                            <TextInput
                                id="student_id"
                                type="number"
                                min="1"
                                step="1"
                                value={data.student_id}
                                onChange={(event) =>
                                    setData('student_id', event.target.value)
                                }
                                className="mt-1 block w-full"
                                placeholder="Enter the student's ID"
                                required
                            />
                            <InputError
                                message={errors.student_id}
                                className="mt-2"
                            />

                            <div className="mt-5 flex gap-3">
                                <PrimaryButton disabled={processing}>
                                    {processing
                                        ? 'Saving...'
                                        : editingId
                                          ? 'Update Child'
                                          : 'Add Child'}
                                </PrimaryButton>

                                {editingId && (
                                    <button
                                        type="button"
                                        onClick={clearForm}
                                        className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300"
                                    >
                                        Cancel
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>

                    <div className="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-slate-100">
                                    <tr>
                                        {[
                                            'Student ID',
                                            'Name',
                                            'Class',
                                            'School',
                                            'Operation',
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
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {paginatedItems.map((child) => (
                                        <tr key={child.id}>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {child.student.id}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {child.student.name}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {child.student.class}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {child.student.school?.name ??
                                                    '-'}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <div className="flex justify-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            startEdit(child)
                                                        }
                                                        className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            remove(child)
                                                        }
                                                        className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}

                                    {children.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan="5"
                                                className="px-4 py-10 text-center text-sm text-gray-500"
                                            >
                                                No children are linked to your
                                                account yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <TablePagination totalItems={children.length} currentPage={currentPage} onPageChange={setCurrentPage} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
