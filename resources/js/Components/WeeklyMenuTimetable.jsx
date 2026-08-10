import MealTimeline from '@/Components/MealTimeline';
import Modal from '@/Components/Modal';
import { useMemo, useState } from 'react';

export default function WeeklyMenuTimetable({ menuItems, schools, initialSchoolId = null, onEdit }) {
    const [selectedSchool, setSelectedSchool] = useState(() => {
        const requested = schools.find((school) => school.id === Number(initialSchoolId));
        return String(requested?.id ?? schools[0]?.id ?? '');
    });
    const [selectedMeal, setSelectedMeal] = useState(null);
    const visibleMeals = useMemo(() => menuItems.filter((meal) =>
        meal.schools.some((school) => school.id === Number(selectedSchool))), [menuItems, selectedSchool]);

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
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Weekly Menu Timeline</h3>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Days are rows and collection times are columns. Click a meal box to view or edit it.</p>
                    </div>
                    <div className="w-full sm:w-72">
                        <label htmlFor="preview-school-filter" className="block text-sm font-medium text-gray-700 dark:text-gray-200">Preview for school</label>
                        <select id="preview-school-filter" value={selectedSchool} onChange={(event) => setSelectedSchool(event.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            {schools.map((school) => <option key={school.id} value={school.id}>{school.name}</option>)}
                        </select>
                    </div>
                </div>
            </div>

            {visibleMeals.length > 0
                ? <MealTimeline menuItems={visibleMeals} onMealClick={(meal) => setSelectedMeal(meal)} />
                : <div className="rounded-lg bg-white p-10 text-center text-sm text-slate-500 shadow">No meals are assigned to this school.</div>}

            <Modal show={selectedMeal !== null} maxWidth="2xl" onClose={() => setSelectedMeal(null)}>
                {selectedMeal && (
                    <div className="p-6">
                        <div className="flex flex-col gap-5 sm:flex-row">
                            {selectedMeal.image_url
                                ? <img src={selectedMeal.image_url} alt={selectedMeal.name} className="h-48 w-full rounded-lg object-cover sm:w-48" />
                                : <div className="flex h-48 w-full items-center justify-center rounded-lg bg-gray-100 text-gray-500 sm:w-48">No photo</div>}
                            <div className="flex-1">
                                <h3 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">{selectedMeal.name}</h3>
                                <p className="text-sm text-gray-500">Menu item ID: {selectedMeal.id}</p>
                                <p className="mt-3 text-sm text-gray-600 dark:text-gray-300">{selectedMeal.description || 'No description provided.'}</p>
                                <p className="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">RM {Number(selectedMeal.price).toFixed(2)}</p>
                                <p className="mt-2 text-sm font-medium text-gray-500">Collection: {selectedMeal.available_start_time && selectedMeal.available_end_time ? `${selectedMeal.available_start_time.slice(0, 5)}–${selectedMeal.available_end_time.slice(0, 5)}` : 'Not set'}</p>
                                <p className="mt-3 text-sm text-gray-500">Categories: {selectedMeal.menu_categories.map((category) => category.name).join(', ') || '-'}</p>
                            </div>
                        </div>
                        <div className="mt-6 flex justify-end gap-3">
                            <button type="button" onClick={() => setSelectedMeal(null)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700">Close</button>
                            {onEdit && <button type="button" onClick={editMeal} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Edit Meal</button>}
                        </div>
                    </div>
                )}
            </Modal>
        </section>
    );
}
