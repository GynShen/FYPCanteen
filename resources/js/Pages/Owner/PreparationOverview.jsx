import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router } from "@inertiajs/react";
import { useEffect, useState } from "react";

export default function PreparationOverview({
  schools,
  filters,
  batches,
  summary,
}) {
  const [form, setForm] = useState({
    ...filters,
    school_id: filters.school_id ?? "",
  });
  const update = (name, value) =>
    setForm((current) => ({ ...current, [name]: value }));
  const updateRange = (name, value) =>
    setForm((current) => ({ ...current, period: "custom", [name]: value }));
  useEffect(() => {
    if (!form.start_date || !form.end_date || form.end_date < form.start_date)
      return undefined;
    const timer = window.setTimeout(
      () =>
        router.get(route("owner.preparation-overview"), form, {
          preserveState: true,
          preserveScroll: true,
          replace: true,
          only: ["batches", "summary", "filters"],
        }),
      500,
    );
    return () => window.clearTimeout(timer);
  }, [form.period, form.school_id, form.start_date, form.end_date]);
  useEffect(() => {
    if (form.period === "custom") return;
    setForm((current) => ({
      ...current,
      start_date: filters.start_date,
      end_date: filters.end_date,
    }));
  }, [filters.start_date, filters.end_date]);

  return (
    <AuthenticatedLayout
      header={
        <h2 className="text-xl font-semibold text-white">
          Preparation Overview
        </h2>
      }
    >
      <Head title="Preparation Overview" />
      <div className="py-8">
        <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
          <section className="rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
                    <div className="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <label className="text-sm font-semibold text-slate-200">
                Period
                <select
                  value={form.period}
                  onChange={(event) => update("period", event.target.value)}
                  className="mt-1 block w-full rounded-md bg-white text-slate-900"
                >
                  <option value="this">This Week</option>
                  <option value="next">Next Week</option>
                  <option value="custom">Custom</option>
                </select>
              </label>
              <label className="text-sm font-semibold text-slate-200">
                School
                <select
                  value={form.school_id}
                  onChange={(event) => update("school_id", event.target.value)}
                  className="mt-1 block w-full rounded-md bg-white text-slate-900"
                >
                  <option value="">All schools</option>
                  {schools.map((school) => (
                    <option key={school.id} value={school.id}>
                      {school.name}
                    </option>
                  ))}
                </select>
              </label>
              <label className="text-sm font-semibold text-slate-200">
                Start date
                <input
                  type="date"
                  value={form.start_date}
                  max={form.end_date || undefined}
                  onChange={(event) =>
                    updateRange("start_date", event.target.value)
                  }
                  className="mt-1 block w-full rounded-md bg-white text-slate-900"
                />
              </label>
              <label className="text-sm font-semibold text-slate-200">
                End date
                <input
                  type="date"
                  value={form.end_date}
                  min={form.start_date || undefined}
                  onChange={(event) =>
                    updateRange("end_date", event.target.value)
                  }
                  className="mt-1 block w-full rounded-md bg-white text-slate-900"
                />
              </label>
            </div>
          </section>
          <div className="grid gap-4 sm:grid-cols-3">
            {[
              ["Portions", summary.portions, "text-indigo-300"],
              ["Meal batches", summary.batches, "text-cyan-300"],
              ["Prepared", summary.prepared, "text-emerald-300"],
            ].map(([label, value, color]) => (
              <div
                key={label}
                className="rounded-2xl border border-slate-700 bg-slate-800 p-5 text-center"
              >
                <p className="text-sm font-semibold text-slate-300">{label}</p>
                <p className={`mt-2 text-3xl font-bold ${color}`}>{value}</p>
              </div>
            ))}
          </div>
          <section className="overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 shadow-lg">
            <div className="max-h-[65vh] overflow-auto">
              <table className="w-full min-w-[1050px]">
                <thead className="sticky top-0 z-10 bg-slate-700 text-xs uppercase text-slate-200">
                  <tr>
                    <th className="px-5 py-3 text-left">School</th>
                    <th className="px-5 py-3 text-left">Date</th>
                    <th className="px-5 py-3 text-left">Collection time</th>
                    <th className="px-5 py-3 text-left">Meal</th>
                    <th>Small</th>
                    <th>Medium</th>
                    <th>Large</th>
                    <th>Total</th>
                    <th>Preparation</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-700">
                  {batches.map((batch) => (
                    <tr key={batch.key} className="text-sm text-slate-200">
                      <td className="px-5 py-4 font-bold text-white">
                        {batch.school}
                      </td>
                      <td className="px-5 py-4">
                        {batch.day}
                        <br />
                        <span className="text-xs text-slate-400">
                          {batch.date}
                        </span>
                      </td>
                      <td className="px-5 py-4">{batch.time}</td>
                      <td className="px-5 py-4 font-semibold text-white">
                        {batch.meal}
                      </td>
                      <td className="text-center">{batch.S}</td>
                      <td className="text-center">{batch.M}</td>
                      <td className="text-center">{batch.L}</td>
                      <td className="text-center font-bold text-indigo-300">
                        {batch.total}
                      </td>
                      <td className="px-5 py-4 text-center">
                        <span
                          className={`rounded-full border px-3 py-1 text-xs font-bold ${batch.prepared ? "border-emerald-500 bg-emerald-500/15 text-emerald-300" : "border-amber-500 bg-amber-500/15 text-amber-300"}`}
                        >
                          {batch.prepared ? "Prepared" : "Not prepared"}
                        </span>
                      </td>
                    </tr>
                  ))}
                  {!batches.length && (
                    <tr>
                      <td
                        colSpan="9"
                        className="px-5 py-12 text-center text-slate-400"
                      >
                        No paid meals require preparation in this period.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
