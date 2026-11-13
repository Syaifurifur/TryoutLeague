import { Link } from '@inertiajs/react';
import { Medal, Trophy } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

const scopes = { national: 'Nasional', province: 'Provinsi', city: 'Kota/Kabupaten', school: 'Sekolah', subject: 'Mata Uji' };
export default function Ranking({ rows = [], mine, scope, subjects = [], subjectId }) {
    return <AppLayout title="Ranking" subtitle="Peringkat berdasarkan hasil final dan aturan event">
        <div className="flex flex-wrap gap-3 border-b pb-3">{Object.entries(scopes).map(([key, label]) => <Link key={key} href={`/ranking?scope=${key}${key === 'subject' && subjects[0] ? `&subject_id=${subjectId || subjects[0].id}` : ''}`} className={`rounded-lg px-3 py-2 text-sm font-semibold ${scope === key ? 'bg-blue-700 text-white' : 'text-slate-500'}`}>{label}</Link>)}</div>
        {scope === 'subject' && <div className="mt-4 flex flex-wrap gap-2">{subjects.map((subject) => <Link key={subject.id} href={`/ranking?scope=subject&subject_id=${subject.id}`} className={`rounded-full px-3 py-2 text-xs font-bold ${Number(subjectId) === Number(subject.id) ? 'bg-blue-100 text-blue-700' : 'bg-white text-slate-500'}`}>{subject.name}</Link>)}</div>}
        {rows[0] && <div className="card mt-5 flex items-center justify-between border-blue-300 bg-blue-50 p-5"><div className="flex items-center gap-3"><Trophy className="text-amber-500"/><div><p className="text-xs">Peringkat #1</p><strong>{rows[0].name}</strong></div></div><strong className="text-2xl">{Number(rows[0].score).toFixed(0)}</strong></div>}
        {!rows.length && <div className="card mt-5 p-10 text-center text-sm text-slate-400">Belum ada ranking pada scope ini.</div>}
        <div className="card mt-5 overflow-hidden">{rows.map((row) => <div key={row.registration_id} className={`grid grid-cols-[70px_1fr_1fr_80px] border-t p-4 text-xs ${row.registration_id === mine ? 'bg-blue-50 font-bold' : ''}`}><span>{row.rank <= 3 && <Medal className="mr-1 inline text-amber-500" size={15}/>}#{row.rank}</span><span>{row.name}</span><span>{row.school}</span><strong className="text-right">{Number(row.score).toFixed(0)}</strong></div>)}</div>
    </AppLayout>;
}
