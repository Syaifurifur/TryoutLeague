<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminPackageController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $data = $request->validate(['subject_id' => ['required', Rule::exists('event_subjects', 'subject_id')->where('event_id', $event->id)], 'name' => ['required', 'max:255'], 'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'], 'question_ids' => ['nullable', 'array'], 'question_ids.*' => ['integer', 'exists:questions,id'], 'question_count' => ['nullable', 'integer', 'min:1'], 'domain' => ['nullable', 'max:120'], 'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])], 'randomize_questions' => ['required', 'boolean'], 'randomize_options' => ['required', 'boolean']]);
        $questionIds = collect($data['question_ids'] ?? [])->unique();
        if ($questionIds->isEmpty()) {
            $questionIds = DB::table('questions')->where('subject_id', $data['subject_id'])->where('status', 'approved')->when($data['domain'] ?? null, fn ($query, $domain) => $query->where('domain', $domain))->when($data['difficulty'] ?? null, fn ($query, $difficulty) => $query->where('difficulty', $difficulty))->inRandomOrder()->limit($data['question_count'] ?? 40)->pluck('id');
        }
        abort_if($questionIds->isEmpty(), 422, 'Tidak ada soal approved yang cocok.');
        abort_if(DB::table('questions')->whereIn('id', $questionIds)->where('subject_id', '!=', $data['subject_id'])->exists(), 422, 'Semua soal harus berasal dari mata uji yang dipilih.');

        DB::transaction(function () use ($event, $data, $questionIds) {
            $packageId = DB::table('exam_packages')->insertGetId(['event_id' => $event->id, 'subject_id' => $data['subject_id'], 'name' => $data['name'], 'version' => 1, 'duration_minutes' => $data['duration_minutes'], 'status' => 'draft', 'randomize_questions' => $data['randomize_questions'], 'randomize_options' => $data['randomize_options'], 'created_at' => now(), 'updated_at' => now()]);
            $sectionId = DB::table('exam_sections')->insertGetId(['exam_package_id' => $packageId, 'name' => 'Bagian 1', 'position' => 1, 'duration_minutes' => $data['duration_minutes'], 'created_at' => now(), 'updated_at' => now()]);
            foreach ($questionIds->values() as $index => $questionId) {
                DB::table('exam_package_questions')->insert(['exam_package_id' => $packageId, 'exam_section_id' => $sectionId, 'question_id' => $questionId, 'position' => $index + 1]);
            }
        });

        return back()->with('success', 'Paket draft berhasil dibuat dan siap dipreview.');
    }

    public function publish(Request $request, int $package)
    {
        $row = DB::table('exam_packages')->where('id', $package)->firstOrFail();
        abort_if(DB::table('exam_package_questions')->where('exam_package_id', $package)->doesntExist(), 422, 'Paket belum memiliki soal.');
        DB::table('exam_packages')->where('id', $package)->update(['status' => 'published', 'updated_at' => now()]);

        return back()->with('success', 'Paket berhasil dipublikasikan.');
    }

    public function preview(int $package)
    {
        $details = DB::table('exam_packages as p')->join('events as e', 'e.id', '=', 'p.event_id')->join('subjects as s', 's.id', '=', 'p.subject_id')->where('p.id', $package)->select('p.*', 'e.name as event', 's.name as subject')->firstOrFail();
        $sections = DB::table('exam_sections')->where('exam_package_id', $package)->orderBy('position')->get();
        foreach ($sections as $section) {
            $section->questions = DB::table('exam_package_questions as pq')->join('questions as q', 'q.id', '=', 'pq.question_id')->where('pq.exam_package_id', $package)->where('pq.exam_section_id', $section->id)->orderBy('pq.position')->get(['q.id', 'q.code', 'q.domain', 'q.subdomain', 'q.difficulty', 'q.type', 'q.content', 'q.version']);
        }

        return Inertia::render('Admin/PackagePreview', ['package' => $details, 'sections' => $sections]);
    }

    public function storeSection(Request $request, int $package)
    {
        $packageRow = DB::table('exam_packages')->where('id', $package)->firstOrFail();
        abort_if($packageRow->status !== 'draft' || DB::table('exam_sessions as es')->join('exam_attempts as a', 'a.exam_session_id', '=', 'es.id')->where('es.exam_package_id', $package)->exists(), 409, 'Paket yang telah dipublikasikan/digunakan wajib dibuatkan versi baru.');
        $data = $request->validate(['name' => ['required', 'max:255'], 'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'], 'question_count' => ['required', 'integer', 'min:1', 'max:200'], 'domain' => ['nullable', 'max:120'], 'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])]]);
        $used = DB::table('exam_package_questions')->where('exam_package_id', $package)->pluck('question_id');
        $questionIds = DB::table('questions')->where('subject_id', $packageRow->subject_id)->where('status', 'approved')->whereNotIn('id', $used)->when($data['domain'] ?? null, fn ($query, $domain) => $query->where('domain', $domain))->when($data['difficulty'] ?? null, fn ($query, $difficulty) => $query->where('difficulty', $difficulty))->inRandomOrder()->limit($data['question_count'])->pluck('id');
        abort_if($questionIds->count() < $data['question_count'], 422, 'Jumlah soal approved yang belum dipakai tidak mencukupi rule section.');
        DB::transaction(function () use ($package, $data, $questionIds) {
            $position = (int) DB::table('exam_sections')->where('exam_package_id', $package)->max('position') + 1;
            $sectionId = DB::table('exam_sections')->insertGetId(['exam_package_id' => $package, 'name' => $data['name'], 'position' => $position, 'duration_minutes' => $data['duration_minutes'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
            $questionPosition = (int) DB::table('exam_package_questions')->where('exam_package_id', $package)->max('position');
            foreach ($questionIds as $questionId) {
                DB::table('exam_package_questions')->insert(['exam_package_id' => $package, 'exam_section_id' => $sectionId, 'question_id' => $questionId, 'position' => ++$questionPosition]);
            }
        });

        return back()->with('success', 'Section baru beserta rule soal berhasil ditambahkan.');
    }

    public function version(Request $request, int $package)
    {
        $source = DB::table('exam_packages')->where('id', $package)->firstOrFail();
        $newId = DB::transaction(function () use ($source) {
            $newId = DB::table('exam_packages')->insertGetId(['event_id' => $source->event_id, 'subject_id' => $source->subject_id, 'name' => $source->name, 'version' => $source->version + 1, 'duration_minutes' => $source->duration_minutes, 'status' => 'draft', 'randomize_questions' => $source->randomize_questions, 'randomize_options' => $source->randomize_options, 'created_at' => now(), 'updated_at' => now()]);
            $sectionMap = [];
            foreach (DB::table('exam_sections')->where('exam_package_id', $source->id)->orderBy('position')->get() as $section) {
                $sectionMap[$section->id] = DB::table('exam_sections')->insertGetId(['exam_package_id' => $newId, 'name' => $section->name, 'position' => $section->position, 'duration_minutes' => $section->duration_minutes, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach (DB::table('exam_package_questions')->where('exam_package_id', $source->id)->get() as $question) {
                DB::table('exam_package_questions')->insert(['exam_package_id' => $newId, 'exam_section_id' => $question->exam_section_id ? $sectionMap[$question->exam_section_id] : null, 'question_id' => $question->question_id, 'position' => $question->position]);
            }

            return $newId;
        });

        return back()->with('success', "Versi paket baru #{$newId} dibuat sebagai draft.");
    }

    public function storeSession(Request $request, int $package)
    {
        $data = $request->validate(['name' => ['required', 'max:255'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'max_attempts' => ['required', 'integer', 'min:1', 'max:10'], 'status' => ['required', Rule::in(['scheduled', 'open', 'closed'])]]);
        abort_unless(DB::table('exam_packages')->where('id', $package)->where('status', 'published')->exists(), 422, 'Publikasikan paket sebelum membuat sesi.');
        DB::table('exam_sessions')->insert([...$data, 'exam_package_id' => $package, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Sesi ujian berhasil dijadwalkan.');
    }
}
