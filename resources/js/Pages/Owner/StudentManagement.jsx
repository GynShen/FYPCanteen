import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import ManagementFilters from '@/Components/ManagementFilters';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import TablePagination from '@/Components/TablePagination';
import useTablePagination from '@/Hooks/useTablePagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function StudentManagement({ students, pendingStudents, schools, classes, filters }) {
    const [editingId, setEditingId] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [showApprovals, setShowApprovals] = useState(false);
    const { currentPage, setCurrentPage, paginatedItems } =
        useTablePagination(students);
    const {
        currentPage: approvalPage,
        setCurrentPage: setApprovalPage,
        paginatedItems: paginatedPendingStudents,
    } = useTablePagination(pendingStudents);
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
        setShowForm(true);
        clearErrors();
        setEditingId(student.id);
        setData({
            school_id: student.school_id,
            name: student.name,
            class: student.class,
        });
        window.setTimeout(() => document.getElementById('student-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    };

    const submit = (event) => {
        event.preventDefault();
        clearErrors();

        if (editingId) {
            patch(route('owner.student.update', { student: editingId }), {
                preserveScroll: true,
                onSuccess: clearForm,
            });
            return;
        }

        post(route('owner.student.store'), {
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

        destroy(route('owner.student.destroy', { student: student.id }), {
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

    const approve = (student) => {
        if (!window.confirm(`Approve ${student.name} (${student.student_code})?`)) return;
        router.patch(route('student-approvals.approve', { student: student.id }), {}, { preserveScroll: true });
    };

    const reject = (student) => {
        const reason = window.prompt(`Reason for rejecting ${student.name}:`);
        if (reason === null) return;
        if (reason.trim().length < 5) {
            window.alert('Please provide a rejection reason of at least 5 characters.');
            return;
        }
        router.patch(route('student-approvals.reject', { student: student.id }), { rejection_reason: reason.trim() }, { preserveScroll: true });
    };

    const editPending = (student) => {
        startEdit(student);
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
                    <ManagementFilters routeName="owner.student" filters={filters} fields={[
                        { name: 'search', label: 'Search', placeholder: 'Student name or student ID' },
                        { name: 'class', label: 'Class', type: 'select', options: classes.map((value) => ({ value, label: value })) },
                        { name: 'school_id', label: 'School', type: 'select', options: schools.map((school) => ({ value: school.id, label: school.name })) },
                        { name: 'status', label: 'Approval status', type: 'select', options: [{ value: 'approved', label: 'Approved' }, { value: 'pending', label: 'Pending' }, { value: 'rejected', label: 'Rejected' }] },
                    ]} />
                    <button type="button" onClick={() => setShowForm((current) => !current)} className="flex w-full items-center justify-between rounded-lg bg-white px-6 py-4 text-left font-bold text-slate-900 shadow dark:bg-gray-800 dark:text-white">
                        <span>{editingId ? `Edit Student ${editingId}` : 'Add Student'}</span><span className="text-xl">{showForm ? '⌃' : '⌄'}</span>
                    </button>
                    <div id="student-form" className={`${showForm ? '!mt-0 block rounded-t-none' : 'hidden'} scroll-mt-6 bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800`}>
                        <h3 className="mb-6 text-lg font-medium text-gray-900 dark:text-gray-100">
                            {editingId ? 'Edit Student' : 'Add Student'}
                        </h3>

                        {editingId && (
                            <p className="mb-4 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                Editing student ID: {students.find((student) => student.id === editingId)?.student_code}. The QR code
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
                        <button type="button" onClick={() => setShowApprovals((current) => !current)} className={`flex w-full items-center justify-between p-5 text-left ${showApprovals ? 'border-b border-gray-200' : ''}`} aria-expanded={showApprovals}>
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Pending Student Approvals ({pendingStudents.length})
                                </h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Verify parent-submitted student details before enabling meal ordering.
                                </p>
                            </div>
                            <span className="text-xl font-bold text-gray-700 dark:text-gray-200">{showApprovals ? '⌃' : '⌄'}</span>
                        </button>
                        {showApprovals && <>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-slate-100">
                                    <tr>
                                        {['Student ID', 'Name', 'Class', 'School', 'Parent', 'Submitted', 'Action'].map((heading) => (
                                            <th key={heading} className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700">{heading}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {paginatedPendingStudents.map((student) => (
                                        <tr key={student.id} className="even:bg-slate-50">
                                            <td className="px-4 py-3 text-center text-sm font-semibold text-gray-900">{student.student_code}</td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">{student.name}</td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">{student.class}</td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">{student.school?.name}</td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900"><span className="block font-medium">{student.child?.user?.name}</span><span className="text-xs text-gray-500">{student.child?.user?.email}</span></td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">{new Date(student.created_at).toLocaleString('en-MY')}</td>
                                            <td className="px-4 py-3 text-center"><div className="flex flex-wrap justify-center gap-2"><button type="button" onClick={() => editPending(student)} className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Edit</button><button type="button" onClick={() => approve(student)} className="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve</button><button type="button" onClick={() => reject(student)} className="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">Reject</button></div></td>
                                        </tr>
                                    ))}
                                    {pendingStudents.length === 0 && <tr><td colSpan="7" className="px-4 py-10 text-center text-sm text-gray-500">No pending student registrations.</td></tr>}
                                </tbody>
                            </table>
                        </div>
                        <TablePagination totalItems={pendingStudents.length} currentPage={approvalPage} onPageChange={setApprovalPage} />
                        </>}
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
                                            'Status',
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
                                    {paginatedItems.map((student) => (
                                        <tr key={student.id}>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {student.student_code}
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
                                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${student.approval_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : student.approval_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}`}>
                                                    {student.approval_status.charAt(0).toUpperCase() + student.approval_status.slice(1)}
                                                </span>
                                                {student.rejection_reason && <p className="mt-2 max-w-xs text-xs text-red-600">{student.rejection_reason}</p>}
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
                                                colSpan="7"
                                                className="px-4 py-10 text-center text-sm text-gray-500"
                                            >
                                                No students have been added yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <TablePagination totalItems={students.length} currentPage={currentPage} onPageChange={setCurrentPage} />
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
                            Student ID: {qrStudent.student_code} · {qrStudent.class} ·{' '}
                            {qrStudent.school?.name}
                        </p>

                        <img
                            src={route('owner.student.qr-code', {
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
                                href={route('owner.student.qr-code', {
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
