<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings', [
            'subjects' => DB::table('subjects')->orderBy('name')->get(),
            'users' => DB::table('users')->leftJoin('schools', 'schools.id', '=', 'users.school_id')->where('users.role', '!=', 'participant')->select('users.id', 'users.name', 'users.email', 'users.role', 'users.status', 'users.school_id', 'schools.name as school')->get(),
            'schools' => DB::table('schools')->orderBy('name')->get(['id', 'name']),
            'templates' => DB::table('certificate_templates')->orderByDesc('is_default')->get(),
            'certificates' => DB::table('certificates as c')->join('event_registrations as r', 'r.id', '=', 'c.registration_id')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->join('events as e', 'e.id', '=', 'r.event_id')->latest('c.issued_at')->limit(30)->get(['c.id', 'c.verification_code', 'c.issued_at', 'c.revoked_at', 'u.name', 'e.name as event']),
            'auditLogs' => DB::table('audit_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')->latest('a.created_at')->limit(50)->get(['a.id', 'a.action', 'a.entity_type', 'a.entity_id', 'a.ip_address', 'a.created_at', 'u.name']),
        ]);
    }

    public function storeSubject(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'max:30', 'unique:subjects,code'], 'name' => ['required', 'max:255'], 'category' => ['required', Rule::in(['required', 'optional'])], 'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']]);
        DB::table('subjects')->insert([...$data, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Mata uji berhasil dibuat.');
    }

    public function updateSubject(Request $request, int $subject)
    {
        $data = $request->validate(['code' => ['required', 'max:30', Rule::unique('subjects')->ignore($subject)], 'name' => ['required', 'max:255'], 'category' => ['required', Rule::in(['required', 'optional'])], 'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']]);
        DB::table('subjects')->where('id', $subject)->update([...$data, 'updated_at' => now()]);

        return back()->with('success', 'Mata uji diperbarui.');
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'max:120'], 'email' => ['required', 'email', 'unique:users,email'], 'phone' => ['nullable', 'unique:users,phone'], 'password' => ['required', 'min:8'], 'role' => ['required', Rule::in(['super_admin', 'admin', 'admin_event', 'content_editor', 'reviewer', 'proctor', 'school_operator', 'analyst'])], 'school_id' => ['nullable', 'exists:schools,id']]);
        if ($data['role'] === 'school_operator' && empty($data['school_id'])) {
            return back()->withErrors(['school_id' => 'Operator sekolah wajib ditautkan ke sekolah.']);
        }
        User::create([...$data, 'status' => 'active']);

        return back()->with('success', 'Pengguna operasional berhasil dibuat.');
    }

    public function storeSchool(Request $request)
    {
        $data = $this->validateSchool($request);
        DB::table('schools')->insert([...$data, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Master sekolah berhasil ditambahkan.');
    }

    public function updateSchool(Request $request, int $school)
    {
        $data = $this->validateSchool($request, $school);
        DB::table('schools')->where('id', $school)->update([...$data, 'updated_at' => now()]);

        return back()->with('success', 'Master sekolah diperbarui.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(['super_admin', 'admin', 'admin_event', 'content_editor', 'reviewer', 'proctor', 'school_operator', 'analyst'])], 'status' => ['required', Rule::in(['active', 'inactive'])], 'school_id' => ['nullable', 'exists:schools,id']]);
        if ($data['role'] === 'school_operator' && empty($data['school_id'])) {
            return back()->withErrors(['school_id' => 'Operator sekolah wajib ditautkan ke sekolah.']);
        }
        $user->update($data);

        return back()->with('success', 'Role dan akses pengguna diperbarui.');
    }

    public function storeCertificateTemplate(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'max:255'], 'title' => ['required', 'max:255'], 'subtitle' => ['nullable', 'max:255'], 'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'body_text' => ['nullable', 'max:1000'], 'signatory_name' => ['nullable', 'max:255'], 'signatory_title' => ['nullable', 'max:255'], 'is_default' => ['required', 'boolean']]);
        DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                DB::table('certificate_templates')->update(['is_default' => false]);
            }
            DB::table('certificate_templates')->insert([...$data, 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Template sertifikat disimpan.');
    }

    public function revokeCertificate(int $certificate)
    {
        DB::table('certificates')->where('id', $certificate)->update(['revoked_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Sertifikat berhasil dicabut.');
    }

    private function validateSchool(Request $request, ?int $schoolId = null): array
    {
        return $request->validate(['npsn' => ['required', 'max:20', Rule::unique('schools')->ignore($schoolId)], 'name' => ['required', 'max:255'], 'education_level' => ['required', 'in:SMA,MA,SMK'], 'ownership' => ['nullable', 'in:Negeri,Swasta'], 'province' => ['required', 'max:120'], 'city' => ['required', 'max:120'], 'district' => ['nullable', 'max:120'], 'address' => ['nullable', 'max:1000'], 'pic_name' => ['nullable', 'max:120'], 'pic_phone' => ['nullable', 'max:30']]);
    }
}
