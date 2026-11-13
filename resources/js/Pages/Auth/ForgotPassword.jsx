import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Mail } from 'lucide-react';
import Logo from '../../Components/Logo';

export default function ForgotPassword() {
    const form = useForm({ email: '' });
    return <><Head title="Lupa Kata Sandi"/><div className="grid min-h-screen place-items-center bg-[#f5f8fd] p-5"><div className="w-full max-w-md"><Logo/><div className="card mt-7 p-8"><h1 className="text-2xl font-extrabold">Reset kata sandi</h1><p className="mt-2 text-sm leading-6 text-slate-500">Masukkan email akun. Tautan reset akan dikirim jika akun ditemukan.</p><form className="mt-6" onSubmit={(event) => { event.preventDefault(); form.post('/forgot-password'); }}><label className="label">Email</label><div className="relative"><Mail className="absolute left-4 top-3.5 text-slate-300" size={18}/><input type="email" className="field pl-11" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoFocus/></div>{form.errors.email && <p className="mt-2 text-xs text-red-600">{form.errors.email}</p>}<button disabled={form.processing} className="btn-primary mt-5 w-full">Kirim Tautan Reset</button></form><Link href="/login" className="mt-5 flex items-center justify-center gap-2 text-sm font-semibold text-blue-700"><ArrowLeft size={16}/>Kembali ke login</Link></div></div></div></>;
}
