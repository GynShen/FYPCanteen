import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function StudentManagement({ students, schools }) {
    const [editingId, setEditingId] = useState(null);
    const [qrStudent, setQrStudent] = useState(null);

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
        school_id: schools[0]?.id ?? '',
        name: '',
        class: '',
    });

    const clearForm = () => {
        setEditingId(null);
        clearErrors();
        reset();
    };

    const startEdit = (student) => {
        clearErrors();
        setEditingId(student.id);
        setData({
            school_id: student.school_id,
            name: student.name,
            class: student.class,
        });
    };

    const submit = (event) => {
        event.preventDefault();
        clearErrors();

        if (editingId) {
            patch(route('operator.student.update', { student: editingId }), {
                preserveScroll: true,
                onSuccess: clearForm,
            });
            return;
        }

        post(route('operator.student.store'), {
            preserveScroll: true,
            onSuccess: clearForm,
        });
    };

    const remove = (student) => {
        if (
            !window.confirm(
                `Delete student ${student.name}? This action cannot be undone.`,
            )
        ) {
            return;
        }

        destroy(route('operator.student.destroy', { student: student.id }), {
            preserveScroll: true,
            onSuccess: () => {
                if (editingId === student.id) {
                    clearForm();
                }
                if (qrStudent?.id === student.id) {
                    setQrStudent(null);
                }
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Student Management
                </h2>
            }
        >
            <Head title="Student Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <h3 className="mb-6 text-lg font-medium text-gray-900 dark:text-gray-100">
                            {editingId ? 'Edit Student' : 'Add Student'}
                        </h3>

                        {editingId && (
                            <p className="mb-4 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                Editing student ID: {editingId}. The QR code
                                will remain unchanged.
                            </p>
                        )}

                        {schools.length === 0 && (
                            <p className="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                                Create a school before adding a student.
                            </p>
                        )}

                        <InputError message={errors.student} className="mb-4" />

                        <form
                            onSubmit={submit}
                            className="grid gap-5 md:grid-cols-3"
                        >
                            <div>
                                <InputLabel
                                    htmlFor="name"
                                    value="Student Name"
                                />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="class" value="Class" />
                                <TextInput
                                    id="class"
                                    value={data.class}
                                    onChange={(event) =>
                                        setData('class', event.target.value)
                                    }
                                    className="mt-1 block w-full"
                                    placeholder="Example: 4 Amanah"
                                    required
                                />
                                <InputError
                                    message={errors.class}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="school_id"
                                    value="School"
                                />
                                <select
                                    id="school_id"
                                    value={data.school_id}
                                    onChange={(event) =>
                                        setData('school_id', event.target.value)
                                    }
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select a school</option>
                                    {schools.map((school) => (
                                        <option
                                            key={school.id}
                                            value={school.id}
                                        >
                                            {school.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.school_id}
                                    className="mt-2"
                                />
                            </div>

                            <div className="flex gap-3 md:col-span-3">
                                <PrimaryButton
                                    disabled={
                                        processing || schools.length === 0
                                    }
                                >
                                    {processing
                                        ? 'Saving...'
                                        : editingId
                                          ? 'Update Student'
                                          : 'Add Student'}
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
                                            'ID',
                                            'Name',
                                            'Class',
                                            'School',
                                            'QR Code',
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
                                    {students.map((student) => (
                                        <tr key={student.id}>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {student.id}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {student.name}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {student.class}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {student.school?.name ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setQrStudent(student)
                                                    }
                                                    className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                                >
                                                    Show QR
                                                </button>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <div className="flex justify-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            startEdit(student)
                                                        }
                                                        className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            remove(student)
                                                        }
                                                        className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}

                                    {students.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan="6"
                                                className="px-4 py-10 text-center text-sm text-gray-500"
                                            >
                                                No students have been added yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <Modal
                show={qrStudent !== null}
                maxWidth="md"
                onClose={() => setQrStudent(null)}
            >
                {qrStudent && (
                    <div className="p-6 text-center">
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            {qrStudent.name}
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                            Student ID: {qrStudent.id} · {qrStudent.class} ·{' '}
                            {qrStudent.school?.name}
                        </p>

                        <img
                            src={route('operator.student.qr-code', {
                                student: qrStudent.id,
                            })}
                            alt={`QR code for ${qrStudent.name}`}
                            className="mx-auto mt-5 h-80 w-80 max-w-full"
                        />

                        <p className="mt-3 text-xs text-gray-500">
                            This QR permanently identifies this student. Keep it
                            private and scan it only through the meal collection
                            system.
                        </p>

                        <div className="mt-6 flex justify-center gap-3">
                            <a
                                href={route('operator.student.qr-code', {
                                    student: qrStudent.id,
                                })}
                                target="_blank"
                                rel="noreferrer"
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Open QR
                            </a>
                            <button
                                type="button"
                                onClick={() => setQrStudent(null)}
                                className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
