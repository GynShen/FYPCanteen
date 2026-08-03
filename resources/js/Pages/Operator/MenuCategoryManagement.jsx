import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function MenuCategoriesManagement({ menuCategories }) {
  const [editingId, setEditingId] = useState(null);

  const { data, setData, post, patch, delete: destroy, processing, errors, reset, clearErrors } = useForm({
    name: '',
  });

  const isEditingDeletedMenuCategories = editingId !== null && !menuCategories.some((menuCategories) => menuCategories.id === editingId);

  const startEdit = (menuCategories) => {
    clearErrors();
    setEditingId(menuCategories.id);
    setData({
      name: menuCategories.name,
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
      patch(route('operator.menuCategory.update', { menuCategory: editingId }), {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
        onError: () => {
          cancelEdit();
          window.alert('This menu category has been deleted or no longer exists. Please refresh the page.');
        },
      });

      return;
    }

    post(route('operator.menuCategory.store'), {
      preserveScroll: true,
      onSuccess: () => {
        clearErrors();
        reset();
      },
    });
  };

  const remove = (id) => {
    const confirmed = window.confirm('Are you sure you want to delete this menu category?');

    if (!confirmed) {
      return;
    }

    destroy(route('operator.menuCategory.destroy', { menuCategory: id }), {
      preserveScroll: true,
    });
  };

  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Menu Categories Management
        </h2>
      }
    >
      <Head title="Menu Categories Management" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
          <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
            <form onSubmit={submit} className="space-y-4">
              <div>
                <InputLabel htmlFor="name" value="Menu Category Name" className="text-gray-800 dark:text-gray-200" />
                <div className="relative mt-1">
                  <TextInput
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1 block w-full text-gray-900 pr-10"
                    required
                  />
                  {data.name && (
                    <button
                      type="button"
                      onClick={() => clearField('name')}
                      className="absolute inset-y-0 right-2 flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                      Clear
                    </button>
                  )}
                </div>
                <InputError message={errors.name} className="mt-2" />
              </div>

              <div className="space-y-3">
                {editingId && (
                  <p className="text-sm font-medium text-gray-800 dark:text-gray-200">
                    Editing menu category ID: {editingId}
                  </p>
                )}

                {isEditingDeletedMenuCategories && (
                  <p className="text-sm font-medium text-red-600">
                    This menu category has been deleted or no longer exists.
                  </p>
                )}

                <div className="flex items-center gap-4">
                  <PrimaryButton disabled={processing}>
                    {editingId ? 'Update Menu Category' : 'Add Menu Category'}
                  </PrimaryButton>

                  {editingId && (
                    <button
                      type="button"
                      onClick={cancelEdit}
                      className="text-sm text-gray-600 rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                    >
                      Cancel
                    </button>
                  )}
                </div>
              </div>
            </form>
          </div>

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
                      Operation
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {menuCategories.map((menuCategories) => (
                    <tr key={menuCategories.id}>
                      <td className="px-6 py-4 text-center text-sm text-gray-900">
                        {menuCategories.id}
                      </td>
                      <td className="px-6 py-4 text-center text-sm text-gray-900">
                        {menuCategories.name}
                      </td>
                      <td className="px-6 py-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <button
                            type="button"
                            onClick={() => startEdit(menuCategories)}
                            className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            onClick={() => remove(menuCategories.id)}
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
