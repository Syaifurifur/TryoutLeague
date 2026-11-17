import { Link } from '@inertiajs/react';
import { Award, CheckCircle2, CircleX, Compass, Target } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import AttemptReview from '../../Components/AttemptReview';

export default function Results({
    participant,
    scores = [],
    ranking,
    domains = [],
    discussion = [],
    history = [],
    published,
    rationalization = { targets: [], recommendations: [] },
}) {
    if (!published) {
        return (
            <AppLayout title="Hasil Tryout" subtitle="Jawaban dan pembahasan sesi selesai">
                <div className="card p-6 text-center">
                    <h2 className="text-xl font-extrabold">
                        {discussion.length ? 'Tryout selesai — pembahasan tersedia' : 'Hasil belum tersedia'}
                    </h2>
                    <p className="mt-2 text-sm text-slate-500">
                        {discussion.length
                            ? 'Periksa jawaban Anda di bawah. Skor final, ranking, dan sertifikat menunggu publikasi admin.'
                            : 'Selesaikan tryout untuk melihat jawaban dan pembahasan.'}
                    </p>
                    <Link href="/schedule" className="btn-secondary mt-4">
                        Kembali ke Jadwal &amp; Tryout
                    </Link>
                </div>
                <AttemptReview discussion={discussion} />
            </AppLayout>
        );
    }

    const average = scores.length ? scores.reduce((sum, score) => sum + Number(score.scaled_score), 0) / scores.length : 0;
    const strengths = domains.slice(0, 3);
    const weaknesses = [...domains].reverse().slice(0, 3);
    const targets = rationalization?.targets || [];
    const recommendations = rationalization?.recommendations || [];

    return (
        <AppLayout title="Hasil Tryout" subtitle="Ringkasan performa, skor IRT, dan rasionalisasi PTN">
            <div className="grid gap-5 lg:grid-cols-[.8fr_1.2fr]">
                <section className="card p-7 text-center">
                    <p className="text-xs font-bold uppercase tracking-widest text-slate-400">Rata-rata skor</p>
                    <strong className="mt-4 block text-5xl text-[#092452]">{average ? average.toFixed(0) : '—'}</strong>
                    <div className="mt-7 grid grid-cols-2 border-t pt-6">
                        <div>
                            <p className="text-xs text-slate-400">Peringkat Nasional</p>
                            <strong className="mt-2 block text-xl text-blue-700">{ranking ? `#${ranking.rank}` : '—'}</strong>
                        </div>
                        <div className="border-l">
                            <p className="text-xs text-slate-400">Percentile</p>
                            <strong className="mt-2 block text-xl text-blue-700">{scores[0]?.percentile ?? '—'}</strong>
                        </div>
                    </div>
                </section>
                <section className="card p-7">
                    <h3 className="text-sm font-extrabold">Skor per subtes / mata uji</h3>
                    <div className="mt-6 space-y-5">
                        {scores.map((score) => (
                            <div key={score.id}>
                                <div className="flex justify-between text-xs">
                                    <strong>{score.subject}</strong>
                                    <span>{Number(score.scaled_score).toFixed(0)} / 800</span>
                                </div>
                                <div className="mt-2 h-2 rounded bg-slate-100">
                                    <div
                                        className="h-full rounded"
                                        style={{ width: `${Math.min(100, Number(score.scaled_score) / 8)}%`, background: score.color }}
                                    />
                                </div>
                                <p className="mt-2 text-[10px] text-slate-400">
                                    Benar {score.correct_count} · Salah {score.wrong_count} · Kosong {score.unanswered_count}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>

            <section className="card mt-5 p-7">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 className="flex items-center gap-2 text-base font-extrabold text-[#092452]">
                            <Compass size={18} className="text-blue-700" />
                            Rasionalisasi &amp; Prediksi Peluang PTN
                        </h3>
                        <p className="mt-1 text-xs text-slate-500">
                            Perbandingan rata-rata skor Anda ({average ? average.toFixed(1) : '0'}) dengan ambang skor prodi PTN target.
                        </p>
                    </div>
                    <Link href="/profile" className="btn-secondary text-xs">
                        <Target size={14} /> Atur Target PTN
                    </Link>
                </div>

                {targets.length === 0 ? (
                    <div className="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                        Anda belum memilih Target PTN.{' '}
                        <Link href="/profile" className="font-bold text-blue-700 underline">
                            Pilih jurusan target di halaman Profil
                        </Link>{' '}
                        untuk melihat prediksi peluang lolos.
                    </div>
                ) : (
                    <div className="mt-5 grid gap-4 md:grid-cols-2">
                        {targets.map((item) => {
                            const badgeClass =
                                item.status === 'Peluang Tinggi'
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : item.status === 'Kompetitif'
                                      ? 'bg-amber-50 text-amber-700 border-amber-200'
                                      : 'bg-rose-50 text-rose-700 border-rose-200';
                            return (
                                <div key={item.slot} className="rounded-2xl border border-slate-200 p-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                                Pilihan {item.slot} • {item.category}
                                            </span>
                                            <h4 className="mt-1 text-base font-extrabold text-[#092452]">
                                                {item.program_name} ({item.degree})
                                            </h4>
                                            <p className="text-xs text-slate-500">{item.university_name}</p>
                                        </div>
                                        <span className={`rounded-full border px-3 py-1 text-xs font-bold ${badgeClass}`}>
                                            {item.status} ({item.chance}%)
                                        </span>
                                    </div>
                                    <div className="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center text-xs">
                                        <div>
                                            <span className="block text-slate-400">Skor Anda</span>
                                            <strong className="text-[#092452]">{item.user_score}</strong>
                                        </div>
                                        <div>
                                            <span className="block text-slate-400">Passing Score</span>
                                            <strong className="text-[#092452]">{item.passing_score}</strong>
                                        </div>
                                        <div>
                                            <span className="block text-slate-400">Selisih</span>
                                            <strong className={item.diff >= 0 ? 'text-emerald-600' : 'text-rose-600'}>
                                                {item.diff >= 0 ? `+${item.diff}` : item.diff}
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {recommendations.length > 0 && (
                    <div className="mt-5 border-t pt-4">
                        <p className="text-xs font-bold text-slate-500">Rekomendasi prodi sesuai skor Anda saat ini:</p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {recommendations.map((rec) => (
                                <span key={rec.id} className="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                                    {rec.university_name} — {rec.program_name} ({rec.passing_score})
                                </span>
                            ))}
                        </div>
                    </div>
                )}
            </section>

            <div className="mt-5 grid gap-5 md:grid-cols-3">
                <DomainCard title="Kekuatan" rows={strengths} icon={CheckCircle2} color="emerald" />
                <DomainCard title="Perlu ditingkatkan" rows={weaknesses} icon={CircleX} color="rose" />
                <section className="card p-6">
                    <h3 className="font-extrabold">Informasi hasil</h3>
                    <p className="mt-4 text-sm">{participant.name}</p>
                    <p className="mt-1 text-xs text-slate-400">{participant.number}</p>
                    <Link href="/certificate" className="btn-primary mt-6 w-full">
                        <Award size={16} />
                        Lihat Sertifikat
                    </Link>
                </section>
            </div>
            <AttemptReview discussion={discussion} />
            <section className="card mt-5 p-6">
                <h2 className="text-lg font-extrabold">Riwayat Tryout</h2>
                <div className="mt-4 divide-y">
                    {history.map((item) => (
                        <div key={item.id} className="flex items-center justify-between py-3 text-sm">
                            <span>
                                {item.event}
                                <small className="block text-slate-400">{item.scored_subjects} mata uji dinilai</small>
                            </span>
                            <strong>{item.average_score ? Number(item.average_score).toFixed(1) : 'Menunggu hasil'}</strong>
                        </div>
                    ))}
                </div>
            </section>
        </AppLayout>
    );
}

function DomainCard({ title, rows, icon: Icon, color }) {
    const styles = color === 'emerald' ? 'border-emerald-100 bg-emerald-50' : 'border-rose-100 bg-rose-50';
    return (
        <section className={`rounded-2xl border p-6 ${styles}`}>
            <h3 className="font-extrabold">{title}</h3>
            {rows.map((row) => (
                <p key={row.domain} className="mt-3 flex items-center justify-between text-xs">
                    <span className="flex items-center gap-2">
                        <Icon size={15} />
                        {row.domain}
                    </span>
                    <strong>{Number(row.percentage || 0).toFixed(0)}%</strong>
                </p>
            ))}
        </section>
    );
}
