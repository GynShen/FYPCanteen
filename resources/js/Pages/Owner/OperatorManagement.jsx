import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import Modal from "@/Components/Modal";
import ManagementFilters from "@/Components/ManagementFilters";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import TablePagination from "@/Components/TablePagination";
import useTablePagination from "@/Hooks/useTablePagination";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { useState } from "react";

export default function OperatorManagement({ operators, schools, filters }) {
  const [editingId, setEditingId] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [passwordOperator, setPasswordOperator] = useState(null);
  const { currentPage, setCurrentPage, paginatedItems } =
    useTablePagination(operators);
  const form = useForm({
    name: "",
    username: "",
    phone_number: "",
    school_id: schools[0]?.id ?? "",
    password: "",
    password_confirmation: "",
  });
  const passwordForm = useForm({
    password: "",
    password_confirmation: "",
  });

  const clearForm = () => {
    setEditingId(null);
    form.reset();
    form.clearErrors();
  };
  const startEdit = (operator) => {
    setShowForm(true);
    setEditingId(operator.id);
    form.setData({
      name: operator.name,
      username: operator.username,
      phone_number: operator.phone_number,
      school_id: operator.school_id ?? "",
      password: "",
      password_confirmation: "",
    });
    form.clearErrors();
    window.setTimeout(
      () =>
        document
          .getElementById("operator-form")
          ?.scrollIntoView({ behavior: "smooth", block: "start" }),
      50,
    );
  };
  const submit = (event) => {
    event.preventDefault();
    if (editingId) {
      form.patch(route("owner.operators.update", { operator: editingId }), {
        preserveScroll: true,
        onSuccess: clearForm,
      });
      return;
    }
    form.post(route("owner.operators.store"), {
      preserveScroll: true,
      onSuccess: clearForm,
    });
  };
  const toggleStatus = (operator) => {
    const action = operator.is_active ? "deactivate" : "activate";
    if (
      !window.confirm(
        `${action.charAt(0).toUpperCase() + action.slice(1)} ${operator.name}?`,
      )
    )
      return;
    router.patch(
      route("owner.operators.status", { operator: operator.id }),
      {},
      { preserveScroll: true },
    );
  };
  const openPassword = (operator) => {
    passwordForm.reset();
    passwordForm.clearErrors();
    setPasswordOperator(operator);
  };
  const closePassword = () => {
    setPasswordOperator(null);
    passwordForm.reset();
    passwordForm.clearErrors();
  };
  const resetPassword = (event) => {
    event.preventDefault();
    passwordForm.patch(
      route("owner.operators.password", { operator: passwordOperator.id }),
      {
        preserveScroll: true,
        onSuccess: closePassword,
      },
    );
  };
  const remove = (operator) => {
    if (
      !window.confirm(
        `Permanently delete operator ${operator.name}? This cannot be undone.`,
      )
    )
      return;
    router.delete(route("owner.operators.destroy", { operator: operator.id }), {
      preserveScroll: true,
    });
  };

  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
          Operator Management
        </h2>
      }
    >
      <Head title="Operator Management" />
      <div className="py-12">
        <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
          <ManagementFilters
            routeName="owner.operators"
            filters={filters}
            fields={[
              {
                name: "search",
                label: "Search",
                placeholder: "ID, name, username or phone",
              },
              {
                name: "school_id",
                label: "School",
                type: "select",
                options: schools.map((school) => ({
                  value: school.id,
                  label: school.name,
                })),
              },
              {
                name: "status",
                label: "Status",
                type: "select",
                options: [
                  { value: "active", label: "Active" },
                  { value: "inactive", label: "Inactive" },
                ],
              },
            ]}
          />
          <button
            type="button"
            onClick={() => setShowForm((current) => !current)}
            className="flex w-full items-center justify-between rounded-lg bg-white px-6 py-4 text-left font-bold text-slate-900 shadow dark:bg-gray-800 dark:text-white"
          >
            <span>
              {editingId ? `Edit Operator #${editingId}` : "Add Operator"}
            </span>
            <span className="text-xl">{showForm ? "⌃" : "⌄"}</span>
          </button>
          <section
            id="operator-form"
            className={`${showForm ? "!mt-0 block rounded-t-none" : "hidden"} scroll-mt-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800`}
          >
            <h3 className="text-lg font-bold text-gray-900 dark:text-white">
              {editingId
                ? `Edit Operator #${editingId}`
                : "Create Operator Account"}
            </h3>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Operators sign in with the username and password assigned by the
              owner.
            </p>
            <form onSubmit={submit} className="mt-6 grid gap-5 md:grid-cols-2">
              <div>
                <InputLabel htmlFor="operator-name" value="Name" />
                <TextInput
                  id="operator-name"
                  value={form.data.name}
                  onChange={(event) => form.setData("name", event.target.value)}
                  className="mt-1 block w-full"
                  required
                />
                <InputError message={form.errors.name} className="mt-2" />
              </div>
              <div>
                <InputLabel htmlFor="operator-username" value="Login Username" />
                <TextInput
                  id="operator-username"
                  value={form.data.username}
                  onChange={(event) =>
                    form.setData("username", event.target.value)
                  }
                  className="mt-1 block w-full"
                  autoComplete="off"
                  required
                />
                <InputError message={form.errors.username} className="mt-2" />
              </div>
              <div>
                <InputLabel htmlFor="operator-phone" value="Phone Number" />
                <TextInput
                  id="operator-phone"
                  value={form.data.phone_number}
                  onChange={(event) =>
                    form.setData("phone_number", event.target.value)
                  }
                  className="mt-1 block w-full"
                  required
                />
                <InputError
                  message={form.errors.phone_number}
                  className="mt-2"
                />
              </div>
              <div>
                <InputLabel htmlFor="operator-school" value="Assigned School" />
                <select
                  id="operator-school"
                  value={form.data.school_id}
                  onChange={(event) =>
                    form.setData("school_id", event.target.value)
                  }
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                >
                  <option value="">Select a school</option>
                  {schools.map((school) => (
                    <option key={school.id} value={school.id}>
                      {school.name}
                    </option>
                  ))}
                </select>
                <InputError message={form.errors.school_id} className="mt-2" />
              </div>
              {!editingId && (
                <>
                  <div>
                    <InputLabel htmlFor="operator-password" value="Password" />
                    <TextInput
                      id="operator-password"
                      type="password"
                      value={form.data.password}
                      onChange={(event) =>
                        form.setData("password", event.target.value)
                      }
                      className="mt-1 block w-full"
                      required
                    />
                    <InputError
                      message={form.errors.password}
                      className="mt-2"
                    />
                  </div>
                  <div>
                    <InputLabel
                      htmlFor="operator-password-confirmation"
                      value="Confirm Password"
                    />
                    <TextInput
                      id="operator-password-confirmation"
                      type="password"
                      value={form.data.password_confirmation}
                      onChange={(event) =>
                        form.setData(
                          "password_confirmation",
                          event.target.value,
                        )
                      }
                      className="mt-1 block w-full"
                      required
                    />
                  </div>
                </>
              )}
              <div className="flex items-center gap-3 md:col-span-2">
                <PrimaryButton disabled={form.processing}>
                  {form.processing
                    ? "Saving..."
                    : editingId
                      ? "Update Operator"
                      : "Create Operator"}
                </PrimaryButton>
                {editingId && (
                  <button
                    type="button"
                    onClick={clearForm}
                    className="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700"
                  >
                    Cancel
                  </button>
                )}
              </div>
            </form>
          </section>

          <section className="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-slate-100">
                  <tr>
                    {[
                      "ID",
                      "Name",
                      "Username",
                      "Phone",
                      "School",
                      "Status",
                      "Created",
                      "Actions",
                    ].map((heading) => (
                      <th
                        key={heading}
                        className="px-4 py-3 text-center text-xs font-bold uppercase text-slate-700"
                      >
                        {heading}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {paginatedItems.map((operator) => (
                    <tr
                      key={operator.id}
                      className="text-slate-800 even:bg-slate-50"
                    >
                      <td className="px-4 py-3 text-center text-sm font-semibold">
                        {operator.id}
                      </td>
                      <td className="px-4 py-3 text-center text-sm font-medium">
                        {operator.name}
                      </td>
                      <td className="px-4 py-3 text-center text-sm font-semibold text-indigo-700">
                        {operator.username}
                      </td>
                      <td className="px-4 py-3 text-center text-sm">
                        {operator.phone_number}
                      </td>
                      <td className="px-4 py-3 text-center text-sm">
                        {operator.school?.name ?? "Not assigned"}
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-bold ${operator.is_active ? "bg-emerald-100 text-emerald-800" : "bg-red-100 text-red-800"}`}
                        >
                          {operator.is_active ? "Active" : "Inactive"}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-center text-sm">
                        {new Date(operator.created_at).toLocaleString("en-MY")}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex flex-wrap justify-center gap-2">
                          <button
                            type="button"
                            onClick={() => startEdit(operator)}
                            className="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            onClick={() => toggleStatus(operator)}
                            className={`rounded px-3 py-2 text-xs font-semibold text-white ${operator.is_active ? "bg-amber-600" : "bg-emerald-600"}`}
                          >
                            {operator.is_active ? "Deactivate" : "Activate"}
                          </button>
                          <button
                            type="button"
                            onClick={() => openPassword(operator)}
                            className="rounded px-3 py-2 text-xs font-semibold"
                            style={{
                              backgroundColor: "#475569",
                              color: "#ffffff",
                            }}
                          >
                            Reset Password
                          </button>
                          <button
                            type="button"
                            onClick={() => remove(operator)}
                            className="rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white"
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                  {operators.length === 0 && (
                    <tr>
                      <td
                        colSpan="8"
                        className="px-4 py-10 text-center text-sm text-gray-500"
                      >
                        No operator accounts have been created.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
            <TablePagination
              totalItems={operators.length}
              currentPage={currentPage}
              onPageChange={setCurrentPage}
            />
          </section>
        </div>
      </div>

      <Modal
        show={passwordOperator !== null}
        maxWidth="lg"
        onClose={closePassword}
      >
        {passwordOperator && (
          <form
            onSubmit={resetPassword}
            className="bg-white text-slate-900"
            style={{ padding: "24px" }}
          >
            <div>
              <h3 className="text-xl font-bold text-slate-900">Set Password</h3>
              <p className="mt-1 text-sm text-slate-600">
                Assign a new sign-in password for{" "}
                <strong>{passwordOperator.name}</strong>.
              </p>
            </div>
            <div className="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
              The operator will be signed out from all existing sessions and
              must use this new password.
            </div>
            <div className="mt-4">
              <InputLabel
                htmlFor="reset-password"
                value="Password"
                style={{ color: "#334155" }}
              />
              <TextInput
                id="reset-password"
                type="password"
                value={passwordForm.data.password}
                onChange={(event) =>
                  passwordForm.setData("password", event.target.value)
                }
                className="mt-1 block w-full"
                style={{
                  backgroundColor: "#ffffff",
                  borderColor: "#cbd5e1",
                  color: "#0f172a",
                }}
                autoComplete="new-password"
                required
              />
              <InputError
                message={passwordForm.errors.password}
                className="mt-2"
              />
            </div>
            <div className="mt-4">
              <InputLabel
                htmlFor="reset-password-confirmation"
                value="Confirm Password"
                style={{ color: "#334155" }}
              />
              <TextInput
                id="reset-password-confirmation"
                type="password"
                value={passwordForm.data.password_confirmation}
                onChange={(event) =>
                  passwordForm.setData(
                    "password_confirmation",
                    event.target.value,
                  )
                }
                className="mt-1 block w-full"
                style={{
                  backgroundColor: "#ffffff",
                  borderColor: "#cbd5e1",
                  color: "#0f172a",
                }}
                autoComplete="new-password"
                required
              />
            </div>
            <div className="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
              <button
                type="button"
                onClick={closePassword}
                className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={passwordForm.processing}
                className="rounded-md px-5 py-2 text-sm font-semibold shadow-sm disabled:opacity-50"
                style={{ backgroundColor: "#4f46e5", color: "#ffffff" }}
              >
                {passwordForm.processing ? "Saving..." : "Save New Password"}
              </button>
            </div>
          </form>
        )}
      </Modal>
    </AuthenticatedLayout>
  );
}
