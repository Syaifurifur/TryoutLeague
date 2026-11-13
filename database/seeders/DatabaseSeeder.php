<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $admin = User::create(['name' => 'Super Admin', 'email' => 'admin@tkajuara.id', 'phone' => '081200000001', 'role' => 'super_admin', 'status' => 'active', 'email_verified_at' => $now, 'password' => Hash::make('password')]);
        $student = User::create(['name' => 'Ahmad Fauzan', 'email' => 'peserta@tkajuara.id', 'phone' => '081200000002', 'role' => 'participant', 'status' => 'active', 'email_verified_at' => $now, 'password' => Hash::make('password')]);
        $schools = [
            ['npsn' => '20100101', 'name' => 'SMAN 1 Bekasi', 'education_level' => 'SMA', 'ownership' => 'Negeri', 'province' => 'Jawa Barat', 'city' => 'Kota Bekasi', 'address' => 'Jl. KH. Agus Salim, Bekasi'],
            ['npsn' => '20100102', 'name' => 'SMAN 3 Jakarta', 'education_level' => 'SMA', 'ownership' => 'Negeri', 'province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan', 'address' => 'Jl. Setiabudi, Jakarta'],
            ['npsn' => '20100103', 'name' => 'SMAN 8 Bandung', 'education_level' => 'SMA', 'ownership' => 'Negeri', 'province' => 'Jawa Barat', 'city' => 'Kota Bandung', 'address' => 'Jl. Solontongan, Bandung'],
            ['npsn' => '20100104', 'name' => 'SMAN 1 Yogyakarta', 'education_level' => 'SMA', 'ownership' => 'Negeri', 'province' => 'DI Yogyakarta', 'city' => 'Kota Yogyakarta', 'address' => 'Jl. HOS Cokroaminoto, Yogyakarta'],
        ];
        foreach ($schools as &$school) {
            $school['created_at'] = $now;
            $school['updated_at'] = $now;
        } unset($school);
        DB::table('schools')->insert($schools);
        $mainSchoolId = DB::table('schools')->where('name', 'SMAN 1 Bekasi')->value('id');
        User::create(['name' => 'Operator SMAN 1 Bekasi', 'email' => 'sekolah@tkajuara.id', 'phone' => '081200000003', 'role' => 'school_operator', 'school_id' => $mainSchoolId, 'status' => 'active', 'email_verified_at' => $now, 'password' => Hash::make('password')]);
        $participantId = DB::table('participants')->insertGetId(['user_id' => $student->id, 'school_id' => $mainSchoolId, 'nisn' => '0098765432', 'grade' => 'XII', 'major' => 'IPA', 'province' => 'Jawa Barat', 'city' => 'Kota Bekasi', 'consent' => true, 'created_at' => $now, 'updated_at' => $now]);
        $eventId = DB::table('events')->insertGetId(['code' => 'TKA2026', 'name' => 'Tryout Nasional TKA 2026', 'slug' => 'tryout-tka-2026', 'description' => 'Simulasi terukur untuk membantu siswa SMA/MA/SMK mengenali kemampuan dan mempersiapkan TKA.', 'terms' => 'Peserta menyetujui pemrosesan data untuk penyelenggaraan tryout dan wajib menjaga kerahasiaan akun.', 'registration_open_at' => $now->copy()->subMonth(), 'registration_close_at' => $now->copy()->addMonth(), 'subject_selection_open_at' => $now->copy()->subMonth(), 'subject_selection_close_at' => $now->copy()->addWeeks(2), 'event_start_at' => $now->copy()->subDay(), 'event_end_at' => $now->copy()->addMonth(), 'result_published_at' => $now->copy()->subDay(), 'status' => 'published', 'quota' => 50000, 'allow_individual_registration' => true, 'allow_school_registration' => true, 'settings' => json_encode(['max_optional_subjects' => 2, 'ranking' => true, 'ranking_scopes' => ['national', 'province', 'city', 'school', 'subject'], 'ranking_privacy' => 'full_name', 'tie_break' => ['score', 'correct_count', 'submitted_at'], 'certificate' => true, 'show_discussion' => false, 'fullscreen_required' => false, 'max_concurrent_sessions' => 1]), 'created_by' => $admin->id, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('scoring_formulas')->insert(['event_id' => $eventId, 'version' => 1, 'correct_points' => 1, 'wrong_points' => 0, 'unanswered_points' => 0, 'scale_min' => 200, 'scale_max' => 800, 'partial_scoring' => true, 'status' => 'active', 'created_by' => $admin->id, 'created_at' => $now, 'updated_at' => $now]);
        $regId = DB::table('event_registrations')->insertGetId(['event_id' => $eventId, 'participant_id' => $participantId, 'participant_number' => 'TKA-26-000123', 'source' => 'individual', 'status' => 'eligible', 'verified_at' => $now->copy()->subWeeks(2), 'created_at' => $now, 'updated_at' => $now]);
        $subjectRows = [['BIN', 'Bahasa Indonesia', 'required', '#18b985'], ['MAT', 'Matematika', 'required', '#2aa8da'], ['BIG', 'Bahasa Inggris', 'required', '#7a62e7'], ['EKO', 'Ekonomi', 'optional', '#e5942e'], ['FIS', 'Fisika', 'optional', '#e65275']];
        $subjectIds = [];
        foreach ($subjectRows as [$code,$name,$category,$color]) {
            $id = DB::table('subjects')->insertGetId(['code' => $code, 'name' => $name, 'category' => $category, 'color' => $color, 'created_at' => $now, 'updated_at' => $now]);
            $subjectIds[$code] = $id;
            DB::table('event_subjects')->insert(['event_id' => $eventId, 'subject_id' => $id, 'is_required' => $category === 'required', 'max_choices' => $category === 'optional' ? 2 : null]);
            if ($category === 'required' || $code === 'EKO') {
                DB::table('participant_subjects')->insert(['registration_id' => $regId, 'subject_id' => $id, 'is_locked' => $category === 'required', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $questionSets = [
            ['MAT', 'Aljabar', 'medium', 'Rata-rata penjualan sebuah toko selama 5 hari adalah 132 unit. Jika penjualan empat hari pertama adalah 120, 150, 100, dan 180 unit, berapa penjualan hari kelima?', ['90', '100', '110', '120'], 2, 'Jumlah total 5 hari = 660. Empat hari pertama = 550, sehingga hari kelima = 110.'],
            ['MAT', 'Statistika', 'easy', 'Median dari data 4, 7, 8, 9, 12, 15, 18 adalah …', ['8', '9', '10', '12'], 1, 'Ada tujuh data, sehingga median adalah data keempat, yaitu 9.'],
            ['MAT', 'Geometri', 'medium', 'Sebuah persegi memiliki keliling 48 cm. Luas persegi tersebut adalah …', ['96 cm²', '120 cm²', '144 cm²', '196 cm²'], 2, 'Sisi persegi = 48 ÷ 4 = 12 cm, maka luas = 12 × 12 = 144 cm².'],
            ['MAT', 'Penalaran', 'hard', 'Jika 2x + 3y = 19 dan x − y = 2, nilai x + y adalah …', ['5', '6', '7', '8'], 3, 'Dari x=y+2, substitusi menghasilkan 5y=15, y=3 dan x=5, sehingga x+y=8.'],
            ['MAT', 'Aljabar', 'easy', 'Nilai dari 3² + 4² adalah …', ['7', '12', '25', '49'], 2, '3² + 4² = 9 + 16 = 25.'],
            ['BIN', 'Literasi', 'medium', 'Kalimat efektif terdapat pada pilihan …', ['Para siswa-siswa sedang belajar.', 'Siswa sedang mempelajari materi TKA.', 'Agar supaya nilainya meningkat.', 'Mereka saling tolong-menolong bersama.'], 1, 'Kalimat B tidak mengandung pemborosan kata maupun struktur yang rancu.'],
            ['BIG', 'Reading', 'medium', 'Choose the closest meaning of “resilient”.', ['fragile', 'adaptable', 'silent', 'ordinary'], 1, 'Resilient means able to recover or adapt well after difficulty.'],
            ['EKO', 'Ekonomi Mikro', 'easy', 'Ketika harga naik dan faktor lain tetap, jumlah barang yang diminta cenderung …', ['naik', 'tetap', 'turun', 'tidak terbatas'], 2, 'Sesuai hukum permintaan, kenaikan harga menurunkan jumlah yang diminta.'],
        ];
        foreach ($questionSets as $i => [$code,$domain,$difficulty,$content,$options,$correct,$explanation]) {
            $qid = DB::table('questions')->insertGetId(['code' => sprintf('%s-%06d', $code, $i + 1), 'subject_id' => $subjectIds[$code], 'domain' => $domain, 'difficulty' => $difficulty, 'type' => 'single_choice', 'content' => $content, 'explanation' => $explanation, 'weight' => 1, 'version' => 1, 'status' => 'approved', 'created_by' => $admin->id, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($options as $j => $option) {
                DB::table('question_options')->insert(['question_id' => $qid, 'label' => chr(65 + $j), 'content' => $option, 'is_correct' => $j === $correct, 'position' => $j + 1]);
            }
        }
        $subjectNames = collect($subjectRows)->mapWithKeys(fn ($s) => [$s[0] => $s[1]]);
        foreach (['BIN', 'MAT', 'BIG', 'EKO'] as $idx => $code) {
            $packageId = DB::table('exam_packages')->insertGetId(['event_id' => $eventId, 'subject_id' => $subjectIds[$code], 'name' => 'TKA '.$subjectNames[$code], 'version' => 1, 'duration_minutes' => $code === 'MAT' ? 50 : 45, 'status' => 'published', 'randomize_questions' => true, 'randomize_options' => true, 'created_at' => $now, 'updated_at' => $now]);
            $qids = DB::table('questions')->where('subject_id', $subjectIds[$code])->pluck('id');
            foreach ($qids as $pos => $qid) {
                DB::table('exam_package_questions')->insert(['exam_package_id' => $packageId, 'question_id' => $qid, 'position' => $pos + 1]);
            }
            $sessionId = DB::table('exam_sessions')->insertGetId(['exam_package_id' => $packageId, 'name' => 'Sesi Utama '.($idx + 1), 'starts_at' => $now->copy()->subHour(), 'ends_at' => $now->copy()->addMonth(), 'max_attempts' => 1, 'status' => 'open', 'created_at' => $now, 'updated_at' => $now]);
            if ($code !== 'MAT') {
                $attemptId = DB::table('exam_attempts')->insertGetId(['registration_id' => $regId, 'exam_session_id' => $sessionId, 'status' => 'scored', 'started_at' => $now->copy()->subDays(4 - $idx), 'expires_at' => $now->copy()->subDays(4 - $idx)->addMinutes(45), 'submitted_at' => $now->copy()->subDays(4 - $idx)->addMinutes(35), 'auto_submitted' => false, 'tab_switch_count' => 0, 'created_at' => $now, 'updated_at' => $now]);
                $scaled = ['BIN' => 612, 'BIG' => 598, 'EKO' => 621][$code];
                DB::table('scores')->insert(['attempt_id' => $attemptId, 'raw_score' => round(($scaled - 200) / 6, 2), 'scaled_score' => $scaled, 'correct_count' => 32, 'wrong_count' => 8, 'unanswered_count' => 0, 'percentile' => 78.4, 'status' => 'finalized', 'components' => json_encode(['Penalaran' => ['correct' => 8, 'total' => 10], 'Pemahaman' => ['correct' => 7, 'total' => 10], 'Aplikasi' => ['correct' => 9, 'total' => 10]]), 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $names = [['Felicia Anggraini', 'SMAN 3 Jakarta', 712], ['Rafi Maulana', 'SMAN 8 Bandung', 705], ['Naufal Ramadhan', 'SMAN 1 Yogyakarta', 698], ['Keisha Putri', 'SMAN 3 Jakarta', 681], ['Dimas Pratama', 'SMAN 8 Bandung', 665]];
        foreach ($names as $i => [$name,$school,$score]) {
            $u = User::create(['name' => $name, 'email' => 'demo'.($i + 1).'@tkajuara.id', 'role' => 'participant', 'status' => 'active', 'password' => Hash::make('password')]);
            $pid = DB::table('participants')->insertGetId(['user_id' => $u->id, 'school_id' => DB::table('schools')->where('name', $school)->value('id'), 'nisn' => '00000000'.($i + 1), 'grade' => 'XII', 'province' => 'Jawa Barat', 'city' => 'Bekasi', 'consent' => true, 'created_at' => $now, 'updated_at' => $now]);
            $rid = DB::table('event_registrations')->insertGetId(['event_id' => $eventId, 'participant_id' => $pid, 'participant_number' => 'TKA-26-'.str_pad((string) (124 + $i), 6, '0', STR_PAD_LEFT), 'source' => 'school', 'status' => 'eligible', 'verified_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('rankings')->insert(['event_id' => $eventId, 'registration_id' => $rid, 'scope' => 'national', 'rank' => $i + 1, 'total_participants' => 25829, 'score' => $score, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('rankings')->insert(['event_id' => $eventId, 'registration_id' => $regId, 'scope' => 'national', 'rank' => 372, 'total_participants' => 25829, 'score' => 632, 'created_at' => $now, 'updated_at' => $now]);
        $certificateTemplateId = DB::table('certificate_templates')->insertGetId(['name' => 'Template Nasional TKA', 'title' => 'SERTIFIKAT', 'subtitle' => 'TRYOUT TKA 2026', 'primary_color' => '#082b61', 'accent_color' => '#f59e0b', 'body_text' => 'Telah mengikuti tryout dan menyelesaikan seluruh proses penilaian.', 'signatory_name' => 'Tim Akademik', 'signatory_title' => 'Penyelenggara', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('certificates')->insert(['registration_id' => $regId, 'certificate_template_id' => $certificateTemplateId, 'verification_code' => 'TKA-2026-AF-000123', 'issued_at' => $now->copy()->subDays(2), 'created_at' => $now, 'updated_at' => $now]);
    }
}
