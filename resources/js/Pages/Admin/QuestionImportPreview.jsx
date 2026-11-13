import { Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, CheckCircle2, FileText, UploadCloud } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

const levelLabels = { easy: 'Mudah', medium: 'Sedang', hard: 'Sulit' };

export default function QuestionImportPreview({ questionImport }) {
    const [processing, setProcessing] = useState(false);
    const questions = questionImport.payload || [];
    const confirmImport = () => { setProcessing(true); router.post(`/admin/questions/import/${questionImport.id}`, {}, { onFinish: () => setProcessing(false) }); };

    return <AppLayout admin title="Pratinjau Import DOCX" subtitle={`${questionImport.original_filename} • ${questionImport.subject.name}`}>
        <div className="grid gap-4 sm:grid-cols-3"><SummaryCard label="Total ditemukan" value={questionImport.total_questions} icon={FileText} color="blue"/><SummaryCard label="Siap diimpor" value={questionImport.valid_questions} icon={CheckCircle2} color="emerald"/><SummaryCard label="Perlu diperbaiki" value={questionImport.invalid_questions} icon={AlertTriangle} color="amber"/></div>
        <div className="mt-6 space-y-4">{questions.map((question) => <article key={question.number} className={`rounded-2xl border bg-white p-5 shadow-sm ${question.valid ? 'border-slate-200' : 'border-red-200 ring-2 ring-red-50'}`}>
            <div className="flex flex-wrap items-start justify-between gap-3"><div className="flex items-center gap-3"><span className={`grid h-9 w-9 place-items-center rounded-xl text-sm font-extrabold ${question.valid ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>{question.number}</span><div><p className="font-bold">{question.valid ? 'Valid dan siap diimpor' : 'Format soal belum valid'}</p><p className="mt-0.5 text-xs text-slate-400">{question.domain || 'Materi belum diisi'} • {levelLabels[question.difficulty] || 'Level belum valid'}</p></div></div><span className={`rounded-full px-3 py-1 text-[10px] font-extrabold uppercase ${question.valid ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>{question.valid ? 'Valid' : 'Perbaiki file'}</span></div>
            <p className="mt-5 whitespace-pre-line text-sm font-semibold leading-6 text-slate-800">{question.content || '—'}</p>
            <div className="mt-4 grid gap-2 sm:grid-cols-2">{Object.entries(question.options).map(([label, content]) => { const correct = Array.isArray(question.correct) ? question.correct.includes(label) : label === question.correct; return <div key={label} className={`rounded-xl border px-3 py-2.5 text-sm ${correct ? 'border-blue-300 bg-blue-50 text-blue-800' : 'border-slate-200 text-slate-600'}`}><span className="mr-2 font-extrabold">{label}.</span>{content}{correct && <span className="ml-2 text-[10px] font-extrabold uppercase">Kunci</span>}</div>; })}</div>
            {question.explanation && <div className="mt-4 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600"><strong>Pembahasan:</strong> {question.explanation}</div>}
            {!question.valid && <ul className="mt-4 space-y-1 rounded-xl bg-red-50 p-3 text-xs font-semibold text-red-700">{question.errors.map((error) => <li key={error}>• {error}</li>)}</ul>}
        </article>)}</div>
        <div className="sticky bottom-4 mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-xl backdrop-blur"><p className="text-xs text-slate-500">Soal tidak valid akan dilewati. Soal hasil impor berstatus <strong>review</strong>.</p><div className="flex gap-2"><Link href="/admin/questions" className="btn-secondary"><ArrowLeft size={16}/>Kembali</Link><button onClick={confirmImport} disabled={processing || questionImport.valid_questions === 0 || questionImport.status !== 'previewed'} className="btn-primary"><UploadCloud size={16}/>{processing ? 'Mengimpor…' : `Impor ${questionImport.valid_questions} Soal Valid`}</button></div></div>
    </AppLayout>;
}

function SummaryCard({ label, value, icon: Icon, color }) { const colors = { blue: 'bg-blue-50 text-blue-700', emerald: 'bg-emerald-50 text-emerald-700', amber: 'bg-amber-50 text-amber-700' }; return <div className="card flex items-center gap-4 p-5"><div className={`grid h-12 w-12 place-items-center rounded-2xl ${colors[color]}`}><Icon size={22}/></div><div><p className="text-2xl font-extrabold">{value}</p><p className="text-xs text-slate-500">{label}</p></div></div>; }
