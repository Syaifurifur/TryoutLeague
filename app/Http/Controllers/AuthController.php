<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Participant;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function loginPage()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        if (! $request->filled('login') && $request->filled('email')) {
            $request->merge(['login' => $request->input('email')]);
        }
        $data = $request->validate(['login' => ['required', 'string'], 'password' => ['required']]);
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        if (! Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'status' => 'active'], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Email/nomor HP atau kata sandi tidak sesuai.'])->onlyInput('login');
        }
        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);

        $destination = match ($request->user()->role) {
            'participant' => route('dashboard'),
            'school_operator' => route('school.dashboard'),
            'content_editor', 'reviewer' => route('admin.questions'),
            'proctor' => route('admin.monitoring'),
            default => route('admin.dashboard'),
        };

        return redirect()->intended($destination);
    }

    public function registerPage(Request $request)
    {
        return Inertia::render('Auth/Register', [
            'schools' => School::orderBy('name')->get(['id', 'name', 'city']),
            'events' => Event::query()->where('status', 'published')->where('allow_individual_registration', true)->where(fn ($query) => $query->whereNull('registration_open_at')->orWhere('registration_open_at', '<=', now()))->where(fn ($query) => $query->whereNull('registration_close_at')->orWhere('registration_close_at', '>=', now()))->get(['id', 'name', 'terms', 'registration_close_at']),
            'selectedEventId' => $request->integer('event') ?: null,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'], 'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'], 'password' => ['required', 'min:8', 'confirmed'],
            'school_id' => ['required', 'exists:schools,id'], 'nisn' => ['required', 'string', 'max:20'],
            'grade' => ['required', 'string'], 'province' => ['required', 'string'], 'city' => ['required', 'string'], 'consent' => ['accepted'],
        ]);

        $event = Event::query()->whereKey($data['event_id'])->where('status', 'published')->where('allow_individual_registration', true)->firstOrFail();
        if (($event->registration_open_at && now()->lt($event->registration_open_at)) || ($event->registration_close_at && now()->gt($event->registration_close_at))) {
            throw ValidationException::withMessages(['event_id' => 'Periode pendaftaran event sudah ditutup.']);
        }
        if ($event->quota && DB::table('event_registrations')->where('event_id', $event->id)->count() >= $event->quota) {
            throw ValidationException::withMessages(['event_id' => 'Kuota event sudah terpenuhi.']);
        }
        if (DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->where('r.event_id', $event->id)->where('p.nisn', $data['nisn'])->exists()) {
            throw ValidationException::withMessages(['nisn' => 'NISN sudah terdaftar pada event ini. Silakan masuk atau gunakan pemulihan akun.']);
        }

        DB::transaction(function () use ($data, $event, &$user) {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($lockedEvent->quota && DB::table('event_registrations')->where('event_id', $lockedEvent->id)->count() >= $lockedEvent->quota) {
                throw ValidationException::withMessages(['event_id' => 'Kuota event sudah terpenuhi.']);
            }
            if (DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->where('r.event_id', $lockedEvent->id)->where('p.nisn', $data['nisn'])->exists()) {
                throw ValidationException::withMessages(['nisn' => 'NISN sudah terdaftar pada event ini. Silakan masuk atau gunakan pemulihan akun.']);
            }
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'password' => Hash::make($data['password']), 'role' => 'participant', 'email_verified_at' => now()]);
            $participant = Participant::create(['user_id' => $user->id, 'school_id' => $data['school_id'], 'nisn' => $data['nisn'], 'grade' => $data['grade'], 'province' => $data['province'], 'city' => $data['city'], 'consent' => true]);
            $number = strtoupper($event->code).'-'.now()->format('y').'-'.strtoupper(Str::random(6));
            $registration = DB::table('event_registrations')->insertGetId(['event_id' => $event->id, 'participant_id' => $participant->id, 'participant_number' => $number, 'status' => 'eligible', 'source' => 'individual', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $required = DB::table('event_subjects')->where('event_id', $event->id)->where('is_required', true)->pluck('subject_id');
            foreach ($required as $subjectId) {
                DB::table('participant_subjects')->insert(['registration_id' => $registration, 'subject_id' => $subjectId, 'is_locked' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Pendaftaran berhasil. Nomor peserta Anda sudah dibuat.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
