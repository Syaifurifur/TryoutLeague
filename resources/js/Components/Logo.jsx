export default function Logo({ compact=false, light=false }) {
    return <a href="/" className="inline-flex items-center gap-2.5 group">
        <span className={`grid h-10 w-10 place-items-center rounded-xl font-black text-xl ${light?'bg-white text-blue-700':'bg-blue-700 text-white'} shadow-sm`}>T</span>
        {!compact && <span className="leading-none"><strong className={`block text-xl font-extrabold tracking-tight ${light?'text-white':'text-slate-950'}`}>TKA <span className={light?'text-blue-200':'text-blue-700'}>JUARA</span></strong><small className={`text-[9px] font-bold tracking-[.16em] ${light?'text-blue-100':'text-slate-500'}`}>TRYOUT SMA/MA/SMK</small></span>}
    </a>
}
