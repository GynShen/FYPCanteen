import Modal from '@/Components/Modal';
import TablePagination, { TABLE_PAGE_SIZE } from '@/Components/TablePagination';
import { useEffect, useMemo, useState } from 'react';

const weekDays = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
];

const dayName = (day) => day.charAt(0).toUpperCase() + day.slice(1);
const mealsPerPage = TABLE_PAGE_SIZE;

export default function WeeklyMenuTimetable({
    menuItems,
    schools,
    initialSchoolId = null,
    onEdit,
}) {
    const [selectedSchool, setSelectedSchool] = useState(() => {
        const requestedSchool = schools.find(
            (school) => school.id === Number(initialSchoolId),
        );

        return String(requestedSchool?.id ?? schools[0]?.id ?? '');
    });
    const [selectedMeal, setSelectedMeal] = useState(null);
    const [currentPage, setCurrentPage] = useState(1);

    const visibleMeals = useMemo(() => {
        const schoolId = Number(selectedSchool);

        return menuItems.filter((meal) =>
            meal.schools.some((school) => school.id === schoolId),
        );
    }, [menuItems, selectedSchool]);

    const paginatedMeals = visibleMeals.slice(
        (currentPage - 1) * mealsPerPage,
        currentPage * mealsPerPage,
    );

    useEffect(() => {
        setCurrentPage(1);
    }, [menuItems, selectedSchool]);

    const scheduledDays = (meal) =>
        new Set(meal.days.map((day) => day.day_of_week));

    const editMeal = () => {
        const meal = selectedMeal;
        setSelectedMeal(null);
        onEdit?.(meal);
    };

    return (
        <section id="weekly-preview" className="space-y-4">
            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-6 dark:bg-gray-800">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Weekly Menu Timetable
                        </h3>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Meals are rows and weekdays are columns. Click a
                            meal to view its details or edit it.
                        </p>
                    </div>

                    <div className="w-full sm:w-72">
                        <label
                            htmlFor="preview-school-filter"
                            className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                        >
                            Preview for school
                        </label>
                        <select
                            id="preview-school-filter"
                            value={selectedSchool}
                            onChange={(event) =>
                                setSelectedSchool(event.target.value)
                            }
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            {schools.map((school) => (
                                <option key={school.id} value={school.id}>
                                    {school.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>

            <div className="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
                <div className="overflow-x-auto">
                    <table className="min-w-[1100px] divide-y divide-gray-200">
                        <thead className="bg-slate-100">
                            <tr>
                                <th className="sticky left-0 z-10 min-w-60 bg-slate-100 px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700">
                                    Meal
                                </th>
                                {weekDays.map((day) => (
                                    <th
                                        key={day}
                                        className="min-w-28 px-3 py-3 text-center text-xs font-semibold uppercase text-gray-700"
                                    >
                                        {dayName(day)}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {paginatedMeals.map((meal) => {
                                const days = scheduledDays(meal);

                                return (
                                    <tr key={meal.id}>
                                        <td className="sticky left-0 z-10 bg-white px-4 py-3">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setSelectedMeal(meal)
                                                }
                                                className="flex items-center gap-3 text-left"
                                            >
                                                {meal.image_url ? (
                                                    <img
                                                        src={meal.image_url}
                                                        alt=""
                                                        className="h-12 w-12 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <span className="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-xs text-gray-500">
                                                        No photo
                                                    </span>
                                                )}
                                                <span>
                                                    <span className="block font-medium text-indigo-700 hover:underline">
                                                        {meal.name}
                                                    </span>
                                                    <span className="block text-xs text-gray-500">
                                                        ID {meal.id} | RM{' '}
                                                        {Number(
                                                            meal.price,
                                                        ).toFixed(2)}
                                                    </span>
                                                </span>
                                            </button>
                                        </td>
                                        {weekDays.map((day) => {
                                            const scheduled = days.has(day);
                                            const canOrder =
                                                scheduled && meal.is_available;

                                            return (
                                                <td
                                                    key={day}
                                                    className={`px-3 py-4 text-center ${
                                                        canOrder
                                                            ? 'bg-green-50'
                                                            : scheduled
                                                              ? 'bg-red-50'
                                                              : 'bg-gray-50/40'
                                                    }`}
                                                >
                                                    <span
                                                        className={
                                                            canOrder
                                                                ? 'inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-green-600 px-2 font-bold text-white'
                                                                : scheduled
                                                                  ? 'inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-red-600 px-2 font-bold text-white'
                                                                  : 'text-gray-300'
                                                        }
                                                    >
                                                        {canOrder
                                                            ? 'Yes'
                                                            : scheduled
                                                              ? 'No'
                                                              : '-'}
                                                    </span>
                                                </td>
                                            );
                                        })}
                                    </tr>
                                );
                            })}
                            {visibleMeals.length === 0 && (
                                <tr>
                                    <td
                                        colSpan="8"
                                        className="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        No meals are assigned to this school.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <TablePagination totalItems={visibleMeals.length} currentPage={currentPage} onPageChange={setCurrentPage} />
            </div>

            <Modal
                show={selectedMeal !== null}
                maxWidth="2xl"
                onClose={() => setSelectedMeal(null)}
            >
                {selectedMeal && (
                    <div className="p-6">
                        <div className="flex flex-col gap-5 sm:flex-row">
                            {selectedMeal.image_url ? (
                                <img
                                    src={selectedMeal.image_url}
                                    alt={selectedMeal.name}
                                    className="h-48 w-full rounded-lg object-cover sm:w-48"
                                />
                            ) : (
                                <div className="flex h-48 w-full items-center justify-center rounded-lg bg-gray-100 text-gray-500 sm:w-48">
                                    No photo
                                </div>
                            )}
                            <div className="flex-1">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                            {selectedMeal.name}
                                        </h3>
                                        <p className="text-sm text-gray-500">
                                            Menu item ID: {selectedMeal.id}
                                        </p>
                                    </div>
                                    <span
                                        className={`rounded-full px-3 py-1 text-xs font-medium ${
                                            selectedMeal.is_available
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-600'
                                        }`}
                                    >
                                        {selectedMeal.is_available
                                            ? 'Available'
                                            : 'Unavailable'}
                                    </span>
                                </div>
                                <p className="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                    {selectedMeal.description ||
                                        'No description provided.'}
                                </p>
                                <p className="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    RM {Number(selectedMeal.price).toFixed(2)}
                                </p>
                                <p className="mt-4 text-sm text-gray-500">
                                    Categories:{' '}
                                    {selectedMeal.menu_categories
                                        .map((category) => category.name)
                                        .join(', ') || '-'}
                                </p>
                                <p className="mt-1 text-sm text-gray-500">
                                    Schools:{' '}
                                    {selectedMeal.schools
                                        .map((school) => school.name)
                                        .join(', ') || '-'}
                                </p>
                            </div>
                        </div>

                        <div className="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                            {weekDays.map((day) => {
                                const active =
                                    scheduledDays(selectedMeal).has(day);
                                return (
                                    <div
                                        key={day}
                                        className={`rounded-md border px-2 py-3 text-center text-sm font-medium ${
                                            active
                                                ? 'border-green-300 bg-green-50 text-green-700'
                                                : 'border-gray-200 bg-gray-50 text-gray-400'
                                        }`}
                                    >
                                        {dayName(day)}
                                        <span className="mt-1 block text-xs">
                                            {active
                                                ? 'Scheduled'
                                                : 'Not scheduled'}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                onClick={() => setSelectedMeal(null)}
                                className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300"
                            >
                                Close
                            </button>
                            {onEdit && (
                                <button
                                    type="button"
                                    onClick={editMeal}
                                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    Edit Meal
                                </button>
                            )}
                        </div>
                    </div>
                )}
            </Modal>
        </section>
    );
}
