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
  });

  const isEditingDeletedSchool = editingId !== null && !schools.some((school) => school.id === editingId);

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

  const clearField = (field) => {
    setData(field, '');
  };
}