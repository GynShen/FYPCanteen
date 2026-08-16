import Modal from '@/Components/Modal';
import MealTimeline from '@/Components/MealTimeline';
import TablePagination, { TABLE_PAGE_SIZE } from '@/Components/TablePagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import useTablePagination from '@/Hooks/useTablePagination';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

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
const localDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};
const orderingWeekStart = () => {
    const today = new Date();
    const isoDay = today.getDay() === 0 ? 7 : today.getDay();
    const monday = new Date(today);
    monday.setHours(0, 0, 0, 0);
    monday.setDate(today.getDate() - isoDay + 1 + (isoDay === 7 ? 14 : 7));
    return monday;
};
const nextDateForDay = (day) => {
    const date = orderingWeekStart();
    date.setDate(date.getDate() + weekDays.indexOf(day));
    return localDate(date);
};
const nextDateForDays = (availableDays) => {
    const firstDay = weekDays.find((day) => availableDays.includes(day));
    return firstDay ? nextDateForDay(firstDay) : '';
};
const shortDate = (day) =>
    new Date(`${nextDateForDay(day)}T00:00:00`).toLocaleDateString('en-MY', {
        day: '2-digit',
        month: '2-digit',
    });
const priceForMeal = (meal) => Number(meal.price);

export default function CustomerMenu({ children, menuItems, cartItems }) {
    const errors = usePage().props.errors;
    const [selectedChildId, setSelectedChildId] = useState(
        children[0]?.student_id ?? '',
    );
    const [selectedMeal, setSelectedMeal] = useState(null);
    const [editingCartItem, setEditingCartItem] = useState(null);
    const [selection, setSelection] = useState({ date: '', quantity: 1 });
    const [processing, setProcessing] = useState(false);
    const [showCart, setShowCart] = useState(false);
    const [draftQuantities, setDraftQuantities] = useState({});
    const [expandedChildren, setExpandedChildren] = useState({});
    const [cartPages, setCartPages] = useState({});

    const selectedChild = children.find(
        (child) => child.student_id === Number(selectedChildId),
    );
    const canOrderForSelectedChild =
        selectedChild?.student.school?.is_active;
    const visibleMeals = useMemo(() => {
        if (!selectedChild) return [];
        return menuItems.filter((meal) =>
            meal.schools.some(
                (school) => school.id === selectedChild.student.school_id,
            ),
        );
    }, [menuItems, selectedChild]);
    const { currentPage, setCurrentPage, paginatedItems } =
        useTablePagination(visibleMeals);
    const savedTotal = cartItems.reduce(
        (sum, item) => sum + priceForMeal(item.menu_item) * item.quantity,
        0,
    );
    const savedQuantity = cartItems.reduce(
        (sum, item) => sum + Number(item.quantity),
        0,
    );
    const total = Object.values(draftQuantities).reduce((sum, draft) => {
        const saved = cartItems.find(
            (item) =>
                item.student_id === draft.studentId &&
                item.menu_item_id === draft.mealId &&
                item.scheduled_time.slice(0, 10) === draft.date,
        );
        return sum + (draft.quantity - (saved?.quantity ?? 0)) * draft.price;
    }, savedTotal);
    const totalQuantity = Object.values(draftQuantities).reduce(
        (sum, draft) => {
            const saved = cartItems.find(
                (item) =>
                    item.student_id === draft.studentId &&
                    item.menu_item_id === draft.mealId &&
                    item.scheduled_time.slice(0, 10) === draft.date,
            );
            return sum + draft.quantity - (saved?.quantity ?? 0);
        },
        savedQuantity,
    );
    const cartGroups = children.map((child) => ({
        child,
        items: cartItems.filter(
            (item) => item.student_id === child.student_id,
        ),
    }));
    const toggleChildCart = (studentId) => {
        setExpandedChildren((current) => ({
            ...current,
            [studentId]: !(current[studentId] ?? false),
        }));
    };

    const quantityKey = (mealId, day) =>
        `${selectedChildId}-${mealId}-${nextDateForDay(day)}`;
    const cartItemFor = (meal, day) => {
        const date = nextDateForDay(day);

        return cartItems.find(
            (item) =>
                item.student_id === Number(selectedChildId) &&
                item.menu_item_id === meal.id &&
                item.scheduled_time.slice(0, 10) === date,
        );
    };
    const quantityFor = (meal, day) => {
        const date = nextDateForDay(day);
        const key = quantityKey(meal.id, day);
        if (draftQuantities[key]) return draftQuantities[key].quantity;
        return (
            cartItemFor(meal, day)?.quantity ?? 0
        );
    };
    const changeTimetableQuantity = (meal, day, value) => {
        const quantity = Math.max(0, Math.min(20, Number(value) || 0));
        const date = nextDateForDay(day);
        const key = quantityKey(meal.id, day);
        const savedItem = cartItemFor(meal, day);
        setDraftQuantities((current) => ({
            ...current,
            [key]: {
                studentId: Number(selectedChildId),
                mealId: meal.id,
                date,
                price: priceForMeal(meal),
                quantity,
            },
        }));
    };
    const saveTimetableQuantity = (meal, day, quantityOverride = null) => {
        const quantity = quantityOverride ?? quantityFor(meal, day);
        router.put(
            route('customer.cart.selection'),
            {
                student_id: Number(selectedChildId),
                menu_item_id: meal.id,
                collection_date: nextDateForDay(day),
                quantity,
            },
            { preserveScroll: true },
        );
    };

    const openMeal = (meal, selectedDay = null) => {
        const date = selectedDay
            ? nextDateForDay(selectedDay)
            : nextDateForDays(meal.days.map((day) => day.day_of_week));
        const draft = draftQuantities[`${selectedChildId}-${meal.id}-${date}`];
        const existingItem = cartItems.find(
            (item) =>
                String(item.student_id) === String(selectedChildId) &&
                item.menu_item_id === meal.id &&
                item.scheduled_time.slice(0, 10) === date,
        );

        setEditingCartItem(null);
        setSelectedMeal(meal);
        setSelection({
            date,
            quantity: draft?.quantity ?? existingItem?.quantity ?? 1,
        });
    };
    const editCartItem = (item) => {
        setShowCart(false);
        setSelectedChildId(String(item.student_id));
        setEditingCartItem(item);
        setSelectedMeal(item.menu_item);
        setSelection({
            date: item.scheduled_time.slice(0, 10),
            quantity: item.quantity,
        });
    };
    const closeMeal = () => {
        const returnToCart = editingCartItem !== null;
        setSelectedMeal(null);
        setEditingCartItem(null);
        if (returnToCart) setShowCart(true);
    };
    const saveSelection = () => {
        const payload = {
            student_id: Number(selectedChildId),
            menu_item_id: selectedMeal.id,
            collection_date: selection.date,
            quantity: Number(selection.quantity),
        };
        const options = {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onSuccess: () => {
                const key = `${selectedChildId}-${selectedMeal.id}-${selection.date}`;
                setDraftQuantities((current) => {
                    const next = { ...current };
                    delete next[key];
                    return next;
                });
                closeMeal();
            },
            onFinish: () => setProcessing(false),
        };

        if (editingCartItem) {
            router.patch(
                route('customer.cart.update', {
                    cartItem: editingCartItem.id,
                }),
                payload,
                options,
            );
        } else {
            router.post(route('customer.cart.store'), payload, options);
        }
    };
    const removeItem = (item) => {
        router.delete(route('customer.cart.destroy', { cartItem: item.id }), {
            preserveScroll: true,
        });
    };
    const checkout = () => {
        if (
            window.confirm(
                'Continue to Stripe to pay for this order?',
            )
        ) {
            router.post(route('customer.checkout'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Menu
                </h2>
            }
        >
            <Head title="Menu" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                        Meal orders for the following week must be placed by Saturday. Please review and complete your children&apos;s meal selections before the weekly deadline.
                    </div>

                    {children.length === 0 ? (
                        <div className="rounded-lg bg-amber-50 p-6 text-amber-800 shadow">
                            Link a child in Children Management before ordering
                            meals.
                        </div>
                    ) : (
                        <>
                            <section className="bg-white p-4 shadow sm:rounded-lg sm:p-6 dark:bg-gray-800">
                                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            Weekly Menu Timetable
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Click a meal to view its details and
                                            add it to the cart.
                                        </p>
                                    </div>
                                    <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-end">
                                    <div className="w-full sm:w-80">
                                        <label
                                            htmlFor="child-filter"
                                            className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                                        >
                                            Menu for child
                                        </label>
                                        <select
                                            id="child-filter"
                                            value={selectedChildId}
                                            onChange={(event) =>
                                                setSelectedChildId(
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            {children.map((child) => (
                                                <option
                                                    key={child.id}
                                                    value={child.student_id}
                                                >
                                                    {child.student.name} -{' '}
                                                    {child.student.school.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    {canOrderForSelectedChild && (
                                        <button type="button" onClick={() => setShowCart(true)} className="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                            Cart ({totalQuantity}) · RM {total.toFixed(2)}
                                        </button>
                                    )}
                                    </div>
                                </div>
                                {Object.values(errors).length > 0 && (
                                    <div className="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                                        {Object.values(errors)[0]}
                                    </div>
                                )}
                            </section>

                            {!canOrderForSelectedChild && (
                                <section className="rounded-lg border border-amber-300 bg-amber-50 p-6 shadow">
                                    <h3 className="text-lg font-semibold text-amber-900">
                                        Meal ordering is temporarily unavailable
                                    </h3>
                                    <p className="mt-2 text-sm text-amber-800">
                                        {`${selectedChild?.student.school?.name} is currently inactive. We apologize for the inconvenience. Please contact the school for further assistance.`}
                                    </p>
                                </section>
                            )}

                            {canOrderForSelectedChild && (
                            <section className="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
                                <div className="p-4">
                                    {visibleMeals.length > 0 ? (
                                        <MealTimeline
                                            menuItems={paginatedItems}
                                            onMealClick={openMeal}
                                            getSelection={cartItemFor}
                                            dayDates={Object.fromEntries(
                                                weekDays.map((day) => [day, shortDate(day)]),
                                            )}
                                        />
                                    ) : (
                                        <div className="p-10 text-center text-sm text-gray-500">No meals are assigned to this child's school.</div>
                                    )}
                                    <TablePagination totalItems={visibleMeals.length} currentPage={currentPage} onPageChange={setCurrentPage} />
                                </div>
                                <div className="hidden">
                                    <table className="w-full min-w-[820px] table-fixed border-collapse border border-slate-300">
                                        <thead className="bg-slate-100">
                                            <tr>
                                                <th className="sticky left-0 z-10 w-48 border border-slate-300 bg-slate-200 px-3 py-2 text-left text-xs font-semibold uppercase text-gray-700">
                                                    Meal
                                                </th>
                                                {weekDays.map((day) => (
                                                    <th
                                                        key={day}
                                                        className="border border-slate-300 bg-slate-200 px-1 py-2 text-center text-xs font-semibold uppercase text-gray-700"
                                                    >
                                                        {dayName(day)}
                                                        <span className="mt-0.5 block text-[10px] font-normal">{shortDate(day)}</span>
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {paginatedItems.map((meal) => {
                                                const days = new Set(
                                                    meal.days.map(
                                                        (day) =>
                                                            day.day_of_week,
                                                    ),
                                                );
                                                return (
                                                    <tr key={meal.id} className="odd:bg-white even:bg-slate-50">
                                                        <td className="sticky left-0 z-10 border border-slate-300 bg-inherit px-2 py-2">
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    openMeal(
                                                                        meal,
                                                                    )
                                                                }
                                                                className="flex items-center gap-3 text-left"
                                                            >
                                                                {meal.image_url ? (
                                                                    <img
                                                                        src={
                                                                            meal.image_url
                                                                        }
                                                                        alt=""
                                                                        className="h-9 w-9 rounded-md object-cover"
                                                                    />
                                                                ) : (
                                                                    <span className="flex h-9 w-9 items-center justify-center rounded-md bg-gray-100 text-[10px] text-gray-500">
                                                                        No photo
                                                                    </span>
                                                                )}
                                                                <span>
                                                                    <span className="block font-medium text-indigo-700 hover:underline">
                                                                        {
                                                                            meal.name
                                                                        }
                                                                    </span>
                                                                    <span className="block text-xs text-gray-500">
                                                                        ID{' '}
                                                                        {
                                                                            meal.id
                                                                        }{' '}
                                                                        | RM{' '}
                                                                        {Number(
                                                                            meal.price,
                                                                        ).toFixed(
                                                                            2,
                                                                        )}
                                                                    </span>
                                                                </span>
                                                            </button>
                                                        </td>
                                                        {weekDays.map((day) => (
                                                            <td
                                                                key={day}
                                                                className={`border border-slate-300 px-1 py-2 text-center ${days.has(day) ? 'bg-emerald-50' : 'bg-slate-100'}`}
                                                            >
                                                                {days.has(day) ? (
                                                                    <div>
                                                                        <div className="mx-auto flex w-[74px] items-center rounded-md border border-gray-300 bg-white">
                                                                            <button type="button" onClick={() => { const quantity = Math.max(0, quantityFor(meal, day) - 1); changeTimetableQuantity(meal, day, quantity); saveTimetableQuantity(meal, day, quantity); }} className="px-1.5 py-0.5 text-base text-gray-600">−</button>
                                                                            <input type="number" min="0" max="20" value={quantityFor(meal, day)} onChange={(event) => changeTimetableQuantity(meal, day, event.target.value)} onBlur={() => saveTimetableQuantity(meal, day)} className="w-8 border-0 p-0.5 text-center text-xs focus:ring-0" />
                                                                            <button type="button" onClick={() => { const quantity = Math.min(20, quantityFor(meal, day) + 1); changeTimetableQuantity(meal, day, quantity); saveTimetableQuantity(meal, day, quantity); }} className="px-1.5 py-0.5 text-base text-gray-600">+</button>
                                                                        </div>
                                                                        {cartItemFor(meal, day) && (
                                                                            <span className="mt-1 block text-[10px] font-semibold text-slate-600">
                                                                                RM {priceForMeal(meal).toFixed(2)}
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                ) : <span className="text-gray-300">-</span>}
                                                            </td>
                                                        ))}
                                                    </tr>
                                                );
                                            })}
                                            {visibleMeals.length === 0 && (
                                                <tr>
                                                    <td
                                                        colSpan="8"
                                                        className="px-4 py-12 text-center text-sm text-gray-500"
                                                    >
                                                        No meals are assigned to
                                                        this child's school.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                    <TablePagination totalItems={visibleMeals.length} currentPage={currentPage} onPageChange={setCurrentPage} />
                                </div>
                            </section>
                            )}
                        </>
                    )}

                    <Modal show={showCart} maxWidth="5xl" onClose={() => setShowCart(false)}>
                    <section className="max-h-[78vh] overflow-y-auto bg-slate-50 dark:bg-gray-800">
                        <div className="border-b border-gray-200 p-5">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Cart
                            </h3>
                            <p className="text-sm text-gray-500">Expand each child to review which weekdays have meals and which are still empty.</p>
                        </div>
                        <div className="space-y-3 p-5">
                            {cartGroups.map(({ child, items }) => {
                                const expanded = expandedChildren[child.student_id] ?? false;
                                const meals = [...new Map(items.map((item) => [item.menu_item_id, item.menu_item])).values()];
                                const cartPage = cartPages[child.student_id] ?? 1;
                                const paginatedCartMeals = meals.slice((cartPage - 1) * TABLE_PAGE_SIZE, cartPage * TABLE_PAGE_SIZE);
                                const childTotal = items.reduce((sum, item) => sum + priceForMeal(item.menu_item) * item.quantity, 0);
                                return (
                                    <div key={child.id} className="overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                                        <button type="button" onClick={() => toggleChildCart(child.student_id)} className="flex w-full items-center justify-between bg-slate-200 px-5 py-4 text-left hover:bg-slate-300">
                                            <span><span className="block font-semibold text-slate-900">{child.student.name}</span><span className="text-xs text-slate-600">{child.student.school.name} · {items.length} selection(s)</span></span>
                                            <span className="flex items-center gap-4"><span className="font-semibold text-indigo-700">RM {childTotal.toFixed(2)}</span><span className="text-xl text-slate-600">{expanded ? '−' : '+'}</span></span>
                                        </button>
                                        {expanded && (
                                            <div className="max-h-[52vh] overflow-auto p-3">
                                                <table className="w-full min-w-[800px] table-fixed border-collapse border border-slate-300">
                                                    <thead className="sticky top-0 z-20 shadow-sm"><tr><th className="border border-slate-400 bg-slate-200 px-3 py-3 text-left text-xs font-bold uppercase text-slate-800">Meal</th>{weekDays.map((day) => <th key={day} className="min-w-28 border border-slate-400 bg-slate-200 px-2 py-3 text-center text-xs font-bold uppercase text-slate-800">{dayName(day)}<span className="mt-0.5 block text-[10px] font-normal">{shortDate(day)}</span></th>)}</tr></thead>
                                                    <tbody>
                                                        {paginatedCartMeals.map((meal, mealIndex) => (
                                                            <tr key={meal.id} className={mealIndex % 2 ? 'bg-slate-50' : 'bg-white'}>
                                                                <td className="border border-slate-300 px-3 py-3"><button type="button" onClick={() => openMeal(meal)} className="font-medium text-indigo-700 hover:underline">{meal.name}</button><span className="block text-xs text-slate-500">RM {Number(meal.price).toFixed(2)}</span></td>
                                                                {weekDays.map((day) => {
                                                                    const item = items.find((entry) => entry.menu_item_id === meal.id && weekDays[new Date(entry.scheduled_time).getDay() === 0 ? 6 : new Date(entry.scheduled_time).getDay() - 1] === day);
                                                                    return <td key={day} className={`border border-slate-300 px-2 py-3 text-center ${item ? 'bg-emerald-50' : 'bg-slate-100'}`}>{item ? <div><button type="button" onClick={() => editCartItem(item)} className="rounded bg-emerald-600 px-3 py-1 text-sm font-semibold text-white hover:bg-emerald-700">Qty {item.quantity}</button><span className="mt-1 block text-xs text-slate-600">{new Date(item.scheduled_time).toLocaleDateString('en-MY')}</span><button type="button" onClick={() => removeItem(item)} className="mt-1 text-xs text-red-600 hover:underline">Remove</button></div> : <span className="text-sm font-medium text-slate-400">Not set</span>}</td>;
                                                                })}
                                                            </tr>
                                                        ))}
                                                        {meals.length === 0 && <tr><td colSpan="8" className="border border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No meals selected for this child.</td></tr>}
                                                    </tbody>
                                                </table>
                                                <TablePagination
                                                    totalItems={meals.length}
                                                    currentPage={cartPage}
                                                    onPageChange={(page) =>
                                                        setCartPages((current) => ({
                                                            ...current,
                                                            [child.student_id]: page,
                                                        }))
                                                    }
                                                />
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                        {cartItems.length > 0 && (
                            <div className="flex flex-col items-end gap-3 border-t p-5">
                                <p className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    Total: RM {total.toFixed(2)}
                                </p>
                                <button
                                    type="button"
                                    onClick={checkout}
                                    className="rounded-md bg-green-600 px-5 py-3 font-medium text-white hover:bg-green-700"
                                >
                                    Make payment
                                </button>
                                <p className="text-xs text-gray-500">
                                    You will be redirected to Stripe's secure checkout page.
                                </p>
                            </div>
                        )}
                    </section>
                    </Modal>
                </div>
            </div>

            <Modal
                show={selectedMeal !== null}
                maxWidth="2xl"
                onClose={closeMeal}
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
                                <h3 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {selectedMeal.name}
                                </h3>
                                <p className="text-sm text-gray-500">
                                    Menu item ID: {selectedMeal.id}
                                </p>
                                <p className="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                    {selectedMeal.description ||
                                        'No description provided.'}
                                </p>
                                <p className="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    RM {priceForMeal(selectedMeal).toFixed(2)}
                                </p>
                                <p className="mt-3 text-sm text-gray-500">
                                    Categories:{' '}
                                    {selectedMeal.menu_categories
                                        ?.map((category) => category.name)
                                        .join(', ') || '-'}
                                </p>
                                <p className="mt-2 text-sm font-medium text-slate-300">
                                    Collection time:{' '}
                                    {selectedMeal.available_start_time && selectedMeal.available_end_time
                                        ? `${selectedMeal.available_start_time.slice(0, 5)}–${selectedMeal.available_end_time.slice(0, 5)}`
                                        : 'Not specified'}
                                </p>
                            </div>
                        </div>
                        {selectedMeal.components?.length > 0 && <section className="mt-6 rounded-lg border border-slate-600 bg-slate-800 p-4 text-slate-100">
                            <div className="flex flex-wrap items-end justify-between gap-2">
                                <div><h4 className="font-semibold">Set meal portion and estimated nutrition</h4><p className="text-xs text-slate-400">Values shown for quantity {Number(selection.quantity) || 1}.</p></div>
                                <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Estimated values</span>
                            </div>
                            <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {selectedMeal.components?.map((component) => (
                                    <div key={component.id} className="rounded-md bg-slate-700 px-3 py-2 text-sm">
                                        <span className="font-semibold">{component.name}</span>
                                        <span className="ml-2 text-slate-300">{component.component_type} · {(Number(component.amount_g) * (Number(selection.quantity) || 1)).toFixed(0)} g</span>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-3 grid grid-cols-2 gap-2 text-center text-sm sm:grid-cols-3 lg:grid-cols-6">
                                {[
                                    ['Energy', selectedMeal.calories_kcal, 'kcal'],
                                    ['Protein', selectedMeal.protein_g, 'g'],
                                    ['Carbs', selectedMeal.carbohydrates_g, 'g'],
                                    ['Fat', selectedMeal.fat_g, 'g'],
                                    ['Fibre', selectedMeal.fibre_g, 'g'],
                                    ['Sodium', selectedMeal.sodium_mg, 'mg'],
                                ].map(([label, value, unit]) => <div key={label} className="rounded-md bg-slate-700 p-2"><span className="block text-xs text-slate-400">{label}</span><strong>{(Number(value || 0) * (Number(selection.quantity) || 1)).toFixed(1)} {unit}</strong></div>)}
                            </div>
                        </section>}
                        <div className="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                            {weekDays.map((day) => {
                                const active = selectedMeal.days.some(
                                    (mealDay) => mealDay.day_of_week === day,
                                );
                                return (
                                    <div
                                        key={day}
                                        className={`rounded-md border px-2 py-3 text-center text-sm font-medium ${active ? 'border-green-300 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-400'}`}
                                    >
                                        {dayName(day)}
                                        <span className="mt-1 block text-xs">
                                            {active
                                                ? 'Available'
                                                : 'Unavailable'}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                        <div className="mt-6 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-semibold text-slate-200">
                                    Child
                                </label>
                                <div className="mt-1 min-h-11 rounded-md border border-slate-500 bg-slate-700 px-3 py-2 text-sm text-white">
                                    <span className="block font-medium">{selectedChild?.student.name}</span>
                                    <span className="block text-xs text-slate-300">{selectedChild?.student.school?.name}</span>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-slate-200">
                                    Collection date
                                </label>
                                <div className="mt-1 flex min-h-11 items-center rounded-md border border-slate-500 bg-slate-700 px-3 py-2 text-sm font-medium text-white">
                                    {selection.date
                                        ? new Date(`${selection.date}T00:00:00`).toLocaleDateString('en-MY')
                                        : '-'}
                                </div>
                            </div>
                        </div>
                        <div className="mt-4">
                            <div>
                                <label className="block text-sm font-semibold text-slate-200">
                                    Quantity
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    max="20"
                                    value={selection.quantity}
                                    onChange={(event) =>
                                        setSelection({
                                            ...selection,
                                            quantity: event.target.value,
                                        })
                                    }
                                    className="mt-1 block w-full rounded-md border-gray-300 bg-white text-slate-900"
                                />
                            </div>
                        </div>
                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                onClick={closeMeal}
                                className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                onClick={saveSelection}
                                disabled={processing || !selection.date}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            >
                                {processing ? 'Saving...' : 'Update Cart'}
                            </button>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
