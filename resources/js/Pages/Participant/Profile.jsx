import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Profile({ participant, user, ptnPrograms = [] }) {
    const form = useForm({
        name: user.name,
        phone: user.phone || '',
        grade: participant.grade || '',
        province: participant.province || '',
        city: participant.city || '',
        target_program_1_id: participant.target_program_1_id || '',
        target_program_2_id: participant.target_program_2_id || '',
    });

    return (
        <AppLayout title="Profil & Target PTN" subtitle="Perbarui data peserta dan pilihan prodi SNBT">
            <form
                className="card max-w-2xl p-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.put('/profile');
                }}
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Nama">
                        <input
                            className="field"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                    </Field>
                    <Field label="Email">
                        <input className="field bg-slate-50" value={user.email} disabled />
                    </Field>
                    <Field label="Nomor HP">
                        <input
                            className="field"
                            value={form.data.phone}
                            onChange={(event) => form.setData('phone', event.target.value)}
                        />
                    </Field>
                    <Field label="Kelas">
                        <select
                            className="field"
                            value={form.data.grade}
                            onChange={(event) => form.setData('grade', event.target.value)}
                        >
                            <option value="XI">XI</option>
                            <option value="XII">XII</option>
                            <option value="Alumni">Alumni</option>
                        </select>
                    </Field>
                    <Field label="Provinsi">
                        <input
                            className="field"
                            value={form.data.province}
                            onChange={(event) => form.setData('province', event.target.value)}
                        />
                    </Field>
                    <Field label="Kota/Kabupaten">
                        <input
                            className="field"
                            value={form.data.city}
                            onChange={(event) => form.setData('city', event.target.value)}
                        />
                    </Field>
                </div>

                <div className="mt-6 border-t pt-5">
                    <h3 className="text-sm font-extrabold text-[#092452]">Target PTN (Untuk Rasionalisasi SNBT)</h3>
                    <div className="mt-3 grid gap-4 sm:grid-cols-2">
                        <Field label="Pilihan PTN 1">
                            <select
                                className="field"
                                value={form.data.target_program_1_id}
                                onChange={(event) => form.setData('target_program_1_id', event.target.value)}
                            >
                                <option value="">— Pilih Prodi 1 —</option>
                                {ptnPrograms.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.university_code || p.university_name} • {p.program_name} ({Number(p.passing_score).toFixed(0)})
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Pilihan PTN 2">
                            <select
                                className="field"
                                value={form.data.target_program_2_id}
                                onChange={(event) => form.setData('target_program_2_id', event.target.value)}
                            >
                                <option value="">— Pilih Prodi 2 —</option>
                                {ptnPrograms.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.university_code || p.university_name} • {p.program_name} ({Number(p.passing_score).toFixed(0)})
                                    </option>
                                ))}
                            </select>
                        </Field>
                    </div>
                </div>

                {Object.keys(form.errors).length > 0 && (
                    <p className="mt-4 text-xs text-red-600">Periksa kembali data yang diisi.</p>
                )}
                <button disabled={form.processing} className="btn-primary mt-6">
                    Simpan Profil &amp; Target PTN
                </button>
            </form>
        </AppLayout>
    );
}

function Field({ label, children }) {
    return (
        <div>
            <label className="label">{label}</label>
            {children}
        </div>
    );
}
