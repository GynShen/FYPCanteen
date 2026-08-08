import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeactivateAccountForm({ className = '' }) {
    const [confirming, setConfirming] = useState(false);
    const passwordInput = useRef();
    const form = useForm({ password: '' });

    const closeModal = () => {
        setConfirming(false);
        form.clearErrors();
        form.reset();
    };

    const deactivate = (event) => {
        event.preventDefault();
        form.patch(route('profile.deactivate'), {
            preserveScroll: true,
            onError: () => passwordInput.current?.focus(),
            onFinish: () => form.reset(),
        });
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Deactivate Account</h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Your account and its historical data will be retained, but you will be signed out and unable to log in.
                </p>
            </header>

            <DangerButton onClick={() => setConfirming(true)}>Deactivate Account</DangerButton>

            <Modal show={confirming} onClose={closeModal}>
                <form onSubmit={deactivate} className="bg-white p-6 text-slate-900">
                    <h2 className="text-lg font-semibold text-slate-900">Deactivate your account?</h2>
                    <p className="mt-2 text-sm text-slate-600">Enter your password to confirm. Your records will not be deleted.</p>
                    <div className="mt-5">
                        <InputLabel htmlFor="deactivate-password" value="Password" className="text-slate-700" />
                        <TextInput id="deactivate-password" ref={passwordInput} type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} className="mt-1 block w-full text-slate-900" autoComplete="current-password" required />
                        <InputError message={form.errors.password} className="mt-2" />
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={closeModal}>Cancel</SecondaryButton>
                        <DangerButton disabled={form.processing}>Deactivate Account</DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
