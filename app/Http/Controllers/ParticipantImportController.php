<?php

namespace App\Http\Controllers;

use App\Models\ParticipantImport;
use App\Models\User;
use App\Services\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;

class ParticipantImportController extends Controller
{
    public function preview(Request $request, SpreadsheetReader $reader)
    {
        $data = $request->validate(['event_id' => ['required', 'exists:events,id'], 'school_id' => ['nullable', 'exists:schools,id'], 'file' => ['required', 'file', 'max:10240', 'extensions:xlsx,csv']]);
        $this->authorizeSchool($request, $data['school_id'] ?? null);
        $path = $data['file']->store('participant-imports');
        try {
            $rows = $reader->read($data['file']->getRealPath(), $data['file']->getClientOriginalExtension());
        } catch (RuntimeException $exception) {
            Storage::delete($path);
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        $seen = ['email' => [], 'phone' => [], 'nisn' => []];
        $parsed = collect($rows)->map(function ($row, $index) use ($data, &$seen) {
            $parsedRow = $this->validateRow($row, $index + 2, $data['school_id'] ?? null, (int) $data['event_id']);
            foreach (['email', 'phone', 'nisn'] as $field) {
                $value = mb_strtolower(trim((string) $parsedRow[$field]));
                if ($value !== '' && isset($seen[$field][$value])) {
                    $parsedRow['errors'][] = ucfirst($field).' duplikat pada file (baris '.$seen[$field][$value].').';
                } elseif ($value !== '') {
                    $seen[$field][$value] = $parsedRow['row'];
                }
            }
            $parsedRow['valid'] = $parsedRow['errors'] === [];

            return $parsedRow;
        })->values()->all();
        $valid = collect($parsed)->where('valid', true)->count();
        $import = ParticipantImport::create(['user_id' => $request->user()->id, 'event_id' => $data['event_id'], 'school_id' => $data['school_id'] ?? null, 'original_filename' => $data['file']->getClientOriginalName(), 'storage_path' => $path, 'status' => 'previewed', 'total_rows' => count($parsed), 'valid_rows' => $valid, 'invalid_rows' => count($parsed) - $valid, 'payload' => $parsed]);

        return redirect()->route($request->user()->role === 'school_operator' ? 'school.participant-imports.show' : 'admin.participant-imports.show', $import);
    }

    public function show(Request $request, ParticipantImport $participantImport)
    {
        abort_unless($participantImport->user_id === $request->user()->id || $request->user()->role === 'super_admin', 403);

        return Inertia::render('Admin/ParticipantImportPreview', ['participantImport' => $participantImport, 'event' => DB::table('events')->find($participantImport->event_id), 'school' => $participantImport->school_id ? DB::table('schools')->find($participantImport->school_id) : null]);
    }

    public function store(Request $request, ParticipantImport $participantImport)
    {
        abort_unless($participantImport->user_id === $request->user()->id || $request->user()->role === 'super_admin', 403);
        $count = DB::transaction(function () use ($participantImport) {
            $locked = ParticipantImport::query()->lockForUpdate()->findOrFail($participantImport->id);
            if ($locked->status !== 'previewed') {
                throw ValidationException::withMessages(['import' => 'Import sudah diproses.']);
            }
            $count = 0;
            foreach (collect($locked->payload)->where('valid', true) as $row) {
                $user = User::create(['name' => $row['name'], 'email' => $row['email'], 'phone' => $row['phone'], 'role' => 'participant', 'status' => 'active', 'password' => Hash::make($row['password'] ?: Str::password(12))]);
                $participantId = DB::table('participants')->insertGetId(['user_id' => $user->id, 'school_id' => $row['school_id'], 'nisn' => $row['nisn'], 'grade' => $row['grade'], 'province' => $row['province'], 'city' => $row['city'], 'consent' => true, 'created_at' => now(), 'updated_at' => now()]);
                $number = 'TKA-'.$locked->event_id.'-'.strtoupper(Str::random(8));
                $registrationId = DB::table('event_registrations')->insertGetId(['event_id' => $locked->event_id, 'participant_id' => $participantId, 'participant_number' => $number, 'source' => 'school', 'status' => 'eligible', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                foreach (DB::table('event_subjects')->where('event_id', $locked->event_id)->where('is_required', true)->pluck('subject_id') as $subjectId) {
                    DB::table('participant_subjects')->insert(['registration_id' => $registrationId, 'subject_id' => $subjectId, 'is_locked' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
                $count++;
            }
            $locked->update(['status' => 'completed', 'imported_at' => now()]);

            return $count;
        });

        return redirect()->route($request->user()->role === 'school_operator' ? 'school.dashboard' : 'admin.participants')->with('success', "{$count} peserta berhasil diimpor.");
    }

    public function export(Request $request)
    {
        $schoolId = $request->user()->role === 'school_operator' ? $request->user()->school_id : $request->integer('school_id');
        $rows = DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->leftJoin('schools as sc', 'sc.id', '=', 'p.school_id')->when($request->event_id, fn ($query, $event) => $query->where('r.event_id', $event))->when($schoolId, fn ($query) => $query->where('p.school_id', $schoolId))->select('r.participant_number', 'u.name', 'u.email', 'u.phone', 'p.nisn', 'sc.name as school', 'p.grade', 'p.province', 'p.city', 'r.status', DB::raw("(select count(*) from exam_attempts a where a.registration_id=r.id and a.status='scored') as completed_attempts"), DB::raw("(select AVG(s.scaled_score) from scores s join exam_attempts a on a.id=s.attempt_id where a.registration_id=r.id and s.status='finalized') as average_score"))->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['nomor_peserta', 'nama', 'email', 'no_hp', 'nisn', 'sekolah', 'kelas', 'provinsi', 'kota', 'status', 'ujian_selesai', 'rata_rata_skor']);
            foreach ($rows as $row) {
                fputcsv($out, (array) $row);
            }
            fclose($out);
        }, 'rekap-peserta-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validateRow(array $row, int $number, ?int $defaultSchoolId, int $eventId): array
    {
        $schoolId = $defaultSchoolId;
        if (! $schoolId && ($row['npsn'] ?? '')) {
            $schoolId = DB::table('schools')->where('npsn', $row['npsn'])->value('id');
        }
        if (! $schoolId && ($row['sekolah'] ?? '')) {
            $schoolId = DB::table('schools')->where('name', $row['sekolah'])->value('id');
        }
        $errors = [];
        foreach (['nama', 'email', 'no_hp', 'nisn', 'kelas', 'provinsi', 'kota'] as $field) {
            if (! ($row[$field] ?? null)) {
                $errors[] = "Kolom {$field} wajib diisi.";
            }
        }
        if (($row['email'] ?? '') && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (($row['email'] ?? '') && DB::table('users')->where('email', $row['email'])->exists()) {
            $errors[] = 'Email sudah terdaftar.';
        }
        if (($row['no_hp'] ?? '') && DB::table('users')->where('phone', $row['no_hp'])->exists()) {
            $errors[] = 'Nomor HP sudah terdaftar.';
        }
        if (($row['nisn'] ?? '') && DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->where('r.event_id', $eventId)->where('p.nisn', $row['nisn'])->exists()) {
            $errors[] = 'NISN sudah terdaftar pada event.';
        }
        if (! $schoolId) {
            $errors[] = 'Sekolah tidak ditemukan; isi NPSN atau pilih sekolah tujuan.';
        }

        return ['row' => $number, 'name' => $row['nama'] ?? '', 'email' => $row['email'] ?? '', 'phone' => $row['no_hp'] ?? '', 'nisn' => $row['nisn'] ?? '', 'grade' => $row['kelas'] ?? '', 'province' => $row['provinsi'] ?? '', 'city' => $row['kota'] ?? '', 'password' => $row['password'] ?? '', 'school_id' => $schoolId, 'valid' => $errors === [], 'errors' => $errors];
    }

    private function authorizeSchool(Request $request, ?int $schoolId): void
    {
        if ($request->user()->role === 'school_operator') {
            abort_unless($request->user()->school_id && $request->user()->school_id === $schoolId, 403);
        }
    }
}
