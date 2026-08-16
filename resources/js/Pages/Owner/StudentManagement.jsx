import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const formatPhoneNumber = (value) => {
    let digits = String(value ?? '').replace(/\D/g, '');

    if (digits.startsWith('60')) {
        digits = `0${digits.slice(2)}`;
    }

    if (digits.length <= 3) {
        return digits;
    }

    return `${digits.slice(0, 3)}-${digits.slice(3)}`;
};

export default function StudentManagement({
    students,
    schools,
    classes,
    filters,
}) {
    const [editing, setEditing] = useState(null);
    const [qrStudent, setQrStudent] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');
    const [classFilter, setClassFilter] = useState(filters.class ?? '');
    const firstLoad = useRef(true);
    const form = useForm({
        name: '',
        class: '',
        school_id: schools[0]?.id ?? '',
        guardian_phone_number: '',
    });

    useEffect(() => {
        if (firstLoad.current) {
            firstLoad.current = false;
            return;
        }
        const timer = window.setTimeout(
            () =>
                router.get(
                    route('owner.student'),
                    { search, class: classFilter },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                ),
            500,
        );
        return () => window.clearTimeout(timer);
    }, [search, classFilter]);

    const resetForm = () => {
        setEditing(null);
        setShowForm(false);
        form.reset();
        form.clearErrors();
    };
    const edit = (student) => {
        setEditing(student);
        setShowForm(true);
        form.setData({
            name: student.name,
            class: student.class,
            school_id: student.school_id,
            guardian_phone_number: formatPhoneNumber(
                student.guardian_phone_number,
            ),
        });
        window.setTimeout(
            () =>
                document
                    .getElementById('student-form')
                    ?.scrollIntoView({ behavior: 'smooth' }),
            50,
        );
    };
    const submit = (event) => {
        event.preventDefault();

        const normalizePhone = (value) => {
            const digits = String(value ?? '').replace(/\D/g, '');
            return digits.startsWith('60') ? `0${digits.slice(2)}` : digits;
        };

        if (
            editing?.child &&
            normalizePhone(editing.guardian_phone_number) !==
                normalizePhone(form.data.guardian_phone_number) &&
            !window.confirm(
                'Changing the guardian phone number will unlink this student from the current parent. Continue?',
            )
        ) {
            return;
        }

        const options = { preserveScroll: true, onSuccess: resetForm };
        editing
            ? form.patch(route('owner.student.update', editing.id), options)
            : form.post(route('owner.student.store'), options);
    };
    const remove = (student) => {
        if (
            window.confirm(
                `Delete ${student.name}? Only unlinked students without orders can be deleted.`,
            )
        )
            router.delete(route('owner.student.destroy', student.id), {
                preserveScroll: true,
            });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold text-white">
                    Student Management
                </h2>
            }
        >
            <Head title="Student Management" />
            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-lg bg-slate-800 p-5">
                        <div className="grid gap-4 md:grid-cols-[2fr_1fr]">
                            <div>
                                <InputLabel value="Search" />
                                <TextInput
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Student, class, parent name or phone"
                                    className="mt-1 w-full"
                                />
                            </div>
                            <div>
                                <InputLabel value="Class" />
                                <select
                                    value={classFilter}
                                    onChange={(e) =>
                                        setClassFilter(e.target.value)
                                    }
                                    className="mt-1 w-full rounded-md border-gray-300"
                                >
                                    <option value="">All classes</option>
                                    {classes.map((value) => (
                                        <option key={value} value={value}>
                                            {value}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </section>

                    <button
                        type="button"
                        onClick={() => setShowForm((value) => !value)}
                        className="flex w-full items-center justify-between rounded-lg bg-slate-800 px-6 py-4 font-bold text-white"
                    >
                        <span>{editing ? 'Edit Student' : 'Add Student'}</span>
                        <span>{showForm ? '▲' : '▼'}</span>
                    </button>
                    {showForm && (
                        <section
                            id="student-form"
                            className="!mt-0 scroll-mt-6 rounded-b-lg bg-slate-800 p-6"
                        >
                            <form
                                onSubmit={submit}
                                className="grid gap-4 md:grid-cols-2"
                            >
                                <div>
                                    <InputLabel value="Student Name" />
                                    <TextInput
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        className="mt-1 w-full"
                                        required
                                    />
                                    <InputError message={form.errors.name} />
                                </div>
                                <div>
                                    <InputLabel value="Class" />
                                    <TextInput
                                        value={form.data.class}
                                        onChange={(e) =>
                                            form.setData(
                                                'class',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 w-full"
                                        required
                                    />
                                    <InputError message={form.errors.class} />
                                </div>
                                <div>
                                    <InputLabel value="School" />
                                    <select
                                        value={form.data.school_id}
                                        onChange={(e) =>
                                            form.setData(
                                                'school_id',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 w-full rounded-md border-gray-300"
                                        required
                                    >
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
                                        message={form.errors.school_id}
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        htmlFor="guardian_phone_number"
                                        value="Guardian phone number"
                                    />
                                    <TextInput
                                        id="guardian_phone_number"
                                        value={form.data.guardian_phone_number}
                                        onChange={(e) =>
                                            form.setData(
                                                'guardian_phone_number',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Example: 012-3456789"
                                        className="mt-1 w-full"
                                    />
                                    <p className="mt-1 text-xs text-slate-400">
                                        Used to automatically match this student
                                        with a customer account.
                                    </p>
                                    <InputError
                                        message={
                                            form.errors.guardian_phone_number
                                        }
                                    />
                                </div>
                                <div className="flex gap-3 md:col-span-2">
                                    <PrimaryButton disabled={form.processing}>
                                        {editing
                                            ? 'Update Student'
                                            : 'Add Student'}
                                    </PrimaryButton>
                                    {editing && (
                                        <button
                                            type="button"
                                            onClick={resetForm}
                                            className="rounded bg-slate-200 px-4 py-2 text-slate-800"
                                        >
                                            Cancel
                                        </button>
                                    )}
                                </div>
                            </form>
                        </section>
                    )}

                    <section className="overflow-hidden rounded-lg bg-white shadow">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[950px] table-fixed text-slate-800">
                                <colgroup>
                                    <col className="w-[22%]" />
                                    <col className="w-[11%]" />
                                    <col className="w-[12%]" />
                                    <col className="w-[14%]" />
                                    <col className="w-[15%]" />
                                    <col className="w-[11%]" />
                                    <col className="w-[15%]" />
                                </colgroup>
                                <thead className="bg-slate-100 text-xs uppercase">
                                    <tr>
                                        <th className="px-4 py-3 text-left">
                                            Student
                                        </th>
                                        <th className="px-3 py-3 text-center">Class</th>
                                        <th className="px-3 py-3 text-center">School</th>
                                        <th className="px-3 py-3 text-center">Parent</th>
                                        <th className="px-3 py-3 text-center">Guardian phone</th>
                                        <th className="px-3 py-3 text-center">QR</th>
                                        <th className="px-3 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {students.map((student) => (
                                        <tr key={student.id}>
                                            <td className="px-4 py-4 font-bold">
                                                {student.name}
                                            </td>
                                            <td className="text-center">
                                                {student.class}
                                            </td>
                                            <td className="text-center">
                                                {student.school?.name}
                                            </td>
                                            <td className="text-center">
                                                {student.child?.user?.username ??
                                                    student.child?.user?.name ??
                                                    'Unlinked'}
                                            </td>
                                            <td className="text-center">
                                                {formatPhoneNumber(student.guardian_phone_number) ||
                                                    '—'}
                                            </td>
                                            <td className="text-center">
                                                <button
                                                    onClick={() =>
                                                        setQrStudent(student)
                                                    }
                                                    className="rounded bg-indigo-600 px-3 py-2 text-sm text-white"
                                                >
                                                    Show QR
                                                </button>
                                            </td>
                                            <td>
                                                <div className="flex justify-center gap-2">
                                                    <button
                                                        onClick={() =>
                                                            edit(student)
                                                        }
                                                        className="rounded bg-indigo-600 px-3 py-2 text-sm text-white"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        onClick={() =>
                                                            remove(student)
                                                        }
                                                        className="rounded bg-red-600 px-3 py-2 text-sm text-white"
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
                                                className="p-10 text-center text-slate-500"
                                            >
                                                No students match the search.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
            <Modal
                show={Boolean(qrStudent)}
                maxWidth="md"
                onClose={() => setQrStudent(null)}
            >
                {qrStudent && (
                    <div className="bg-white p-6 text-center text-slate-900">
                        <h3 className="text-xl font-bold">{qrStudent.name}</h3>
                        <p className="text-sm text-slate-600">
                            {qrStudent.class} · {qrStudent.school?.name}
                        </p>
                        <img
                            src={route('owner.student.qr-code', qrStudent.id)}
                            alt={`QR code for ${qrStudent.name}`}
                            className="mx-auto mt-5 h-80 w-80"
                        />
                        <button
                            onClick={() => setQrStudent(null)}
                            className="mt-5 rounded bg-slate-200 px-4 py-2"
                        >
                            Close
                        </button>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
