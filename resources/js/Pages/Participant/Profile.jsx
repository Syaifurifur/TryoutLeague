import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Profile({ participant, user }) {
    const form = useForm({ name: user.name, phone: user.phone || '', grade: participant.grade || '', province: participant.province || '', city: participant.city || '' });
    return <AppLayout title="Profil" subtitle="Perbarui data peserta"><form className="card max-w-2xl p-6" onSubmit={(event) => { event.preventDefault(); form.put('/profile'); }}><div className="grid gap-4 sm:grid-cols-2"><Field label="Nama"><input className="field" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)}/></Field><Field label="Email"><input className="field bg-slate-50" value={user.email} disabled/></Field><Field label="Nomor HP"><input className="field" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)}/></Field><Field label="Kelas"><input className="field" value={form.data.grade} onChange={(event) => form.setData('grade', event.target.value)}/></Field><Field label="Provinsi"><input className="field" value={form.data.province} onChange={(event) => form.setData('province', event.target.value)}/></Field><Field label="Kota/Kabupaten"><input className="field" value={form.data.city} onChange={(event) => form.setData('city', event.target.value)}/></Field></div>{Object.keys(form.errors).length > 0 && <p className="mt-4 text-xs text-red-600">Periksa kembali data yang diisi.</p>}<button disabled={form.processing} className="btn-primary mt-6">Simpan Profil</button></form></AppLayout>;
}

function Field({ label, children }) { return <div><label className="label">{label}</label>{children}</div>; }
