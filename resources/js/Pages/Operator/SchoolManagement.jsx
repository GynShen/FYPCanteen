import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function SchoolManagement({ schools }) {
  const [editingId, setEditingId] = useState(null);

  const defaultValues = {
    school_name: '',
    address: '',
  }

  const { data, setData, delete: destroy, processing, errors, reset, clearErrors } = useForm(defaultValues);

  const startEdit = (item) => {
    clearErrors();
    setEditingId(item.id);
    setData({
      school_name: item.school_name,
      address: item.address,
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

    const formData = new FormData();
    formData.append('school_name', data.school_name);
    formData.append('address', data.address);

    if (editingId) {
      formData.append('_method', 'PATCH');

      router.post(route('operator.school.update', { school: editingId }), formData, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
      });

      return;
    }

    router.post(route('operator.school.store'), formData, {
      preserveScroll: true,
      onSuccess: () => {
        clearErrors();
        reset();
      },
    });
  };

  const remove = (id) => {
    destroy(route('operator.school.destroy', { school: id }), {
      preserveScroll: true,
    });
  };

  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800">
          School Management
        </h2>
      }
    >
      <Head title="School Management" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow sm:rounded-lg">
            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
              <form onSubmit={submit} className="space-y-4">
                <div>
                  <InputLabel htmlFor="school_name" value="School Name" />
                  <TextInput
                    id="school_name"
                    value={data.school_name}
                    onChange={(e) =>
                      setData(
                        'school_name',
                        e.target.value,
                      )
                    }
                    className="mt-1 block w-full"
                    required
                  />
                  <InputError
                    message={errors.school_name}
                    className="mt-2"
                  />
                </div>

                <div>
                  <InputLabel
                    htmlFor="address"
                    value="Address"
                  />
                  <TextInput
                    id="address"
                    value={data.address}
                    onChange={(e) =>
                      setData(
                        'address',
                        e.target.value,
                      )
                    }
                    className="mt-1 block w-full"
                  />
                  <InputError
                    message={errors.address}
                    className="mt-2"
                  />
                </div>
                <div className="flex items-center gap-4">
                  <PrimaryButton disabled={processing}>
                    {editingId
                      ? 'Update Menu Item'
                      : 'Add Menu Item'}
                  </PrimaryButton>

                  {editingId && (
                    <button
                      type="button"
                      onClick={cancelEdit}
                      className="text-sm text-gray-600 underline"
                    >
                      Cancel
                    </button>
                  )}
                </div>
              </form>

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
                        Created At (DD/MM/YYYY,
                        HH:mm:ss)
                      </th>
                      <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        Updated At (DD/MM/YYYY,
                        HH:mm:ss)
                      </th>
                      <th className="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        Operations
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
                          {new Date(
                            school.created_at,
                          ).toLocaleString('en-SG')}
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-900">
                          {new Date(
                            school.updated_at,
                          ).toLocaleString('en-SG')}
                        </td>
                        <td className="px-6 py-4">
                          <div className="flex items-center gap-2">
                            <button
                              type="button"
                              className="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                            >
                              Edit
                            </button>
                            <button
                              type="button"
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
      </div>
    </AuthenticatedLayout>
  );
}
