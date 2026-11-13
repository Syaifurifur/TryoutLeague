import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, CircleX } from 'lucide-react';
import Logo from '../Components/Logo';

export default function CertificateVerify({ certificate }) {
    const valid = certificate && !certificate.revoked_at;
    const title = valid ? 'Sertifikat Terverifikasi' : certificate ? 'Sertifikat Telah Dicabut' : 'Sertifikat Tidak Ditemukan';
    return <><Head title="Verifikasi Sertifikat"/><div className="grid min-h-screen place-items-center bg-[#f5f8fd] p-5"><div className="w-full max-w-xl text-center"><Logo/><div className="card mt-8 p-10">{valid ? <CheckCircle2 className="mx-auto text-emerald-500" size={58}/> : <CircleX className="mx-auto text-red-500" size={58}/>}<h1 className="mt-5 text-2xl font-extrabold">{title}</h1>{valid && <><p className="mt-3 text-sm text-slate-500">Sertifikat ini sah dan diterbitkan oleh TKA Juara.</p><div className="mt-7 rounded-2xl bg-slate-50 p-6 text-left text-sm"><Row label="Nama" value={certificate.name}/><Row label="Nomor peserta" value={certificate.participant_number}/><Row label="Event" value={certificate.event}/><Row label="Kode" value={certificate.verification_code}/></div></>}{certificate?.revoked_at && <p className="mt-3 text-sm text-red-600">Kode ditemukan, tetapi status sertifikat tidak lagi berlaku.</p>}<Link href="/" className="btn-primary mt-7">Kembali ke Beranda</Link></div></div></div></>;
}

function Row({ label, value }) { return <p className="mb-4 flex justify-between gap-4 last:mb-0"><span className="text-slate-400">{label}</span><strong className="text-right">{value}</strong></p>; }
