<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\AdminPackageController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ParticipantImportController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\QuestionImportController;
use App\Http\Controllers\SchoolController;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $events = DB::table('events')->where('status', 'published')->orderBy('event_start_at')->get(['id', 'name', 'slug', 'description', 'banner_path', 'registration_open_at', 'registration_close_at', 'event_start_at', 'event_end_at', 'quota']);

    return Inertia::render('Landing', [
        'events' => $events,
        'subjectNames' => DB::table('subjects')->orderBy('category')->orderBy('name')->pluck('name'),
        'platformStats' => ['participants' => DB::table('event_registrations')->count(), 'schools' => DB::table('schools')->count(), 'packages' => DB::table('exam_packages')->where('status', 'published')->count()],
    ]);
})->name('home');
Route::get('/events/{event:slug}', function (Event $event) {
    abort_unless($event->status === 'published', 404);
    $subjects = DB::table('event_subjects as es')->join('subjects as s', 's.id', '=', 'es.subject_id')->where('es.event_id', $event->id)->get(['s.name', 'es.is_required']);

    return Inertia::render('EventDetail', ['event' => $event, 'subjects' => $subjects]);
})->name('events.show');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::get('/register', [AuthController::class, 'registerPage'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestPage'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetPage'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/verify-certificate/{code}', function (string $code) {
    $row = DB::table('certificates as c')->join('event_registrations as r', 'r.id', '=', 'c.registration_id')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->join('events as e', 'e.id', '=', 'r.event_id')->where('c.verification_code', $code)->select('c.*', 'u.name', 'r.participant_number', 'e.name as event')->first();

    return Inertia::render('CertificateVerify', ['certificate' => $row]);
})->name('certificate.verify');

