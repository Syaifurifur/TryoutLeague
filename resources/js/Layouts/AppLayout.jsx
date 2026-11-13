import { Head, Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboard, BookOpenCheck, CalendarDays, ClipboardCheck, Trophy, Award, UserRound, CircleHelp, LogOut, Bell, Users, LibraryBig, MonitorDot, Settings2, Menu, X } from 'lucide-react';
import { useState } from 'react';
import Logo from '../Components/Logo';

const participantNav=[['/dashboard','Beranda',LayoutDashboard],['/subjects','Mata Uji',BookOpenCheck],['/schedule','Jadwal & Tryout',CalendarDays],['/results','Hasil Tryout',ClipboardCheck],['/ranking','Ranking',Trophy],['/certificate','Sertifikat',Award],['/profile','Profil',UserRound]];
const adminNav=[['/admin','Dashboard',LayoutDashboard,['admin','super_admin','admin_event','analyst']],['/admin/events','Event & Jadwal',CalendarDays,['admin','super_admin','admin_event']],['/admin/participants','Peserta',Users,['admin','super_admin','admin_event']],['/admin/questions','Bank Soal',LibraryBig,['admin','super_admin','content_editor','reviewer']],['/admin/monitoring','Monitoring',MonitorDot,['admin','super_admin','admin_event','proctor']],['/admin/settings','Pengaturan',Settings2,['admin','super_admin']]];
const schoolNav=[['/school','Dashboard Sekolah',LayoutDashboard],['/school','Peserta',Users]];

export default function AppLayout({title,subtitle,children,admin=false,actions}){
    const {auth,flash}=usePage().props; const [open,setOpen]=useState(false); const nav=auth.user?.role==='school_operator'?schoolNav:(admin?adminNav.filter(item=>item[3].includes(auth.user?.role)):participantNav);
    const current=window.location.pathname;
    const isParticipant=auth.user?.role==='participant'; const notificationHref=isParticipant?'/notifications':(auth.user?.role==='school_operator'?'/school':'/admin/monitoring');
    return <div className="min-h-screen bg-[#f7f9fd] text-slate-900">
        <Head title={title}/>
        <aside className={`fixed inset-y-0 left-0 z-40 w-[248px] border-r border-slate-200 bg-white transition-transform lg:translate-x-0 ${open?'translate-x-0':'-translate-x-full'}`}>
            <div className="flex h-20 items-center justify-between px-6"><Logo/><button className="lg:hidden" onClick={()=>setOpen(false)}><X size={20}/></button></div>
            {admin&&<div className="mx-4 mb-4 rounded-xl bg-blue-700 px-4 py-2 text-center text-[10px] font-extrabold tracking-[.2em] text-white">ADMIN CONSOLE</div>}
            <nav className="space-y-1 px-3">{nav.map(([href,label,Icon],i)=>{const active=href==='/admin'?current==='/admin':(href==='/dashboard'?current==='/dashboard':current.startsWith(href));return <Link key={label+''+i} href={href} className={`flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${active?'bg-blue-50 text-blue-700':'text-slate-500 hover:bg-slate-50 hover:text-slate-900'}`}><Icon size={18}/>{label}</Link>})}</nav>
            <div className="absolute bottom-5 left-3 right-3 border-t border-slate-100 pt-4">
                {isParticipant&&<><Link href="/profile" className="nav-secondary"><UserRound size={17}/>Profil</Link><Link href="/help" className="nav-secondary"><CircleHelp size={17}/>Bantuan</Link></>}
                <button onClick={()=>router.post('/logout')} className="nav-secondary w-full"><LogOut size={17}/>Keluar</button>
            </div>
        </aside>
        {open&&<button className="fixed inset-0 z-30 bg-slate-950/30 lg:hidden" onClick={()=>setOpen(false)}/>} 
        <main className="lg:pl-[248px]">
            <header className="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur md:px-8">
                <div className="flex items-center gap-3"><button className="rounded-lg p-2 hover:bg-slate-100 lg:hidden" onClick={()=>setOpen(true)}><Menu/></button><div><h1 className="text-xl font-extrabold tracking-tight">{title}</h1>{subtitle&&<p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>}</div></div>
                <div className="flex items-center gap-3"><Link href={notificationHref} title={isParticipant?'Notifikasi':'Monitoring'} className="relative grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-500"><Bell size={18}/><span className="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"/></Link><div className="hidden text-right sm:block"><p className="text-sm font-bold">{auth.user?.name}</p><p className="text-[11px] capitalize text-slate-400">{auth.user?.role?.replace('_',' ')}</p></div><div className="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-400 font-extrabold text-white">{auth.user?.name?.charAt(0)}</div></div>
            </header>
            <div className="p-4 md:p-8">{flash?.success&&<div className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{flash.success}</div>}{flash?.error&&<div className="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{flash.error}</div>}{actions&&<div className="mb-5 flex justify-end">{actions}</div>}{children}</div>
        </main>
    </div>
}
