import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2 } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';
import RichQuestionContent from '../../Components/RichQuestionContent';

export default function PackagePreview({ package: examPackage, sections = [] }) {
    const questionCount = sections.reduce((total, section) => total + section.questions.length, 0);
    return <AppLayout admin title="Preview Paket" subtitle={`${examPackage.event} · ${examPackage.subject}`}>
        <div className="mb-5 flex flex-wrap items-center justify-between gap-3"><Link href="/admin/events" className="btn-secondary"><ArrowLeft size={16}/>Kembali</Link><div className="flex gap-2"><span className="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold">{questionCount} soal · {examPackage.duration_minutes} menit · v{examPackage.version}</span>{examPackage.status === 'draft' && <button onClick={() => router.post(`/admin/packages/${examPackage.id}/publish`)} className="btn-primary"><CheckCircle2 size={16}/>Publish Paket</button>}</div></div>
        <section className="card p-6"><h2 className="text-xl font-extrabold">{examPackage.name}</h2><p className="mt-2 text-xs text-slate-500">Random soal: {examPackage.randomize_questions ? 'Ya' : 'Tidak'} · Random opsi: {examPackage.randomize_options ? 'Ya' : 'Tidak'}</p></section>
        <div className="mt-5 space-y-5">{sections.map((section) => <section key={section.id} className="card p-6"><div className="flex justify-between"><h3 className="font-extrabold">Bagian {section.position}: {section.name}</h3><span className="text-xs text-slate-400">{section.duration_minutes || examPackage.duration_minutes} menit</span></div><div className="mt-5 space-y-3">{section.questions.map((question, index) => <article key={question.id} className="rounded-xl border border-slate-100 p-4"><div className="flex flex-wrap gap-2 text-[10px] font-bold uppercase text-blue-700"><span>#{index + 1}</span><span>{question.code}</span><span>{question.domain}</span><span>{question.difficulty}</span><span>{question.type}</span><span>v{question.version}</span></div><RichQuestionContent content={question.content} className="mt-3 text-sm leading-7"/></article>)}</div></section>)}</div>
    </AppLayout>;
}
