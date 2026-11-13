import { Head, useForm } from '@inertiajs/react';
import Logo from '../../Components/Logo';

export default function ResetPassword({ token, email }) {
    const form = useForm({ token, email: email || '', password: '', password_confirmation: '' });
    return <><Head title="Atur Kata Sandi Baru"/><div className="grid min-h-screen place-items-center bg-[#f5f8fd] p-5"><div className="w-full max-w-md"><Logo/><form className="card mt-7 p-8" onSubmit={(event) => { event.preventDefault(); form.post('/reset-password'); }}><h1 className="text-2xl font-extrabold">Kata sandi baru</h1><Field label="Email" error={form.errors.email}><input type="email" className="field" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)}/></Field><Field label="Kata sandi baru" error={form.errors.password}><input type="password" className="field" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)}/></Field><Field label="Konfirmasi kata sandi"><input type="password" className="field" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)}/></Field><button disabled={form.processing} className="btn-primary mt-6 w-full">Simpan Kata Sandi</button></form></div></div></>;
}

function Field({ label, error, children }) { return <div className="mt-5"><label className="label">{label}</label>{children}{error && <p className="mt-2 text-xs text-red-600">{error}</p>}</div>; }
