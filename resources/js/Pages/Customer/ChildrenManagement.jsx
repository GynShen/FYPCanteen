import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import TablePagination from '@/Components/TablePagination';
import useTablePagination from '@/Hooks/useTablePagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function ChildrenManagement({ children, schools }) {
    const [editingId, setEditingId] = useState(null);
    const [qrChild, setQrChild] = useState(null);
    const [showStudentRequest, setShowStudentRequest] = useState(false);
    const [resubmittingChild, setResubmittingChild] = useState(null);
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
        student_code: '',
    });
    const studentRequestForm = useForm({
        name: '',
        class: '',
        school_id: schools[0]?.id ?? '',
    });

    const clearForm = () => {
        setEditingId(null);
        clearErrors();
        reset();
    };

    const startEdit = (child) => {
        clearErrors();
        setEditingId(child.id);
        setData('student_code', child.student.student_code);
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

    const submitStudentRequest = (event) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                studentRequestForm.reset();
                setShowStudentRequest(false);
                setResubmittingChild(null);
            },
        };

        if (resubmittingChild) {
            studentRequestForm.patch(route('customer.children.resubmit', { child: resubmittingChild.id }), options);
            return;
        }

        studentRequestForm.post(route('customer.children.request-student'), options);
    };

    const openResubmit = (child) => {
        setResubmittingChild(child);
        studentRequestForm.setData({
            name: child.student.name,
            class: child.student.class,
            school_id: child.student.school_id,
        });
        studentRequestForm.clearErrors();
        setShowStudentRequest(true);
        window.setTimeout(() => document.getElementById('student-request-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
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
                                htmlFor="student_code"
                                value="Student ID"
                            />
                            <TextInput
                                id="student_code"
                                type="text"
                                value={data.student_code}
                                onChange={(event) =>
                                    setData('student_code', event.target.value.toUpperCase())
                                }
                                className="mt-1 block w-full"
                                placeholder="Example: STU000001"
                                required
                            />
                            <InputError
                                message={errors.student_code}
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

                        <div className="mt-6 border-t border-gray-200 pt-5">
                            <button
                                type="button"
                                onClick={() => {
                                    if (!showStudentRequest) {
                                        setResubmittingChild(null);
                                        studentRequestForm.reset();
                                        studentRequestForm.clearErrors();
                                    }
                                    setShowStudentRequest((value) => !value);
                                }}
                                className="text-sm font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
                            >
                                Student ID not found? Submit a new student
                            </button>

                            {showStudentRequest && (
                                <form id="student-request-form" onSubmit={submitStudentRequest} className="mt-5 grid scroll-mt-6 gap-4 md:grid-cols-3">
                                    {resubmittingChild && <p className="rounded-md bg-red-50 p-3 text-sm text-red-700 md:col-span-3"><strong>Rejection reason:</strong> {resubmittingChild.student.rejection_reason}</p>}
                                    <div>
                                        <InputLabel htmlFor="new-student-name" value="Student Name" />
                                        <TextInput id="new-student-name" value={studentRequestForm.data.name} onChange={(event) => studentRequestForm.setData('name', event.target.value)} className="mt-1 block w-full" required />
                                        <InputError message={studentRequestForm.errors.name} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="new-student-class" value="Class" />
                                        <TextInput id="new-student-class" value={studentRequestForm.data.class} onChange={(event) => studentRequestForm.setData('class', event.target.value)} className="mt-1 block w-full" placeholder="Example: 4 Amanah" required />
                                        <InputError message={studentRequestForm.errors.class} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="new-student-school" value="School" />
                                        <select id="new-student-school" value={studentRequestForm.data.school_id} onChange={(event) => studentRequestForm.setData('school_id', event.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            <option value="">Select a school</option>
                                            {schools.map((school) => <option key={school.id} value={school.id}>{school.name}</option>)}
                                        </select>
                                        <InputError message={studentRequestForm.errors.school_id} className="mt-2" />
                                    </div>
                                    <div className="md:col-span-3">
                                        <p className="mb-3 text-sm text-amber-700">The student will remain pending until approved by the school or owner. Ordering is disabled while pending.</p>
                                        <PrimaryButton disabled={studentRequestForm.processing || schools.length === 0}>
                                            {studentRequestForm.processing ? 'Submitting...' : resubmittingChild ? 'Correct & Resubmit' : 'Submit for Approval'}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            )}
                        </div>
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
                                            'Status',
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
                                                {child.student.student_code}
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
                                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${child.student.approval_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : child.student.approval_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}`}>
                                                    {child.student.approval_status.charAt(0).toUpperCase() + child.student.approval_status.slice(1)}
                                                </span>
                                                {child.student.rejection_reason && <p className="mt-2 max-w-xs text-xs text-red-600">{child.student.rejection_reason}</p>}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <div className="flex justify-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => openResubmit(child)}
                                                        className={`rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700 ${child.student.approval_status === 'rejected' ? '' : 'hidden'}`}
                                                    >
                                                        Correct &amp; Resubmit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setQrChild(child)
                                                        }
                                                        className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                                    >
                                                        Show QR
                                                    </button>
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
                                                colSpan="6"
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

            <Modal
                show={qrChild !== null}
                maxWidth="md"
                onClose={() => setQrChild(null)}
            >
                {qrChild && (
                    <div className="bg-white p-6 text-center text-slate-900">
                        <h3 className="text-xl font-semibold text-slate-900">
                            {qrChild.student.name}
                        </h3>
                        <p className="mt-1 text-sm text-slate-600">
                            Student ID: {qrChild.student.student_code} · {qrChild.student.class} · {qrChild.student.school?.name}
                        </p>

                        <img
                            src={route('customer.children.qr-code', {
                                child: qrChild.id,
                            })}
                            alt={`QR code for ${qrChild.student.name}`}
                            className="mx-auto mt-5 h-80 w-80 max-w-full"
                        />

                        <p className="mt-3 text-xs text-slate-500">
                            This QR permanently identifies your child. Keep it private and use it only for meal collection.
                        </p>

                        <div className="mt-6 flex justify-center gap-3">
                            <a
                                href={route('customer.children.qr-code', {
                                    child: qrChild.id,
                                })}
                                target="_blank"
                                rel="noreferrer"
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Open QR
                            </a>
                            <button
                                type="button"
                                onClick={() => setQrChild(null)}
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
