import { Link } from '@inertiajs/react';
import { Bell, CheckCircle2, Info } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

export default function Notifications({ notifications = [] }) {
    return <AppLayout title="Notifikasi" subtitle="Informasi pendaftaran, jadwal, hasil, dan sertifikat">
        <section className="card overflow-hidden">
            {notifications.length === 0 && <div className="p-10 text-center text-sm text-slate-400"><Bell className="mx-auto mb-3"/>Belum ada notifikasi.</div>}
            {notifications.map((item) => <Link key={item.id} href={item.href} className="flex gap-4 border-b border-slate-100 p-5 transition hover:bg-slate-50">
                <span className={`grid h-11 w-11 shrink-0 place-items-center rounded-xl ${item.type === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-700'}`}>{item.type === 'success' ? <CheckCircle2 size={20}/> : <Info size={20}/>}</span>
                <span><strong className="text-sm">{item.title}</strong><span className="mt-1 block text-xs leading-5 text-slate-500">{item.message}</span><small className="mt-2 block text-[10px] text-slate-400">{item.time ? new Date(item.time).toLocaleString('id-ID') : 'Sekarang'}</small></span>
            </Link>)}
        </section>
    </AppLayout>;
}
