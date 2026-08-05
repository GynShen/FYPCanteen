import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function MenuManagement({ menuItems, categories }) {
  const [editingId, setEditingId] = useState(null);

  const { data, setData, post, patch, delete: destroy, processing, errors, reset, clearErrors } = useForm({
    menu_category_id: categories[0]?.id ?? '',
    name: '',
    description: '',
    day_of_week: '',
    price: '',
    image_url: null,
    is_available: true,
  });

  const isEditingDeletedMenuItem = editingId !== null && !menuItems.some((item) => item.id === editingId);

  const startEdit = (item) => {
    clearErrors();
    setEditingId(item.id);
    setData({
      name: item.name,
      description: item.description,
      day_of_week: item.day_of_week,
      price: item.price,
      image_url: item.image_url,
      is_available: item.is_available,
    });
  };

  const cancelEdit = () => {
    clearErrors();
    setEditingId(null);
    reset();
  };

  const clearField = (field) => {
    setData(field, '');
  };

  const submit = (e) => {
    e.preventDefault();
    clearErrors();

    if (editingId) {
      patch(route('operator.menu.update', { menuItem: editingId }), {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
        onError: () => {
          cancelEdit();
          window.alert('This menu item has been deleted or no longer exists. Please refresh the page.');
        },
      });

      return;
    }

    post(route('operator.menu.store'), {
      preserveScroll: true,
      onSuccess: () => {
        clearErrors();
        reset();
      },
    });
  };

  const remove = (id) => {
    const confirmed = window.confirm('Are you sure you want to delete this menu item?');

    if (!confirmed) {
      return;
    }

    destroy(route('operator.menu.destroy', { menu: id }), {
      preserveScroll: true,
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
          <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 text-left">
                <thead className="bg-slate-100">
                  <tr>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                      ID
                    </th>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                      Name
                    </th>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                      Description
                    </th>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                      Day of Week
                    </th>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                      Price
                    </th>
                    <th className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                      Operation
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {menuItems.map((item) => (
                    <tr key={item.id}>
                      <td className="px-6 py-4 text-center text-sm text-gray-900">
                        {item.id}
                      </td>
                      <td className="px-6 py-4 text-center text-sm text-gray-900">
                        {item.image_url && (
                          <img
                            src={`/storage/app/public/${item.image_url}`}
                            alt={item.name}
                            className="h-20 w-20 rounded object-cover"
                          />
                        )}
                        <p>{item.name}</p>
                      </td>
                      <td className="px-6 py-4 text-center text-sm text-gray-900">
                        {item.description}
                      </td>
                      <td className="px-6 py-4 text-center text-sm text-gray-900">
                        {item.day_of_week}
                      </td>
                      <td className="px-6 py-4 text-center text-sm text-gray-900">
                        {item.price}
                      </td>
                      <td className="px-6 py-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <button
                            type="button"
                            onClick={() => startEdit(item)}
                            className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            onClick={() => remove(item.id)}
                            className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}