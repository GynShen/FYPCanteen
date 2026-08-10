import WeeklyMenuTimetable from '@/Components/WeeklyMenuTimetable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

export default function WeeklyMenu({ menuItems, schools, canEdit }) {
    const editMeal = (meal) => router.get(route('owner.menu'), { edit: meal.id });

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-100">Weekly Menu Timeline</h2>}>
            <Head title="Weekly Menu Timeline" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <WeeklyMenuTimetable menuItems={menuItems} schools={schools} onEdit={canEdit ? editMeal : undefined} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