Route::middleware(['auth', 'role:participant'])->group(function () {
    Route::get('/dashboard', [ParticipantController::class, 'dashboard'])->name('dashboard');
    Route::get('/schedule', [ParticipantController::class, 'schedule'])->name('schedule');
    Route::get('/subjects', [ParticipantController::class, 'subjects'])->name('subjects');
    Route::put('/subjects', [ParticipantController::class, 'updateSubjects'])->name('subjects.update');
    Route::get('/results', [ParticipantController::class, 'results'])->name('results');
    Route::get('/ranking', [ParticipantController::class, 'ranking'])->name('ranking');
    Route::get('/certificate', [ParticipantController::class, 'certificate'])->name('certificate');
    Route::get('/profile', [ParticipantController::class, 'profile'])->name('profile');
    Route::put('/profile', [ParticipantController::class, 'updateProfile'])->name('profile.update');
    Route::get('/notifications', [ParticipantController::class, 'notifications'])->name('notifications');
    Route::get('/help', fn () => Inertia::render('Participant/Help'))->name('help');
    Route::post('/exam-sessions/{session}/start', [ExamController::class, 'start'])->name('exam.start');
    Route::get('/attempts/{attempt}', [ExamController::class, 'show'])->name('exam.show');
    Route::put('/attempts/{attempt}/answers/{question}', [ExamController::class, 'answer'])->middleware('throttle:120,1')->name('exam.answer');
    Route::put('/attempts/{attempt}/flags/{question}', [ExamController::class, 'flag'])->name('exam.flag');
    Route::post('/attempts/{attempt}/integrity', [ExamController::class, 'integrity'])->middleware('throttle:30,1')->name('exam.integrity');
    Route::post('/attempts/{attempt}/heartbeat', [ExamController::class, 'heartbeat'])->middleware('throttle:12,1')->name('exam.heartbeat');
    Route::post('/attempts/{attempt}/submit', [ExamController::class, 'submit'])->name('exam.submit');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::middleware('role:admin,super_admin,admin_event,analyst')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    });
    Route::middleware('role:admin,super_admin,admin_event')->group(function () {
        Route::get('/participants', [AdminController::class, 'participants'])->name('participants');
        Route::patch('/participants/{registration}/status', [AdminController::class, 'setParticipantStatus'])->name('participants.status');
        Route::get('/participants/export', [ParticipantImportController::class, 'export'])->name('participants.export');
        Route::post('/participants/import/preview', [ParticipantImportController::class, 'preview'])->name('participant-imports.preview');
        Route::get('/participants/import/{participantImport}', [ParticipantImportController::class, 'show'])->name('participant-imports.show');
        Route::post('/participants/import/{participantImport}', [ParticipantImportController::class, 'store'])->name('participant-imports.store');
        Route::get('/events', [AdminController::class, 'events'])->name('events');
        Route::post('/events', [AdminEventController::class, 'store'])->name('events.store');
        Route::put('/events/{event}', [AdminEventController::class, 'update'])->name('events.update');
        Route::post('/events/{event}', [AdminEventController::class, 'update'])->name('events.update-upload');
        Route::patch('/events/{event}/status', [AdminEventController::class, 'setStatus'])->name('events.status');
        Route::post('/events/{event}/subjects', [AdminEventController::class, 'attachSubject'])->name('events.subjects.store');
        Route::delete('/events/{event}/subjects/{subject}', [AdminEventController::class, 'detachSubject'])->name('events.subjects.destroy');
        Route::post('/events/{event}/scoring-formulas', [AdminEventController::class, 'storeFormula'])->name('events.formulas.store');
        Route::put('/events/{event}/rules', [AdminEventController::class, 'updateRules'])->name('events.rules.update');
        Route::post('/events/{event}/finalize', [AdminEventController::class, 'finalizeResults'])->name('events.finalize');
        Route::post('/events/{event}/rescore', [AdminEventController::class, 'rescore'])->name('events.rescore');
        Route::post('/events/{event}/publish-results', [AdminEventController::class, 'publishResults'])->name('events.publish-results');
        Route::post('/events/{event}/packages', [AdminPackageController::class, 'store'])->name('packages.store');
        Route::post('/packages/{package}/publish', [AdminPackageController::class, 'publish'])->name('packages.publish');
        Route::get('/packages/{package}/preview', [AdminPackageController::class, 'preview'])->name('packages.preview');
        Route::post('/packages/{package}/sections', [AdminPackageController::class, 'storeSection'])->name('packages.sections.store');
        Route::post('/packages/{package}/version', [AdminPackageController::class, 'version'])->name('packages.version');
        Route::post('/packages/{package}/sessions', [AdminPackageController::class, 'storeSession'])->name('sessions.store');
        Route::get('/events/{event}/reports/results', [AdminReportController::class, 'results'])->name('reports.results');
    });
    Route::middleware('role:admin,super_admin,content_editor,reviewer')->group(function () {
        Route::get('/questions', [AdminController::class, 'questions'])->name('questions');
        Route::post('/questions', [AdminController::class, 'storeQuestion'])->name('questions.store');
        Route::post('/questions/{question}', [AdminController::class, 'updateQuestion'])->name('questions.update');
        Route::patch('/questions/{question}/status', [AdminController::class, 'setQuestionStatus'])->name('questions.status');
        Route::post('/questions/{question}/comments', [AdminController::class, 'commentQuestion'])->name('questions.comments');
        Route::post('/questions/import/preview', [QuestionImportController::class, 'preview'])->name('question-imports.preview');
        Route::post('/questions/import/spreadsheet/preview', [QuestionImportController::class, 'previewSpreadsheet'])->name('question-imports.spreadsheet-preview');
        Route::get('/questions/import/{questionImport}', [QuestionImportController::class, 'show'])->name('question-imports.show');
        Route::post('/questions/import/{questionImport}', [QuestionImportController::class, 'store'])->name('question-imports.store');
    });
    Route::middleware('role:admin,super_admin,admin_event,proctor')->group(function () {
        Route::get('/monitoring', [AdminController::class, 'monitoring'])->name('monitoring');
    });
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
        Route::post('/settings/subjects', [AdminSettingsController::class, 'storeSubject'])->name('settings.subjects.store');
        Route::put('/settings/subjects/{subject}', [AdminSettingsController::class, 'updateSubject'])->name('settings.subjects.update');
        Route::post('/settings/users', [AdminSettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::patch('/settings/users/{user}', [AdminSettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::post('/settings/schools', [AdminSettingsController::class, 'storeSchool'])->name('settings.schools.store');
        Route::put('/settings/schools/{school}', [AdminSettingsController::class, 'updateSchool'])->name('settings.schools.update');
        Route::post('/settings/certificate-templates', [AdminSettingsController::class, 'storeCertificateTemplate'])->name('settings.certificates.store');
        Route::post('/certificates/{certificate}/revoke', [AdminSettingsController::class, 'revokeCertificate'])->name('certificates.revoke');
        Route::get('/reports/audit', [AdminReportController::class, 'audit'])->name('reports.audit');
    });
});

Route::prefix('school')->name('school.')->middleware(['auth', 'role:school_operator'])->group(function () {
    Route::get('/', [SchoolController::class, 'dashboard'])->name('dashboard');
    Route::get('/participants/export', [ParticipantImportController::class, 'export'])->name('participants.export');
    Route::post('/participants/import/preview', [ParticipantImportController::class, 'preview'])->name('participant-imports.preview');
    Route::get('/participants/import/{participantImport}', [ParticipantImportController::class, 'show'])->name('participant-imports.show');
    Route::post('/participants/import/{participantImport}', [ParticipantImportController::class, 'store'])->name('participant-imports.store');
});

if (app()->environment('local')) {
    Route::get('/demo-login/{role}', function (string $role) {
        $email = $role === 'admin' ? 'admin@tkajuara.id' : 'peserta@tkajuara.id';
        abort_unless(in_array($role, ['admin', 'participant'], true), 404);
        Auth::loginUsingId(User::where('email', $email)->value('id'));
        request()->session()->regenerate();

        return redirect($role === 'admin' ? '/admin' : '/dashboard');
    })->name('demo.login');
    Route::get('/demo-exam', function () {
        $user = User::where('email', 'peserta@tkajuara.id')->firstOrFail();
        Auth::login($user);
        request()->session()->regenerate();
        $session = DB::table('exam_sessions as es')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('subjects as s', 's.id', '=', 'ep.subject_id')->where('s.code', 'MAT')->value('es.id');

        return app(ExamController::class)->start(request(), $session);
    });
}
