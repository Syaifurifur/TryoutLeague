import { Link } from '@inertiajs/react';
import { CalendarDays, Clock3 } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

export default function Schedule({ sessions, event }) {
    return <AppLayout title="Jadwal Ujian" subtitle={event}><div className="space-y-4">{sessions.map((session) => <section key={session.id} className="card flex flex-wrap items-center justify-between gap-4 p-5"><div className="flex items-center gap-4"><span className="grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-blue-700"><CalendarDays size={22}/></span><div><h2 className="font-extrabold">{session.subject}</h2><p className="mt-1 text-xs text-slate-500">{session.name}</p></div></div><div className="text-sm"><p>{new Date(session.starts_at).toLocaleString('id-ID')}</p><p className="mt-1 flex items-center gap-2 text-xs text-slate-400"><Clock3 size={14}/>{session.duration_minutes} menit</p></div><Link href="/dashboard" className="btn-secondary">Lihat Tryout</Link></section>)}</div></AppLayout>;
}
