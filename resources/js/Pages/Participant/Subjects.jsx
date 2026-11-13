import { useForm } from '@inertiajs/react';
import { BookOpenCheck, Check, LockKeyhole } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

export default function Subjects({ subjects, maxOptional, canEdit, closesAt }) {
    const initial = subjects.filter((subject) => subject.selected_id).map((subject) => subject.id);
    const form = useForm({ subjects: initial });
    const required = subjects.filter((subject) => subject.is_required).map((subject) => subject.id);
    const toggle = (subject) => {
        if (!canEdit || subject.is_required) return;
        const selected = form.data.subjects.includes(subject.id);
        const optionalCount = form.data.subjects.filter((id) => !required.includes(id)).length;
        if (!selected && optionalCount >= maxOptional) return;
        form.setData('subjects', selected ? form.data.subjects.filter((id) => id !== subject.id) : [...form.data.subjects, subject.id]);
    };
    return <AppLayout title="Pilihan Mata Uji" subtitle="Pilihan tersimpan sesuai periode event"><div className={`rounded-2xl p-5 text-sm ${canEdit ? 'border border-blue-100 bg-blue-50 text-blue-800' : 'border border-amber-100 bg-amber-50 text-amber-800'}`}>{canEdit ? <>Pilih maksimal <strong>{maxOptional}</strong> mata uji pilihan. Mata uji wajib tetap terkunci.{closesAt && <> Batas: {new Date(closesAt).toLocaleString('id-ID')}.</>}</> : <strong>Periode pemilihan mata uji sedang ditutup.</strong>}</div><form onSubmit={(event) => { event.preventDefault(); form.put('/subjects'); }}><div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">{subjects.map((subject) => { const selected = form.data.subjects.includes(subject.id); return <button disabled={!canEdit} type="button" key={subject.id} onClick={() => toggle(subject)} className={`card relative flex items-center gap-4 p-5 text-left ${selected ? 'border-blue-500 ring-4 ring-blue-50' : ''}`}><span style={{ backgroundColor: `${subject.color}18`, color: subject.color }} className="grid h-12 w-12 place-items-center rounded-xl"><BookOpenCheck size={22}/></span><div><h3 className="text-sm font-extrabold">{subject.name}</h3><p className="mt-1 text-xs text-slate-400">{subject.is_required ? 'Wajib' : 'Pilihan'}</p></div><span className={`absolute right-4 top-4 grid h-6 w-6 place-items-center rounded-full ${selected ? 'bg-blue-700 text-white' : 'border'}`}>{subject.is_required ? <LockKeyhole size={12}/> : selected && <Check size={14}/>}</span></button>; })}</div>{form.errors.subjects && <p className="mt-4 text-sm text-red-600">{form.errors.subjects}</p>}<div className="mt-7 flex justify-end"><button disabled={!canEdit || form.processing} className="btn-primary">Simpan Pilihan</button></div></form></AppLayout>;
}
