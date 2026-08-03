import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function SchoolManagement({ schools }) {
  const [editingId, setEditingId] = useState(null);

  const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
    name: '',
    address: '',
  });

  const startEdit = (school) => {
    clearErrors();
    setEditingId(school.id);
    setData({
      name: school.name,
      address: school.address,
    });
  };

  const cancelEdit = () => {
    clearErrors();
    setEditingId(null);
    reset();
  };

  const submit = (e) => {
    e.preventDefault();
    clearErrors();

    if (editingId) {
      patch(route('operator.school.update', { school: editingId }), {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
      });

      return;
    }

    post(route('operator.school.store'), {
      preserveScroll: true,
      onSuccess: () => {
        clearErrors();
        reset();
      },
    });
  };

  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-white">
          School Management
        </h2>
      }
    >
      <Head title="School Management" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow sm:rounded-lg">
            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
              <form onSubmit={submit} className="space-y-4 ">
                <div>
                  <InputLabel htmlFor="name" value="School Name" className="text-gray-900 dark:text-gray-900" />
                  <TextInput
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1 block w-full text-gray-900"
                    required
                  />
                  <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                  <InputLabel htmlFor="address" value="Address" className="text-gray-900 dark:text-gray-900" />
                  <TextInput
                    id="address"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    className="mt-1 block w-full text-gray-900"
                    required
                  />
                  <InputError message={errors.address} className="mt-2" />
                </div>

                <div className="flex items-center gap-4">
                  <PrimaryButton disabled={processing}>
                    {editingId ? 'Update School' : 'Add School'}
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
              </form>
            </div>
          </div>

          <div className="mt-6 overflow-hidden bg-white shadow sm:rounded-lg">
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 text-left">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                      Name
                    </th>
                    <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                      Address
                    </th>
                    <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                      Created At
                    </th>
                    <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                      Updated At
                    </th>
                    <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                      Operation
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {schools.map((school) => (
                    <tr key={school.id}>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {school.name}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {school.address}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {new Date(school.created_at).toLocaleString('en-SG')}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {new Date(school.updated_at).toLocaleString('en-SG')}
                      </td>
                      <td className="px-6 py-4">
                          <div className="flex items-center gap-2">
                            <button
                              type="button"
                              onClick={() => startEdit(school)}
                              className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                            >
                              Edit
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
