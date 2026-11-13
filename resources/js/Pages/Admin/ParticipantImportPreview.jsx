import { Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2 } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

export default function ParticipantImportPreview({ participantImport, event }) {
    const { auth } = usePage().props;
    const schoolMode = auth.user?.role === 'school_operator';
    const base = schoolMode ? '/school' : '/admin/participants';

    return <AppLayout admin={!schoolMode} title="Pratinjau Import Peserta" subtitle={`${participantImport.original_filename} · ${event.name}`}>
        <div className="grid gap-3 sm:grid-cols-3"><Box label="Total" value={participantImport.total_rows}/><Box label="Valid" value={participantImport.valid_rows} good/><Box label="Error" value={participantImport.invalid_rows}/></div>
        <div className="card mt-5 overflow-x-auto"><table className="w-full min-w-[800px] text-left text-xs"><thead className="bg-slate-50"><tr><th className="p-3">Baris</th><th>Nama</th><th>Email</th><th>NISN</th><th>Status</th></tr></thead><tbody>{participantImport.payload.map((row) => <tr key={row.row} className="border-t"><td className="p-3">{row.row}</td><td>{row.name}</td><td>{row.email}</td><td>{row.nisn}</td><td>{row.valid ? <span className="text-emerald-700"><CheckCircle2 className="inline" size={15}/> Valid</span> : <div className="text-red-700"><AlertTriangle className="mr-1 inline" size={15}/>{row.errors.join(' ')}</div>}</td></tr>)}</tbody></table></div>
        <div className="mt-5 flex justify-end gap-2"><Link href={base} className="btn-secondary">Kembali</Link><button disabled={!participantImport.valid_rows} onClick={() => router.post(`${base}/import/${participantImport.id}`)} className="btn-primary">Import {participantImport.valid_rows} Peserta Valid</button></div>
    </AppLayout>;
}

function Box({ label, value, good }) { return <div className="card p-5"><p className="text-xs text-slate-400">{label}</p><strong className={`mt-2 block text-3xl ${good ? 'text-emerald-700' : ''}`}>{value}</strong></div>; }
