import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import WeeklyMenuTimetable from '@/Components/WeeklyMenuTimetable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

const days = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
];

export default function MenuManagement({
    menuItems,
    categories,
    schools,
    previewSchoolId,
}) {
    const [editingId, setEditingId] = useState(null);
    const fileInput = useRef(null);

    const {
        data,
        setData,
        post,
        delete: destroy,
        processing,
        errors,
        reset,
        clearErrors,
        transform,
    } = useForm({
        menu_category_ids: [],
        school_ids: [],
        name: '',
        description: '',
        days: [],
        price: '',
        image: null,
        is_available: true,
    });

    const clearForm = () => {
        setEditingId(null);
        reset();
        clearErrors();

        if (fileInput.current) {
            fileInput.current.value = '';
        }
    };

    const startEdit = (item) => {
        clearErrors();
        setEditingId(item.id);
        setData({
            menu_category_ids: item.menu_categories.map(
                (category) => category.id,
            ),
            school_ids: item.schools.map((school) => school.id),
            name: item.name,
            description: item.description ?? '',
            days: item.days.map((day) => day.day_of_week),
            price: item.price,
            image: null,
            is_available: Boolean(item.is_available),
        });

        if (fileInput.current) {
            fileInput.current.value = '';
        }
    };

    const editFromPreview = (item) => {
        startEdit(item);
        window.setTimeout(() => {
            document
                .getElementById('menu-form')
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 50);
    };

    const submit = (event) => {
        event.preventDefault();
        clearErrors();

        transform((formData) =>
            editingId ? { ...formData, _method: 'patch' } : formData,
        );

        post(
            editingId
                ? route('owner.menu.update', { menu: editingId })
                : route('owner.menu.store'),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: clearForm,
            },
        );
    };

    const toggleDay = (day) => {
        setData(
            'days',
            data.days.includes(day)
                ? data.days.filter((selectedDay) => selectedDay !== day)
                : [...data.days, day],
        );
    };

    const toggleSchool = (schoolId) => {
        setData(
            'school_ids',
            data.school_ids.includes(schoolId)
                ? data.school_ids.filter(
                      (selectedSchoolId) => selectedSchoolId !== schoolId,
                  )
                : [...data.school_ids, schoolId],
        );
    };

    const toggleCategory = (categoryId) => {
        setData(
            'menu_category_ids',
            data.menu_category_ids.includes(categoryId)
                ? data.menu_category_ids.filter(
                      (selectedCategoryId) => selectedCategoryId !== categoryId,
                  )
                : [...data.menu_category_ids, categoryId],
        );
    };

    const remove = (item) => {
        if (
            !window.confirm(
                `Delete ${item.name}? Its Google Drive image will also be deleted.`,
            )
        ) {
            return;
        }

        destroy(route('owner.menu.destroy', { menu: item.id }), {
            preserveScroll: true,
            onSuccess: () => {
                if (editingId === item.id) {
                    clearForm();
                }
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Menu Management
                </h2>
            }
        >
            <Head title="Menu Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div
                        id="menu-form"
                        className="scroll-mt-6 bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                    >
                        <h3 className="mb-6 text-lg font-medium text-gray-900 dark:text-gray-100">
                            {editingId ? 'Edit Meal' : 'Add Meal'}
                        </h3>

                        {editingId && (
                            <p className="mb-4 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                Editing menu item ID: {editingId}
                            </p>
                        )}

                        {categories.length === 0 && (
                            <p className="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                                Create a menu category before adding a meal.
                            </p>
                        )}

                        {schools.length === 0 && (
                            <p className="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                                Create a school before adding a meal.
                            </p>
                        )}

                        <form
                            onSubmit={submit}
                            className="grid gap-5 md:grid-cols-2"
                        >
                            <div>
                                <InputLabel htmlFor="name" value="Meal Name" />
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
                                <InputLabel value="Categories" />
                                <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                    {categories.map((category) => (
                                        <label
                                            key={category.id}
                                            className="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 dark:text-gray-200"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.menu_category_ids.includes(
                                                    category.id,
                                                )}
                                                onChange={() =>
                                                    toggleCategory(category.id)
                                                }
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            />
                                            {category.name}
                                        </label>
                                    ))}
                                </div>
                                <InputError
                                    message={errors.menu_category_ids}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel value="Available Days" />
                                <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    {days.map((day) => (
                                        <label
                                            key={day}
                                            className="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm capitalize text-gray-700 dark:text-gray-200"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.days.includes(
                                                    day,
                                                )}
                                                onChange={() => toggleDay(day)}
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            />
                                            {day}
                                        </label>
                                    ))}
                                </div>
                                <InputError
                                    message={errors.days}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel value="Available at Schools" />
                                <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                    {schools.map((school) => (
                                        <label
                                            key={school.id}
                                            className="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 dark:text-gray-200"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.school_ids.includes(
                                                    school.id,
                                                )}
                                                onChange={() =>
                                                    toggleSchool(school.id)
                                                }
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            />
                                            {school.name}
                                        </label>
                                    ))}
                                </div>
                                <InputError
                                    message={errors.school_ids}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="price"
                                    value="Price (RM)"
                                />
                                <TextInput
                                    id="price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.price}
                                    onChange={(event) =>
                                        setData('price', event.target.value)
                                    }
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError
                                    message={errors.price}
                                    className="mt-2"
                                />
                            </div>

                            <div className="md:col-span-2">
                                <InputLabel
                                    htmlFor="description"
                                    value="Description"
                                />
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(event) =>
                                        setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    rows="3"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <InputError
                                    message={errors.description}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="image"
                                    value={
                                        editingId
                                            ? 'Replace Meal Photo (optional)'
                                            : 'Meal Photo'
                                    }
                                />
                                <input
                                    ref={fileInput}
                                    id="image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(event) =>
                                        setData(
                                            'image',
                                            event.target.files[0] ?? null,
                                        )
                                    }
                                    className="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-200"
                                    required={!editingId}
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    JPEG, PNG, or WebP, maximum 5 MB.
                                </p>
                                <InputError
                                    message={errors.image}
                                    className="mt-2"
                                />
                            </div>

                            <div className="flex items-center pt-6">
                                <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input
                                        type="checkbox"
                                        checked={data.is_available}
                                        onChange={(event) =>
                                            setData(
                                                'is_available',
                                                event.target.checked,
                                            )
                                        }
                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    />
                                    Available for ordering
                                </label>
                            </div>

                            <div className="flex items-center gap-3 md:col-span-2">
                                <PrimaryButton
                                    disabled={
                                        processing ||
                                        categories.length === 0 ||
                                        schools.length === 0
                                    }
                                >
                                    {processing
                                        ? 'Saving...'
                                        : editingId
                                          ? 'Update Meal'
                                          : 'Add Meal'}
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
                                            'Photo',
                                            'Meal',
                                            'Category',
                                            'Schools',
                                            'Days',
                                            'Price',
                                            'Available',
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
                                    {menuItems.map((item) => (
                                        <tr key={item.id}>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {item.id}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.image_url ? (
                                                    <img
                                                        src={item.image_url}
                                                        alt={item.name}
                                                        className="mx-auto h-20 w-20 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-md bg-gray-100 text-xs text-gray-500">
                                                        No photo
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                <p className="font-medium">
                                                    {item.name}
                                                </p>
                                                <p className="max-w-xs text-xs text-gray-500">
                                                    {item.description}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                {item.menu_categories
                                                    .map(
                                                        (category) =>
                                                            category.name,
                                                    )
                                                    .join(', ') || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm capitalize text-gray-900">
                                                {item.schools
                                                    .map(
                                                        (school) => school.name,
                                                    )
                                                    .join(', ')}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm capitalize text-gray-900">
                                                {item.days
                                                    .map(
                                                        (day) =>
                                                            day.day_of_week,
                                                    )
                                                    .join(', ')}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm text-gray-900">
                                                RM{' '}
                                                {Number(item.price).toFixed(2)}
                                            </td>
                                            <td className="px-4 py-3 text-center text-sm">
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs font-medium ${item.is_available ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}
                                                >
                                                    {item.is_available
                                                        ? 'Yes'
                                                        : 'No'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <div className="flex justify-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            startEdit(item)
                                                        }
                                                        className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            remove(item)
                                                        }
                                                        className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {menuItems.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan="9"
                                                className="px-4 py-10 text-center text-sm text-gray-500"
                                            >
                                                No meals have been added yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <WeeklyMenuTimetable
                        menuItems={menuItems}
                        schools={schools}
                        initialSchoolId={previewSchoolId}
                        onEdit={editFromPreview}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
