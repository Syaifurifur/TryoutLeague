import { useState } from 'react';
import RichQuestionContent from './RichQuestionContent';

const labels = { correct: 'Benar', wrong: 'Salah / belum sepenuhnya benar', unanswered: 'Tidak dijawab' };

export default function AttemptReview({ discussion = [] }) {
    const [filter, setFilter] = useState('all');
    const attempts = [...new Map(discussion.map((item) => [item.attempt_id, `${item.subject} — ${item.session} (#${item.attempt_id})`])).entries()];
    if (!discussion.length) return null;
    const visible = discussion.filter((item) => filter === 'all' || String(item.attempt_id) === filter);

    return <section className="card mt-5 p-4 md:p-6">
        <div className="flex flex-wrap items-center justify-between gap-3"><div><h2 className="text-lg font-extrabold">Jawaban & Pembahasan Tryout</h2><p className="mt-1 text-xs text-slate-500">Nomor dan huruf opsi mengikuti urutan saat Anda mengerjakan. Hanya sesi yang sudah selesai ditampilkan.</p></div><select aria-label="Pilih sesi pembahasan" className="field max-w-md" value={filter} onChange={(event) => setFilter(event.target.value)}><option value="all">Semua sesi selesai</option>{attempts.map(([id, label]) => <option key={id} value={id}>{label}</option>)}</select></div>
        <div className="mt-5 space-y-5">{visible.map((item) => <article key={item.id} className="rounded-xl border border-slate-200 p-4 md:p-5">
            <div className="flex flex-wrap justify-between gap-3"><div><p className="text-xs font-bold text-blue-700">{item.subject} · {item.session}</p><h3 className="mt-2 font-extrabold">Soal {item.position} <span className="text-xs font-normal text-slate-400">{item.code}</span></h3></div><span className={`self-start rounded-lg px-3 py-1 text-xs font-bold ${item.result === 'correct' ? 'bg-emerald-50 text-emerald-700' : item.result === 'unanswered' ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-700'}`}>{labels[item.result]}</span></div>
            <RichQuestionContent content={item.content} className="mt-4 text-sm leading-7"/>
            {item.image_path && <img src={`/storage/${item.image_path}`} alt="Ilustrasi soal yang dikerjakan" className="my-4 max-h-80 object-contain"/>}
            {item.type === 'matrix' ? <div className="mt-4 overflow-x-auto"><table className="w-full min-w-[500px] text-left text-xs"><thead><tr className="bg-slate-50"><th className="p-3">Pernyataan</th><th className="p-3">Jawaban Anda</th><th className="p-3">Kunci jawaban</th><th className="p-3">Hasil</th></tr></thead><tbody>{item.matrix_rows.map((row, index) => <tr key={index} className="border-t"><td className="p-3">{row.statement}</td><td className="p-3">{row.selected ? `${row.selected.label}. ${row.selected.content}` : 'Tidak dijawab'}</td><td className="p-3 font-bold text-emerald-700">{row.correct ? `${row.correct.label}. ${row.correct.content}` : '—'}</td><td className="p-3">{row.is_correct ? 'Benar' : row.selected ? 'Salah' : 'Kosong'}</td></tr>)}</tbody></table></div> : <><div className="mt-4 space-y-2">{item.options.map((option) => <div key={option.id} className={`rounded-lg border p-3 text-sm ${option.is_correct ? 'border-emerald-200 bg-emerald-50' : option.selected ? 'border-rose-200 bg-rose-50' : 'border-slate-100'}`}><span className="font-bold">{option.label}.</span> {option.content}<span className="mt-1 flex flex-wrap gap-2 text-xs font-bold">{option.selected && <span>Jawaban Anda</span>}{option.is_correct && <span className="text-emerald-700">Kunci benar</span>}</span></div>)}</div><p className="mt-3 text-xs">Jawaban Anda: <strong>{item.selected_labels.join(', ') || 'Tidak dijawab'}</strong> · Kunci: <strong>{item.correct_labels.join(', ')}</strong></p></>}
            <div className="mt-4 rounded-xl bg-blue-50 p-4"><h4 className="text-sm font-extrabold text-blue-900">Pembahasan</h4><RichQuestionContent content={item.explanation || 'Pembahasan belum diisi oleh pembuat soal.'} className="mt-2 text-sm leading-7 text-slate-700"/></div>
        </article>)}</div>
    </section>;
}
