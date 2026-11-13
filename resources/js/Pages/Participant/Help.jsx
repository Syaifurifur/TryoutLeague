import { Link } from '@inertiajs/react';
import { CircleHelp, CloudUpload, ShieldCheck, TimerReset } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

const items = [
    [TimerReset, 'Waktu ujian', 'Timer mengikuti waktu server. Saat waktu habis, jawaban otomatis dikirim dan dikunci.'],
    [CloudUpload, 'Koneksi terputus', 'Jawaban tetap tersimpan di perangkat dan otomatis disinkronkan ketika koneksi kembali. Jangan hapus data browser selama ujian.'],
    [ShieldCheck, 'Submit akhir', 'Periksa jumlah soal terjawab dan antrean sinkronisasi sebelum menekan Akhiri Ujian.'],
];

export default function Help() {
    return <AppLayout title="Bantuan" subtitle="Panduan singkat mengikuti tryout dengan aman">
        <div className="grid gap-4 md:grid-cols-3">{items.map(([Icon, title, body]) => <section key={title} className="card p-5"><Icon className="text-blue-700"/><h2 className="mt-4 font-extrabold">{title}</h2><p className="mt-2 text-xs leading-6 text-slate-500">{body}</p></section>)}</div>
        <section className="card mt-5 p-6"><h2 className="flex items-center gap-2 text-lg font-extrabold"><CircleHelp size={20}/>Sebelum mulai</h2><ol className="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600"><li>Pastikan mata uji sudah benar.</li><li>Gunakan browser terbaru dan koneksi yang stabil.</li><li>Aktifkan layar penuh jika diwajibkan event.</li><li>Jangan membuka akun yang sama di perangkat lain.</li></ol><div className="mt-6 flex flex-wrap gap-2"><Link href="/schedule" className="btn-primary">Lihat Jadwal</Link><Link href="/profile" className="btn-secondary">Periksa Profil</Link></div></section>
    </AppLayout>;
}
