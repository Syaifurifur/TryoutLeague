import { Head, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, ArrowRight, Bookmark, CheckCircle2, CloudOff, CloudUpload, Expand, Send, ShieldAlert, Wifi } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Logo from '../../Components/Logo';
import RichQuestionContent from '../../Components/RichQuestionContent';

export default function Runner({ attempt, questions, fullscreenRequired = false }) {
    const storageKey = `tka-state-${attempt.id}`;
    const queueKey = `tka-pending-${attempt.id}`;
    const localState = readJson(storageKey, {});
    const [index, setIndex] = useState(0);
    const [answers, setAnswers] = useState(Object.fromEntries(questions.map((question) => [question.id, localState[question.id] ?? (question.type === 'matrix' ? (question.matrix_answer_ids || {}) : (question.answer_ids || []))])));
    const [flags, setFlags] = useState(Object.fromEntries(questions.map((question) => [question.id, question.is_flagged])));
    const [remaining, setRemaining] = useState(0);
    const [saveState, setSaveState] = useState('saved');
    const [online, setOnline] = useState(navigator.onLine);
    const [confirm, setConfirm] = useState(false);
    const [submitError, setSubmitError] = useState('');
    const syncing = useRef(false);
    const submitted = attempt.status !== 'in_progress';
    const current = questions[index];
    const serverOffset = useMemo(() => new Date(attempt.server_time).getTime() - Date.now(), [attempt.server_time]);
    const calcTime = useCallback(() => Math.max(0, Math.floor((new Date(attempt.expires_at).getTime() - (Date.now() + serverOffset)) / 1000)), [attempt.expires_at, serverOffset]);
    const getQueue = () => readJson(queueKey, []);
    const writeQueue = (items) => localStorage.setItem(queueKey, JSON.stringify(items));

    const syncQueue = useCallback(async () => {
        if (syncing.current || !navigator.onLine || submitted) return getQueue().length === 0;
        const queue = getQueue();
        if (!queue.length) return true;
        syncing.current = true;
        setSaveState('saving');
        const failed = [];
        for (const item of queue) {
            try {
                await window.axios.put(`/attempts/${attempt.id}/answers/${item.questionId}`, {
                    option_ids: item.option_ids,
                    matrix_answers: item.matrix_answers,
                    idempotency_key: item.idempotency_key,
                });
            } catch (error) {
                if (error.response?.status !== 409) failed.push(item);
            }
        }
        writeQueue(failed);
        syncing.current = false;
        setSaveState(failed.length ? 'offline' : 'saved');
        return failed.length === 0;
    }, [attempt.id, submitted]);

    useEffect(() => {
        setRemaining(calcTime());
        const timer = setInterval(() => {
            const next = calcTime();
            setRemaining(next);
            if (next <= 0 && !submitted) submitAttempt(true);
        }, 1000);
        return () => clearInterval(timer);
    }, [calcTime, submitted]);

    useEffect(() => {
        syncQueue();
        const periodic = setInterval(syncQueue, 10000);
        const heartbeat = setInterval(() => window.axios.post(`/attempts/${attempt.id}/heartbeat`).catch(() => {}), 30000);
        const onOnline = () => {
            setOnline(true);
            window.axios.post(`/attempts/${attempt.id}/integrity`, { type: 'reconnect' }).catch(() => {});
            syncQueue();
        };
        const onOffline = () => setOnline(false);
        const onVisibility = () => {
            if (document.hidden && !submitted) window.axios.post(`/attempts/${attempt.id}/integrity`, { type: 'tab_hidden' }).catch(() => {});
        };
        const onFullscreen = () => {
            if (!document.fullscreenElement && !submitted) window.axios.post(`/attempts/${attempt.id}/integrity`, { type: 'fullscreen_exit' }).catch(() => {});
        };
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);
        document.addEventListener('visibilitychange', onVisibility);
        document.addEventListener('fullscreenchange', onFullscreen);
        return () => {
            clearInterval(periodic);
            clearInterval(heartbeat);
            window.removeEventListener('online', onOnline);
            window.removeEventListener('offline', onOffline);
            document.removeEventListener('visibilitychange', onVisibility);
            document.removeEventListener('fullscreenchange', onFullscreen);
        };
    }, [attempt.id, submitted, syncQueue]);

    const select = async (optionId) => {
        if (submitted) return;
        const previous = answers[current.id] || [];
        const selected = current.type === 'multiple_choice'
            ? (previous.includes(optionId) ? previous.filter((id) => id !== optionId) : [...previous, optionId])
            : [optionId];
        if (!selected.length) return;
        const nextAnswers = { ...answers, [current.id]: selected };
        setAnswers(nextAnswers);
        localStorage.setItem(storageKey, JSON.stringify(nextAnswers));
        const payload = {
            questionId: current.id,
            option_ids: selected,
            idempotency_key: `${attempt.id}-${current.id}-${Date.now()}-${crypto.randomUUID?.() || Math.random()}`,
        };
        const queued = [...getQueue().filter((item) => item.questionId !== current.id), payload];
        writeQueue(queued);
        setSaveState('saving');
        if (!navigator.onLine) {
            setSaveState('offline');
            return;
        }
        await syncQueue();
    };

    const selectMatrix = async (rowIndex, optionId) => {
        if (submitted) return;
        const selected = { ...(answers[current.id] || {}), [rowIndex]: optionId };
        const nextAnswers = { ...answers, [current.id]: selected };
        setAnswers(nextAnswers);
        localStorage.setItem(storageKey, JSON.stringify(nextAnswers));
        const payload = { questionId: current.id, matrix_answers: selected, idempotency_key: `${attempt.id}-${current.id}-${Date.now()}-${crypto.randomUUID?.() || Math.random()}` };
        writeQueue([...getQueue().filter((item) => item.questionId !== current.id), payload]);
        setSaveState(navigator.onLine ? 'saving' : 'offline');
        if (navigator.onLine) await syncQueue();
    };

    const toggleFlag = async () => {
        if (submitted) return;
        setFlags((value) => ({ ...value, [current.id]: !value[current.id] }));
        try {
            await window.axios.put(`/attempts/${attempt.id}/flags/${current.id}`);
        } catch {
            setFlags((value) => ({ ...value, [current.id]: !value[current.id] }));
        }
    };

    const submitAttempt = async (automatic = false) => {
        if (submitted || syncing.current) return;
        setSubmitError('');
        const synchronized = await syncQueue();
        if (!synchronized && !automatic) {
            setSubmitError('Masih ada jawaban yang belum tersinkron. Periksa koneksi lalu coba kembali.');
            return;
        }
        try {
            await window.axios.post(`/attempts/${attempt.id}/submit`);
            localStorage.removeItem(queueKey);
            localStorage.removeItem(storageKey);
            router.visit('/results');
        } catch (error) {
            if (error.response?.status === 409 || automatic) router.visit('/results');
            else setSubmitError('Submit belum berhasil. Silakan coba kembali.');
        }
    };

    const isAnswered = (question) => question.type === 'matrix' ? Object.keys(answers[question.id] || {}).length === (question.matrix_rows || []).length : (answers[question.id] || []).length > 0;
    const answered = questions.filter(isAnswered).length;
    const flagged = Object.values(flags).filter(Boolean).length;
    const time = `${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`;

    if (!questions.length) return <div className="grid min-h-screen place-items-center">Paket ini belum memiliki soal.</div>;

    return <><Head title={`Ujian ${current.subject}`}/><div className="min-h-screen bg-[#f6f8fc]">
        <header className="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white px-4 md:px-7"><Logo/><div className="hidden text-center sm:block"><h1 className="text-sm font-extrabold">TKA {current.subject}</h1><p className="mt-1 text-[10px] text-slate-400">Nomor {index + 1} dari {questions.length}</p></div><div className="flex items-center gap-3"><div className={`rounded-xl border px-4 py-2 text-center ${remaining < 300 ? 'border-red-200 bg-red-50 text-red-700' : 'border-slate-200'}`}><p className="text-[9px] font-bold uppercase tracking-wider">Sisa waktu</p><strong className="font-mono text-lg">{time}</strong></div><button title="Layar penuh" onClick={() => document.documentElement.requestFullscreen?.()} className="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500"><Expand size={18}/></button></div></header>
        {fullscreenRequired && !document.fullscreenElement && <div className="mx-auto mt-4 flex max-w-7xl items-center justify-between rounded-xl bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800"><span>Sesi ini mewajibkan mode layar penuh.</span><button onClick={() => document.documentElement.requestFullscreen?.()} className="rounded-lg bg-amber-600 px-3 py-2 text-white">Aktifkan</button></div>}
        <main className="mx-auto grid max-w-[1500px] gap-5 p-4 lg:grid-cols-[1fr_280px] lg:p-7"><section className="card min-h-[650px] p-6 md:p-8"><div className="flex items-start justify-between gap-4"><div><span className="rounded-full bg-blue-50 px-3 py-1.5 text-[10px] font-bold text-blue-700">{current.domain}</span><h2 className="mt-5 text-sm font-extrabold">{current.type === 'multiple_choice' ? 'Pilih semua jawaban yang benar.' : current.type === 'matrix' ? 'Pilih satu kategori untuk setiap pernyataan.' : 'Pilih satu jawaban yang benar.'}</h2></div><SaveIndicator state={saveState}/></div>
            <RichQuestionContent content={current.content} className="mt-6 border-b border-slate-100 pb-7 text-[15px] font-medium leading-8"/>{current.image_path && <img src={`/storage/${current.image_path}`} alt="Ilustrasi soal" className="mt-5 max-h-80 rounded-xl object-contain"/>}
            {current.type === 'matrix' ? <div className="mt-6 overflow-x-auto"><table className="w-full min-w-[560px] text-sm"><thead><tr><th className="p-3 text-left">Pernyataan</th>{current.options.map((option) => <th key={option.id} className="p-3 text-center">{option.content}</th>)}</tr></thead><tbody>{current.matrix_rows.map((row, rowIndex) => <tr key={rowIndex} className="border-t"><td className="p-3">{row.statement}</td>{current.options.map((option) => <td key={option.id} className="p-3 text-center"><input type="radio" name={`matrix-${current.id}-${rowIndex}`} checked={Number(answers[current.id]?.[rowIndex]) === Number(option.id)} onChange={() => selectMatrix(rowIndex, option.id)}/></td>)}</tr>)}</tbody></table></div> : <div className="mt-6 space-y-3">{current.options.map((option, optionIndex) => { const selected = (answers[current.id] || []).includes(option.id); return <button disabled={submitted} key={option.id} onClick={() => select(option.id)} className={`flex w-full items-center gap-4 rounded-xl border p-4 text-left text-sm transition ${selected ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-100' : 'border-slate-200 bg-white hover:border-blue-300'}`}><span className={`grid h-8 w-8 shrink-0 place-items-center ${current.type === 'multiple_choice' ? 'rounded-lg' : 'rounded-full'} border text-xs font-extrabold ${selected ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-200 text-slate-500'}`}>{String.fromCharCode(65 + optionIndex)}</span><span>{option.content}</span></button>; })}</div>}
            <div className="mt-12 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6"><button onClick={() => setIndex((value) => Math.max(0, value - 1))} disabled={index === 0} className="btn-secondary px-4 py-2.5"><ArrowLeft size={16}/>Sebelumnya</button><button onClick={toggleFlag} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold ${flags[current.id] ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'}`}><Bookmark size={16} fill={flags[current.id] ? 'currentColor' : 'none'}/>{flags[current.id] ? 'Ditandai' : 'Ragu-ragu'}</button>{index < questions.length - 1 ? <button onClick={() => setIndex((value) => value + 1)} className="btn-primary px-5 py-2.5">Selanjutnya<ArrowRight size={16}/></button> : <button onClick={() => setConfirm(true)} className="btn-primary bg-emerald-600 px-5 py-2.5 hover:bg-emerald-700"><Send size={16}/>Akhiri Ujian</button>}</div></section>
            <aside className="space-y-5"><section className="card p-5"><div className="flex items-center justify-between"><h3 className="text-sm font-extrabold">Daftar Nomor</h3><span className="text-[10px] text-slate-400">{answered}/{questions.length} dijawab</span></div><div className="mt-5 grid grid-cols-5 gap-2">{questions.map((question, questionIndex) => <button key={question.id} onClick={() => setIndex(questionIndex)} className={`relative aspect-square rounded-lg text-xs font-bold transition ${questionIndex === index ? 'bg-blue-700 text-white ring-4 ring-blue-100' : flags[question.id] ? 'bg-amber-100 text-amber-700' : isAnswered(question) ? 'bg-emerald-100 text-emerald-700' : 'border border-slate-200 bg-white text-slate-500'}`}>{questionIndex + 1}</button>)}</div></section><section className="card p-5"><div className="flex items-center gap-3"><span className={`grid h-10 w-10 place-items-center rounded-xl ${online ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'}`}>{online ? <Wifi size={19}/> : <CloudOff size={19}/>}</span><div><p className="text-xs font-extrabold">{online ? 'Koneksi stabil' : 'Sedang offline'}</p><p className="mt-1 text-[10px] text-slate-400">{online ? 'Autosave aktif' : 'Jawaban diantre lokal'}</p></div></div></section></aside></main>
    </div>
    {confirm && <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-5 backdrop-blur-sm"><div className="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl"><span className="grid h-14 w-14 place-items-center rounded-2xl bg-amber-50 text-amber-600"><ShieldAlert size={28}/></span><h2 className="mt-5 text-xl font-extrabold">Kirim jawaban sekarang?</h2><p className="mt-3 text-sm leading-6 text-slate-500">Anda telah menjawab <strong>{answered} dari {questions.length}</strong> soal dan menandai <strong>{flagged}</strong> soal ragu-ragu. Jawaban tidak dapat diubah setelah dikirim.</p>{answered < questions.length && <div className="mt-4 flex gap-2 rounded-xl bg-amber-50 p-3 text-xs text-amber-700"><AlertTriangle size={16}/><span>Masih ada {questions.length - answered} soal belum dijawab.</span></div>}{submitError && <p className="mt-4 rounded-xl bg-red-50 p-3 text-xs font-semibold text-red-700">{submitError}</p>}<div className="mt-6 flex gap-3"><button onClick={() => setConfirm(false)} className="btn-secondary flex-1">Periksa Lagi</button><button onClick={() => submitAttempt(false)} className="btn-primary flex-1">Ya, Kirim</button></div></div></div>}</>;
}

function SaveIndicator({ state }) {
    const classes = state === 'saved' ? 'text-emerald-600' : state === 'saving' ? 'text-blue-600' : 'text-amber-600';
    return <div className={`flex items-center gap-2 text-xs font-semibold ${classes}`}>{state === 'saved' ? <><CheckCircle2 size={16}/>Tersimpan</> : state === 'saving' ? <><CloudUpload size={16}/>Menyimpan…</> : <><CloudOff size={16}/>Antre offline</>}</div>;
}

function readJson(key, fallback) {
    try { return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback)); } catch { return fallback; }
}
