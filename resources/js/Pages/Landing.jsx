import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    BarChart3,
    BookOpenCheck,
    BrainCircuit,
    Building2,
    CalendarCheck2,
    CheckCircle2,
    ChevronDown,
    Compass,
    Medal,
    ShieldCheck,
    Sparkles,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import Logo from '../Components/Logo';

const TPS_SUBTESTS = [
    { name: 'Penalaran Umum (PU)', desc: 'Penalaran induktif, deduktif, dan kuantitatif.' },
    { name: 'Pengetahuan & Pemahaman Umum (PPU)', desc: 'Kedalaman kosakata, makna, dan struktur gagasan.' },
    { name: 'Pemahaman Bacaan & Menulis (PBM)', desc: 'Ketelitian ejaan, kalimat efektif, dan koherensi teks.' },
    { name: 'Pengetahuan Kuantitatif (PK)', desc: 'Konsep aljabar, geometri, dan logika matematika dasar.' },
];

const LITERASI_SUBTESTS = [
    { name: 'Literasi Bahasa Indonesia', desc: 'Analisis bacaan sastra, sains, dan sosial secara kritis.' },
    { name: 'Literasi Bahasa Inggris', desc: 'Pemahaman teks akademik, inferensi, dan argumen.' },
    { name: 'Penalaran Matematika', desc: 'Pemecahan masalah kontekstual berbasis data dan model.' },
];

const LEAGUE_SERIES = [
    { month: 'Januari', title: 'Episode 1 • Diagnostic Test', note: 'Pemetaan skor IRT awal & rekomendasi target PTN' },
    { month: 'Februari', title: 'Episode 2 • School Battle', note: 'Kompetisi skor individu & peringkat antar-SMA/SMK' },
    { month: 'Maret', title: 'Episode 3 • Grand Tryout SNBT', note: 'Simulasi akbar nasional + rasionalisasi jurusan' },
    { month: 'April', title: 'Episode 4 • Final Sprint', note: 'Pemantapan akhir & evaluasi subtes terlemah' },
];

const FAQS = [
    [
        'Apakah Tryout UTBK-SNBT di Tryout League gratis?',
        'Ya, peserta kelas XII maupun kelas XI dapat mendaftar dan mengikuti seluruh subtes utama secara gratis.',
    ],
    [
        'Bagaimana cara kerja simulasi skor sistem IRT?',
        'Sistem IRT (Item Response Theory) menghitung bobot tiap soal berdasarkan tingkat kesukaran dan performa seluruh peserta, sehingga hasilnya lebih mendekati standar UTBK-SNBT resmi.',
    ],
    [
        'Apakah siswa kelas XI boleh ikut untuk curi start?',
        'Sangat boleh. Tersedia pilihan kelas XI, XII, dan Alumni saat pendaftaran agar siswa bisa mengukur kesiapan lebih awal.',
    ],
    [
        'Apakah jawaban aman saat koneksi internet terputus?',
        'Ya. Jawaban disimpan secara lokal terlebih dahulu lalu otomatis disinkronkan ke server ketika koneksi kembali.',
    ],
    [
        'Bagaimana cara cek keaslian sertifikat tryout?',
        'Setiap sertifikat memiliki kode verifikasi unik dan QR/tautan halaman verifikasi publik di Tryout League.',
    ],
];

