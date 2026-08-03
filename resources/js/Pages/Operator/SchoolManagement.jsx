import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function SchoolManagement({ schools }) {
    const [schoolName, setSchoolName] = useState('');

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
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                      <div className="space-y-4">
                        {schools.map((school) => (
                          <div key={school.id} className="rounded border p-4">
                            <div className="flex items-center justify-between gap-4">
                              <div className="flex items-start gap-4">
                                <div>
                                  <h3 className="text-lg font-semibold">{school.school_name}</h3>
                                  <p>{school.school_address}</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
