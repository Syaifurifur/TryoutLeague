<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrialEventSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $adminId = DB::table('users')->whereIn('role', ['super_admin', 'admin'])->orderBy('id')->value('id');
            $event = DB::table('events')->where('code', 'SIMULASI-TKA-01')->first();

            if (! $event) {
                $eventId = DB::table('events')->insertGetId([
                    'code' => 'SIMULASI-TKA-01',
                    'name' => 'Simulasi Pelaksanaan Tryout TKA',
                    'slug' => 'simulasi-pelaksanaan-tryout-tka',
                    'description' => 'Event khusus untuk mencoba seluruh alur pelaksanaan tryout: memilih mata uji, memulai sesi, autosave, submit, scoring, dan publikasi hasil.',
                    'terms' => 'Event simulasi. Jawaban dan nilai hanya digunakan untuk pengujian sistem.',
                    'registration_open_at' => $now->copy()->subDay(),
                    'registration_close_at' => $now->copy()->addDays(7),
                    'subject_selection_open_at' => $now->copy()->subDay(),
                    'subject_selection_close_at' => $now->copy()->addDays(7),
                    'event_start_at' => $now->copy()->subMinutes(30),
                    'event_end_at' => $now->copy()->addDays(7),
                    'result_published_at' => null,
                    'status' => 'published',
                    'quota' => 100,
                    'allow_individual_registration' => true,
                    'allow_school_registration' => true,
                    'settings' => json_encode([
                        'max_optional_subjects' => 0,
                        'ranking' => true,
                        'ranking_scopes' => ['national', 'province', 'city', 'school', 'subject'],
                        'ranking_privacy' => 'full_name',
                        'tie_break' => ['score', 'correct_count', 'submitted_at'],
                        'certificate' => true,
                        'show_discussion' => true,
                        'fullscreen_required' => false,
                        'max_concurrent_sessions' => 1,
                    ]),
                    'created_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $event = DB::table('events')->find($eventId);
            }

            if (! DB::table('scoring_formulas')->where('event_id', $event->id)->exists()) {
                DB::table('scoring_formulas')->insert([
                    'event_id' => $event->id,
                    'version' => 1,
                    'correct_points' => 1,
                    'wrong_points' => 0,
                    'unanswered_points' => 0,
                    'scale_min' => 200,
                    'scale_max' => 800,
                    'partial_scoring' => true,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $subjects = DB::table('subjects')->whereIn('code', ['BIN', 'MAT', 'BIG'])->orderByRaw("FIELD(code, 'BIN', 'MAT', 'BIG')")->get();
            foreach ($subjects as $subject) {
                DB::table('event_subjects')->insertOrIgnore([
                    'event_id' => $event->id,
                    'subject_id' => $subject->id,
                    'is_required' => true,
                ]);

                $package = DB::table('exam_packages')->where('event_id', $event->id)->where('subject_id', $subject->id)->where('version', 1)->first();
                if (! $package) {
                    $packageId = DB::table('exam_packages')->insertGetId([
                        'event_id' => $event->id,
                        'subject_id' => $subject->id,
                        'name' => 'Paket Simulasi '.$subject->name,
                        'version' => 1,
                        'duration_minutes' => 15,
                        'status' => 'published',
                        'randomize_questions' => true,
                        'randomize_options' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $package = DB::table('exam_packages')->find($packageId);
                }

                $section = DB::table('exam_sections')->where('exam_package_id', $package->id)->orderBy('position')->first();
                if (! $section) {
                    $sectionId = DB::table('exam_sections')->insertGetId([
                        'exam_package_id' => $package->id,
                        'name' => 'Bagian Utama',
                        'position' => 1,
                        'duration_minutes' => 15,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $section = DB::table('exam_sections')->find($sectionId);
                }

                $questions = DB::table('questions')->where('subject_id', $subject->id)->where('status', 'approved')->orderBy('id')->limit(10)->pluck('id');
                foreach ($questions as $position => $questionId) {
                    DB::table('exam_package_questions')->insertOrIgnore([
                        'exam_package_id' => $package->id,
                        'exam_section_id' => $section->id,
                        'question_id' => $questionId,
                        'position' => $position + 1,
                    ]);
                }

                if (! DB::table('exam_sessions')->where('exam_package_id', $package->id)->exists()) {
                    DB::table('exam_sessions')->insert([
                        'exam_package_id' => $package->id,
                        'name' => 'Sesi Simulasi '.$subject->name,
                        'starts_at' => $now->copy()->subMinutes(30),
                        'ends_at' => $now->copy()->addDays(7),
                        'max_attempts' => 1,
                        'status' => 'open',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $participantId = DB::table('participants as p')->join('users as u', 'u.id', '=', 'p.user_id')->where('u.email', 'peserta@tkajuara.id')->value('p.id');
            if ($participantId) {
                $registration = DB::table('event_registrations')->where('event_id', $event->id)->where('participant_id', $participantId)->first();
                if (! $registration) {
                    $registrationId = DB::table('event_registrations')->insertGetId([
                        'event_id' => $event->id,
                        'participant_id' => $participantId,
                        'participant_number' => 'SIM-TKA-000001',
                        'source' => 'individual',
                        'status' => 'eligible',
                        'verified_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $registration = DB::table('event_registrations')->find($registrationId);
                }
                foreach ($subjects as $subject) {
                    DB::table('participant_subjects')->insertOrIgnore([
                        'registration_id' => $registration->id,
                        'subject_id' => $subject->id,
                        'is_locked' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }
}
