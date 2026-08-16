import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const auth = usePage().props.auth;
    const user = auth.user;

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav className="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Dashboard
                                </NavLink>
                                {user.role === 'owner' && (
                                    <>
                                        <NavLink
                                            href={route('owner.school')}
                                            active={route().current(
                                                'owner.school',
                                            )}
                                        >
                                            School
                                        </NavLink>
                                        <NavLink
                                            href={route('owner.operators')}
                                            active={route().current(
                                                'owner.operators',
                                            )}
                                        >
                                            Operator
                                        </NavLink>
                                        <NavLink
                                            href={route(
                                                'owner.menuCategory',
                                            )}
                                            active={route().current(
                                                'owner.menuCategory',
                                            )}
                                        >
                                            Menu Category
                                        </NavLink>
                                        <NavLink
                                            href={route('owner.menu')}
                                            active={route().current(
                                                'owner.menu',
                                            )}
                                        >
                                            Menu
                                        </NavLink>
                                        <NavLink href={route('weekly-menu.index')} active={route().current('weekly-menu.*')}>
                                            Weekly Menu
                                        </NavLink>
                                    </>
                                )}
                                {user.role === 'customer' && (
                                    <>
                                        <NavLink
                                            href={route('customer.children')}
                                            active={route().current(
                                                'customer.children',
                                            )}
                                        >
                                            Children
                                        </NavLink>
                                        <NavLink
                                            href={route('customer.menu')}
                                            active={route().current(
                                                'customer.menu',
                                            )}
                                        >
                                            Menu
                                        </NavLink>
                                        <NavLink
                                            href={route('customer.orders')}
                                            active={route().current(
                                                'customer.orders',
                                            )}
                                        >
                                            Orders
                                        </NavLink>
                                    </>
                                )}
                                {user.role === 'operator' && (<>
                                    <NavLink href={route('owner.student')} active={route().current('owner.student*')}>Student</NavLink>
                                    <NavLink href={route('weekly-menu.index')} active={route().current('weekly-menu.*')}>Weekly Menu</NavLink>
                                    <NavLink href={route('operator.preparation-plan')} active={route().current('operator.preparation-plan')}>Preparation Plan</NavLink>
                                    <NavLink href={route('meal-collection.index')} active={route().current('meal-collection.*')}>Meal Collection</NavLink>
                                </>)}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            {user.role === 'customer' && (
                                <Link
                                    href={route('customer.notifications')}
                                    className="relative me-2 inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                                    aria-label={`Notifications${auth.unread_notifications_count > 0 ? `, ${auth.unread_notifications_count} unread` : ''}`}
                                    title="Notifications"
                                >
                                    <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 00-12 0v.75a8.967 8.967 0 01-2.312 6.022 23.848 23.848 0 005.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    {auth.unread_notifications_count > 0 && (
                                        <span className="absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-800">
                                            {auth.unread_notifications_count > 99 ? '99+' : auth.unread_notifications_count}
                                        </span>
                                    )}
                                </Link>
                            )}
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            {user.role === 'customer' && (
                                <Link
                                    href={route('customer.notifications')}
                                    className="relative me-2 inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                                    aria-label={`Notifications${auth.unread_notifications_count > 0 ? `, ${auth.unread_notifications_count} unread` : ''}`}
                                >
                                    <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 00-12 0v.75a8.967 8.967 0 01-2.312 6.022 23.848 23.848 0 005.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    {auth.unread_notifications_count > 0 && (
                                        <span className="absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-800">
                                            {auth.unread_notifications_count > 99 ? '99+' : auth.unread_notifications_count}
                                        </span>
                                    )}
                                </Link>
                            )}
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800 dark:text-gray-200">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            {user.role === 'owner' && (
                                <>
                                    <ResponsiveNavLink
                                        href={route('owner.school')}
                                    >
                                        School
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink
                                        href={route('owner.operators')}
                                    >
                                        Operator
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        href={route('owner.menuCategory')}
                                    >
                                        Menu Category
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink
                                        href={route('owner.menu')}
                                    >
                                        Menu
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink href={route('weekly-menu.index')}>
                                        Weekly Menu
                                    </ResponsiveNavLink>
                                </>
                            )}
                            {user.role === 'customer' && (
                                <>
                                    <ResponsiveNavLink
                                        href={route('customer.children')}
                                    >
                                        Children
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink
                                        href={route('customer.menu')}
                                    >
                                        Menu
                                    </ResponsiveNavLink>
                                    <ResponsiveNavLink
                                        href={route('customer.orders')}
                                    >
                                        Orders
                                    </ResponsiveNavLink>
                                </>
                            )}
                            {user.role === 'operator' && (<>
                                <ResponsiveNavLink href={route('owner.student')}>Student</ResponsiveNavLink>
                                <ResponsiveNavLink href={route('weekly-menu.index')}>Weekly Menu</ResponsiveNavLink>
                                <ResponsiveNavLink href={route('operator.preparation-plan')}>Preparation Plan</ResponsiveNavLink>
                                <ResponsiveNavLink href={route('meal-collection.index')}>Meal Collection</ResponsiveNavLink>
                            </>)}
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow dark:bg-gray-800">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