export default function Landing({ events = [], platformStats = {}, subjectNames = [] }) {
    const { auth } = usePage().props;
    const dashboardHref =
        auth.user?.role === 'participant'
            ? '/dashboard'
            : auth.user?.role === 'school_operator'
              ? '/school'
              : '/admin';
    const nextEvent = events[0];

    const stats = [
        [Number(platformStats.participants || 0).toLocaleString('id-ID'), 'Peserta terdaftar', Users],
        [Number(platformStats.schools || 0).toLocaleString('id-ID'), 'Sekolah bergabung', Building2],
        [Number(platformStats.packages || 0).toLocaleString('id-ID'), 'Paket subtes SNBT', BookOpenCheck],
        [`${events.length}`, 'Event aktif', Medal],
    ];

    return (
        <>
            <Head title="Tryout UTBK-SNBT 2026" />
            <div className="overflow-hidden bg-white">
                <header className="fixed inset-x-0 top-0 z-30 border-b border-slate-100 bg-white/90 backdrop-blur-xl">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                        <Logo />
                        <nav className="hidden items-center gap-8 text-sm font-semibold text-slate-600 md:flex">
                            <a href="#beranda" className="hover:text-blue-700">Beranda</a>
                            <a href="#materi" className="hover:text-blue-700">Materi SNBT</a>
                            <a href="#fitur" className="hover:text-blue-700">Fitur IRT</a>
                            <a href="#jadwal" className="hover:text-blue-700">Jadwal Jan–Apr</a>
                            <a href="#faq" className="hover:text-blue-700">FAQ</a>
                        </nav>
                        <div className="flex gap-2">
                            {auth.user ? (
                                <Link href={dashboardHref} className="btn-primary py-2.5">
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link href="/login" className="btn-secondary px-4 py-2.5">
                                        Masuk
                                    </Link>
                                    <Link href="/register" className="btn-primary px-4 py-2.5">
                                        Daftar
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main id="beranda" className="pt-20">
                    <section className="relative">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_75%_30%,#dbeafe_0,transparent_32%),radial-gradient(circle_at_20%_20%,#eff6ff_0,transparent_28%)]" />
                        <div className="relative mx-auto grid min-h-[680px] max-w-7xl items-center gap-8 px-5 py-16 lg:grid-cols-[1.04fr_.96fr] lg:px-8">
                            <div className="z-10 max-w-2xl">
                                <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3.5 py-2 text-xs font-bold text-blue-700">
                                    <Sparkles size={14} /> Tryout UTBK-SNBT Nasional • Seri Januari – April
                                </div>
                                <h1 className="text-4xl font-extrabold leading-[1.12] tracking-[-.04em] text-[#092452] sm:text-5xl lg:text-6xl">
                                    Taklukkan UTBK-SNBT,
                                    <br />
                                    Raih PTN Impian di
                                    <br />
                                    <span className="text-blue-700">Tryout League!</span>
                                </h1>
                                <p className="mt-6 max-w-lg text-base leading-8 text-slate-600">
                                    Simulasi UTBK-SNBT khusus siswa Kelas XII &amp; XI dengan penilaian sistem{' '}
                                    <strong className="text-slate-800">IRT</strong>, rasionalisasi peluang lolos jurusan PTN, dan ranking nasional.
                                </p>
                                <div className="mt-8 flex flex-wrap gap-3">
                                    <Link href="/register" className="btn-primary">
                                        Daftar Tryout Gratis <ArrowRight size={17} />
                                    </Link>
                                    <a href="#materi" className="btn-secondary">
                                        Lihat 7 Subtes SNBT
                                    </a>
                                </div>
                                <div className="mt-7 flex flex-wrap gap-5 text-xs font-semibold text-slate-500">
                                    <span className="flex items-center gap-2">
                                        <CheckCircle2 size={16} className="text-emerald-500" />
                                        Kelas XII &amp; XI Curi Start
                                    </span>
                                    <span className="flex items-center gap-2">
                                        <Target size={16} className="text-blue-600" />
                                        Simulasi Skor IRT
                                    </span>
                                    <span className="flex items-center gap-2">
                                        <ShieldCheck size={16} className="text-blue-600" />
                                        Prediksi Peluang PTN
                                    </span>
                                </div>
                            </div>

                            <div className="relative min-h-[510px]">
                                <div className="absolute inset-x-8 bottom-2 h-80 rounded-full bg-gradient-to-t from-blue-100 to-transparent blur-2xl" />
                                <img
                                    src="/images/tka-students-v2.png"
                                    alt="Pelajar SMA bersiap menghadapi UTBK-SNBT"
                                    className="absolute bottom-0 left-1/2 h-[560px] max-w-none -translate-x-1/2 rounded-[4rem] object-contain mix-blend-multiply lg:h-[610px]"
                                />
                                <div className="card absolute right-0 top-14 flex items-center gap-3 p-4">
                                    <span className="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-700">
                                        <BrainCircuit size={20} />
                                    </span>
                                    <div>
                                        <strong className="block text-xs">Simulasi Skor IRT</strong>
                                        <span className="text-[10px] text-slate-400">Bobot dinamis standar SNBT</span>
                                    </div>
                                </div>
                                <div className="card absolute left-0 top-44 flex items-center gap-3 p-4">
                                    <span className="grid h-10 w-10 place-items-center rounded-xl bg-violet-50 text-violet-600">
                                        <Compass size={20} />
                                    </span>
                                    <div>
                                        <strong className="block text-xs">Rasionalisasi PTN</strong>
                                        <span className="text-[10px] text-slate-400">Prediksi peluang lolos prodi</span>
                                    </div>
                                </div>
                                <div className="card absolute bottom-20 right-2 flex items-center gap-3 p-4">
                                    <span className="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600">
                                        <Trophy size={20} />
                                    </span>
                                    <div>
                                        <strong className="block text-xs">Ranking Nasional</strong>
                                        <span className="text-[10px] text-slate-400">Bandingkan posisi se-Indonesia</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="relative mx-auto -mt-4 max-w-7xl px-5 lg:px-8">
                            <div className="grid overflow-hidden rounded-2xl bg-gradient-to-r from-[#092f68] to-[#0759c7] text-white shadow-2xl shadow-blue-900/20 sm:grid-cols-2 lg:grid-cols-4">
                                {stats.map(([value, label, Icon], i) => (
                                    <div
                                        key={label}
                                        className={`flex items-center gap-4 px-6 py-6 ${i ? 'border-white/10 lg:border-l' : ''}`}
                                    >
                                        <span className="grid h-11 w-11 place-items-center rounded-xl bg-white/10">
                                            <Icon size={20} />
                                        </span>
                                        <div>
                                            <strong className="block text-lg">{value}</strong>
                                            <span className="text-xs text-blue-100">{label}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="materi" className="mx-auto max-w-7xl px-5 py-24 lg:px-8">
                        <div className="mx-auto max-w-2xl text-center">
                            <p className="kicker">Materi Uji Resmi UTBK-SNBT</p>
                            <h2 className="mt-3 text-3xl font-extrabold tracking-tight text-[#092452] sm:text-4xl">
                                Lengkap 7 Subtes TPS &amp; Literasi
                            </h2>
                            <p className="mt-4 text-slate-500">
                                Dirancang mengikuti struktur resmi UTBK-SNBT agar kamu terbiasa dengan ritme waktu, tipe soal HOTS, dan pola penalaran terbaru.
                            </p>
                        </div>

                        <div className="mt-12 grid gap-6 lg:grid-cols-2">
                            <div className="card p-7">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-extrabold text-blue-700">
                                            BAGIAN 1 • 4 SUBTES
                                        </span>
                                        <h3 className="mt-3 text-xl font-extrabold text-[#092452]">
                                            Tes Potensi Skolastik (TPS)
                                        </h3>
                                    </div>
                                    <BrainCircuit className="text-blue-700" size={28} />
                                </div>
                                <div className="mt-6 space-y-3">
                                    {TPS_SUBTESTS.map((item) => (
                                        <div key={item.name} className="rounded-xl bg-slate-50 p-4">
                                            <p className="flex items-center gap-2 text-sm font-bold text-[#092452]">
                                                <CheckCircle2 size={16} className="shrink-0 text-emerald-500" />
                                                {item.name}
                                            </p>
                                            <p className="mt-1 pl-6 text-xs leading-6 text-slate-500">{item.desc}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="card p-7">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="rounded-full bg-violet-50 px-3 py-1 text-xs font-extrabold text-violet-700">
                                            BAGIAN 2 • 3 SUBTES
                                        </span>
                                        <h3 className="mt-3 text-xl font-extrabold text-[#092452]">
                                            Tes Literasi &amp; Penalaran Matematika
                                        </h3>
                                    </div>
                                    <BookOpenCheck className="text-violet-700" size={28} />
                                </div>
                                <div className="mt-6 space-y-3">
                                    {LITERASI_SUBTESTS.map((item) => (
                                        <div key={item.name} className="rounded-xl bg-slate-50 p-4">
                                            <p className="flex items-center gap-2 text-sm font-bold text-[#092452]">
                                                <CheckCircle2 size={16} className="shrink-0 text-emerald-500" />
                                                {item.name}
                                            </p>
                                            <p className="mt-1 pl-6 text-xs leading-6 text-slate-500">{item.desc}</p>
                                        </div>
                                    ))}
                                </div>
                                {subjectNames.length > 0 && (
                                    <div className="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                                        {subjectNames.map((s) => (
                                            <span
                                                key={s}
                                                className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                                            >
                                                {s}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>

                    <section id="fitur" className="bg-[#f5f8fd] py-24">
                        <div className="mx-auto max-w-7xl px-5 lg:px-8">
                            <div className="mx-auto max-w-2xl text-center">
                                <p className="kicker">Kenapa Tryout League?</p>
                                <h2 className="mt-3 text-3xl font-extrabold tracking-tight text-[#092452] sm:text-4xl">
                                    Bukan Sekadar Tryout, Tapi Peta Jalan Lolos PTN
                                </h2>
                                <p className="mt-4 text-slate-500">
                                    Dirancang untuk membantu siswa Kelas XII dan XI mengukur peluang secara objektif sejak Januari hingga April.
                                </p>
                            </div>
                            <div className="mt-12 grid gap-5 md:grid-cols-3">
                                {[
                                    [
                                        BarChart3,
                                        'Simulasi Skor Sistem IRT',
                                        'Penilaian berbasis Item Response Theory dengan pembobotan tingkat kesulitan soal dan evaluasi akurasi per subtes.',
                                    ],
                                    [
                                        Target,
                                        'Rasionalisasi & Peluang PTN',
                                        'Bandingkan skor IRT kamu terhadap daya tampung dan ambang skor prodi PTN impian untuk menentukan strategi pilihan.',
                                    ],
                                    [
                                        Award,
                                        'Ranking Nasional & Sertifikat',
                                        'Pantau peringkat se-Indonesia maupun antar-sekolah, lengkap dengan sertifikat digital terverifikasi.',
                                    ],
                                ].map(([Icon, title, text]) => (
                                    <div
                                        className="card group bg-white p-7 transition hover:-translate-y-1 hover:border-blue-200"
                                        key={title}
                                    >
                                        <span className="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-700 transition group-hover:bg-blue-700 group-hover:text-white">
                                            <Icon />
                                        </span>
                                        <h3 className="mt-5 text-lg font-extrabold text-[#092452]">{title}</h3>
                                        <p className="mt-2 text-sm leading-7 text-slate-500">{text}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="jadwal" className="mx-auto max-w-7xl px-5 py-24 lg:px-8">
                        <div className="grid gap-10 lg:grid-cols-2">
                            <div>
                                <p className="kicker">Kalender Liga Januari – April</p>
                                <h2 className="mt-3 text-3xl font-extrabold text-[#092452]">
                                    4 Episode Menuju UTBK-SNBT
                                </h2>
                                <p className="mt-4 max-w-lg leading-7 text-slate-500">
                                    Ikuti setiap episode bulanan dari Januari sampai April untuk melihat grafik kenaikan skor IRT kamu secara konsisten.
                                </p>
                                <div className="mt-8 grid gap-3 sm:grid-cols-2">
                                    {LEAGUE_SERIES.map((ep) => (
                                        <div key={ep.month} className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                            <span className="text-[11px] font-extrabold uppercase tracking-wider text-blue-700">
                                                {ep.month}
                                            </span>
                                            <h3 className="mt-1 text-sm font-extrabold text-[#092452]">{ep.title}</h3>
                                            <p className="mt-1 text-xs leading-5 text-slate-500">{ep.note}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="card self-start p-7">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="badge-success">
                                            {nextEvent ? 'Pendaftaran tersedia' : 'Segera hadir'}
                                        </span>
                                        <h3 className="mt-3 text-xl font-extrabold text-[#092452]">
                                            {nextEvent?.name || 'Tryout UTBK-SNBT Nasional — Batch Terdekat'}
                                        </h3>
                                    </div>
                                    <CalendarCheck2 className="text-blue-700" size={32} />
                                </div>
                                <div className="mt-7 space-y-5 border-t border-slate-100 pt-6">
                                    {[
                                        [
                                            'Pendaftaran',
                                            nextEvent?.registration_close_at
                                                ? `Sampai ${new Date(nextEvent.registration_close_at).toLocaleDateString('id-ID')}`
                                                : 'Dibuka periode Jan – Apr 2026',
                                        ],
                                        [
                                            'Periode tryout',
                                            nextEvent?.event_start_at
                                                ? `${new Date(nextEvent.event_start_at).toLocaleDateString('id-ID')} – ${new Date(nextEvent.event_end_at).toLocaleDateString('id-ID')}`
                                                : 'Mengikuti jadwal episode aktif',
                                        ],
                                        [
                                            'Kuota',
                                            nextEvent?.quota
                                                ? `${Number(nextEvent.quota).toLocaleString('id-ID')} peserta`
                                                : 'Terbuka Nasional (XII & XI)',
                                        ],
                                        ['Sistem Penilaian', 'IRT + Rasionalisasi PTN + Ranking'],
                                    ].map(([a, b]) => (
                                        <div className="flex justify-between gap-4 text-sm" key={a}>
                                            <span className="text-slate-400">{a}</span>
                                            <strong className="text-right text-slate-800">{b}</strong>
                                        </div>
                                    ))}
                                </div>
                                <Link href="/register" className="btn-primary mt-7 w-full">
                                    Amankan Kursimu <ArrowRight size={17} />
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section id="faq" className="bg-[#f5f8fd] py-24">
                        <div className="mx-auto max-w-3xl px-5">
                            <div className="text-center">
                                <p className="kicker">Pertanyaan umum</p>
                                <h2 className="mt-3 text-3xl font-extrabold text-[#092452]">Yang sering ditanyakan</h2>
                            </div>
                            <div className="mt-10 space-y-3">
                                {FAQS.map(([q, a]) => (
                                    <details key={q} className="group card bg-white p-5">
                                        <summary className="flex cursor-pointer list-none items-center justify-between font-bold text-[#092452]">
                                            {q}
                                            <ChevronDown size={18} className="shrink-0 transition group-open:rotate-180" />
                                        </summary>
                                        <p className="mt-3 text-sm leading-7 text-slate-500">{a}</p>
                                    </details>
                                ))}
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="bg-[#071d43] py-10 text-white">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-5 sm:flex-row lg:px-8">
                        <Logo light />
                        <p className="text-xs text-blue-200">
                            © 2026 Tryout League. Platform Simulasi UTBK-SNBT Nasional.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
