import { router, useForm } from '@inertiajs/react';
import { BookOpenCheck, FileBadge, ShieldCheck, Users } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

export default function Settings({ subjects = [], users = [], schools = [], templates = [], certificates = [], auditLogs = [] }) {
    const subject = useForm({ code: '', name: '', category: 'optional', color: '#0B63CE' });
    const school = useForm({ npsn: '', name: '', education_level: 'SMA', ownership: 'Negeri', province: '', city: '', district: '', address: '', pic_name: '', pic_phone: '' });
    const user = useForm({ name: '', email: '', phone: '', password: '', role: 'content_editor', school_id: '' });
    const certificate = useForm({ name: 'Template Utama', title: 'SERTIFIKAT', subtitle: 'TRYOUT TKA', primary_color: '#082b61', accent_color: '#f59e0b', body_text: 'Telah mengikuti tryout dan menyelesaikan seluruh proses penilaian.', signatory_name: 'Tim Akademik', signatory_title: 'Penyelenggara', is_default: true });

    return <AppLayout admin title="Pengaturan" subtitle="Mata uji, role pengguna, sertifikat, dan audit log">
        <div className="grid gap-5 xl:grid-cols-2">
            <Section icon={BookOpenCheck} title="Master mata uji">
                <form onSubmit={(event) => { event.preventDefault(); subject.post('/admin/settings/subjects', { onSuccess: () => subject.reset() }); }} className="grid gap-2 sm:grid-cols-4">
                    <input className="field" placeholder="Kode" value={subject.data.code} onChange={(event) => subject.setData('code', event.target.value)}/>
                    <input className="field sm:col-span-2" placeholder="Nama" value={subject.data.name} onChange={(event) => subject.setData('name', event.target.value)}/>
                    <input type="color" className="field h-11" value={subject.data.color} onChange={(event) => subject.setData('color', event.target.value)}/>
                    <select className="field" value={subject.data.category} onChange={(event) => subject.setData('category', event.target.value)}><option value="required">Wajib</option><option value="optional">Pilihan</option></select>
                    <button disabled={subject.processing} className="btn-primary sm:col-span-3">Tambah Mata Uji</button>
                </form>
                <div className="mt-4 flex flex-wrap gap-2">{subjects.map((item) => <span key={item.id} className="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold">{item.code} · {item.name}</span>)}</div>
            </Section>
            <Section icon={Users} title="Master sekolah">
                <form onSubmit={(event) => { event.preventDefault(); school.post('/admin/settings/schools', { onSuccess: () => school.reset() }); }} className="grid gap-2 sm:grid-cols-2"><input className="field" placeholder="NPSN" value={school.data.npsn} onChange={(event) => school.setData('npsn', event.target.value)}/><input className="field" placeholder="Nama sekolah" value={school.data.name} onChange={(event) => school.setData('name', event.target.value)}/><select className="field" value={school.data.education_level} onChange={(event) => school.setData('education_level', event.target.value)}><option>SMA</option><option>MA</option><option>SMK</option></select><select className="field" value={school.data.ownership} onChange={(event) => school.setData('ownership', event.target.value)}><option>Negeri</option><option>Swasta</option></select><input className="field" placeholder="Provinsi" value={school.data.province} onChange={(event) => school.setData('province', event.target.value)}/><input className="field" placeholder="Kota/Kabupaten" value={school.data.city} onChange={(event) => school.setData('city', event.target.value)}/><input className="field" placeholder="Nama PIC" value={school.data.pic_name} onChange={(event) => school.setData('pic_name', event.target.value)}/><input className="field" placeholder="Nomor PIC" value={school.data.pic_phone} onChange={(event) => school.setData('pic_phone', event.target.value)}/><button disabled={school.processing} className="btn-primary sm:col-span-2">Tambah Sekolah</button></form><div className="mt-4 max-h-44 overflow-auto text-xs">{schools.map((item) => <p key={item.id} className="border-b py-2"><strong>{item.npsn}</strong> · {item.name} · {item.city}</p>)}</div>
            </Section>
            <Section icon={Users} title="Pengguna operasional">
                <form onSubmit={(event) => { event.preventDefault(); user.post('/admin/settings/users', { onSuccess: () => user.reset() }); }} className="grid gap-2 sm:grid-cols-2">
                    <input className="field" placeholder="Nama" value={user.data.name} onChange={(event) => user.setData('name', event.target.value)}/>
                    <input className="field" placeholder="Email" value={user.data.email} onChange={(event) => user.setData('email', event.target.value)}/>
                    <input className="field" placeholder="Nomor HP" value={user.data.phone} onChange={(event) => user.setData('phone', event.target.value)}/>
                    <input className="field" placeholder="Password" type="password" value={user.data.password} onChange={(event) => user.setData('password', event.target.value)}/>
                    <select className="field" value={user.data.role} onChange={(event) => user.setData('role', event.target.value)}>{['admin_event', 'content_editor', 'reviewer', 'proctor', 'school_operator', 'analyst', 'admin'].map((role) => <option key={role}>{role}</option>)}</select>
                    {user.data.role === 'school_operator' && <select className="field" value={user.data.school_id} onChange={(event) => user.setData('school_id', event.target.value)}><option value="">Pilih sekolah</option>{schools.map((school) => <option key={school.id} value={school.id}>{school.name}</option>)}</select>}
                    <button disabled={user.processing} className="btn-primary sm:col-span-2">Buat Pengguna</button>
                </form>
                <div className="mt-4 max-h-80 space-y-2 overflow-auto">{users.map((item) => <OperationalUserRow key={item.id} item={item} schools={schools}/>)}</div>
            </Section>
            <Section icon={FileBadge} title="Template sertifikat">
                <form onSubmit={(event) => { event.preventDefault(); certificate.post('/admin/settings/certificate-templates'); }} className="grid gap-2 sm:grid-cols-2">
                    <input className="field" value={certificate.data.name} onChange={(event) => certificate.setData('name', event.target.value)}/>
                    <input className="field" value={certificate.data.title} onChange={(event) => certificate.setData('title', event.target.value)}/>
                    <input className="field" value={certificate.data.subtitle} onChange={(event) => certificate.setData('subtitle', event.target.value)}/>
                    <input className="field" value={certificate.data.signatory_name} onChange={(event) => certificate.setData('signatory_name', event.target.value)}/>
                    <textarea className="field sm:col-span-2" value={certificate.data.body_text} onChange={(event) => certificate.setData('body_text', event.target.value)}/>
                    <button disabled={certificate.processing} className="btn-primary sm:col-span-2">Simpan Template</button>
                </form>
                <div className="mt-4 text-xs text-slate-500">{templates.map((item) => <p key={item.id}>{item.name} {item.is_default ? '· Default' : ''}</p>)}</div>
            </Section>
            <Section icon={FileBadge} title="Sertifikat terbit & revoke">
                <div className="max-h-80 space-y-2 overflow-auto">{certificates.map((item) => <div key={item.id} className="flex items-center justify-between rounded-xl bg-slate-50 p-3 text-xs"><span><strong>{item.name}</strong><small className="block text-slate-400">{item.event} · {item.verification_code}</small></span>{item.revoked_at ? <span className="font-bold text-red-600">Dicabut</span> : <button onClick={() => router.post(`/admin/certificates/${item.id}/revoke`)} className="rounded-lg bg-red-50 px-3 py-2 font-bold text-red-600">Revoke</button>}</div>)}</div>
            </Section>
            <Section icon={ShieldCheck} title="Audit log">
                <a href="/admin/reports/audit" className="btn-secondary mb-4">Export Audit CSV</a>
                <div className="max-h-96 overflow-auto">{auditLogs.map((log) => <div key={log.id} className="border-b py-3 text-xs"><strong>{log.action}</strong><p className="mt-1 text-slate-500">{log.name || 'Sistem'} · {log.entity_type} #{log.entity_id} · {new Date(log.created_at).toLocaleString('id-ID')}</p></div>)}</div>
            </Section>
        </div>
    </AppLayout>;
}

function Section({ icon: Icon, title, children }) {
    return <section className="card p-6"><h2 className="mb-5 flex items-center gap-2 font-extrabold"><Icon className="text-blue-700" size={19}/>{title}</h2>{children}</section>;
}

function OperationalUserRow({ item, schools }) {
    const access = useForm({ role: item.role, status: item.status, school_id: item.school_id || '' });
    return <form onSubmit={(event) => { event.preventDefault(); access.patch(`/admin/settings/users/${item.id}`); }} className="grid gap-2 rounded-xl bg-slate-50 p-3 text-xs sm:grid-cols-[1fr_150px_110px_auto]"><span><strong>{item.name}</strong><br/>{item.email}</span><select className="rounded-lg border bg-white px-2" value={access.data.role} onChange={(event) => access.setData('role', event.target.value)}>{['admin_event', 'content_editor', 'reviewer', 'proctor', 'school_operator', 'analyst', 'admin'].map((role) => <option key={role}>{role}</option>)}</select><select className="rounded-lg border bg-white px-2" value={access.data.status} onChange={(event) => access.setData('status', event.target.value)}><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>{access.data.role === 'school_operator' && <select className="rounded-lg border bg-white px-2 sm:col-start-2 sm:col-end-4" value={access.data.school_id} onChange={(event) => access.setData('school_id', event.target.value)}><option value="">Pilih sekolah</option>{schools.map((school) => <option key={school.id} value={school.id}>{school.name}</option>)}</select>}<button className="rounded-lg bg-blue-700 px-3 py-2 font-bold text-white">Simpan</button></form>;
}
