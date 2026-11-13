<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionImport;
use App\Services\DocxQuestionParser;
use App\Services\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;

class QuestionImportController extends Controller
{
    public function preview(Request $request, DocxQuestionParser $parser)
    {
        $data = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'file' => ['required', 'file', 'max:10240', 'extensions:docx', 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream'],
        ], [
            'file.extensions' => 'File harus menggunakan format .docx.',
            'file.mimetypes' => 'File yang diunggah bukan dokumen Word yang valid.',
            'file.max' => 'Ukuran file DOCX maksimal 10 MB.',
        ]);

        $path = $data['file']->store('question-imports');

        try {
            $questions = $parser->parse($data['file']->getRealPath());
        } catch (RuntimeException $exception) {
            Storage::delete($path);
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        if ($questions === []) {
            Storage::delete($path);
            throw ValidationException::withMessages(['file' => 'Tidak ditemukan blok soal. Pastikan setiap soal diawali dengan "SOAL:".']);
        }

        $valid = collect($questions)->where('valid', true)->count();
        $import = QuestionImport::create([
            'user_id' => $request->user()->id,
            'subject_id' => $data['subject_id'],
            'original_filename' => $data['file']->getClientOriginalName(),
            'storage_path' => $path,
            'status' => 'previewed',
            'total_questions' => count($questions),
            'valid_questions' => $valid,
            'invalid_questions' => count($questions) - $valid,
            'payload' => $questions,
        ]);

        return redirect()->route('admin.question-imports.show', $import);
    }

    public function previewSpreadsheet(Request $request, SpreadsheetReader $reader)
    {
        $data = $request->validate(['subject_id' => ['required', 'exists:subjects,id'], 'file' => ['required', 'file', 'max:10240', 'extensions:xlsx,csv']]);
        $path = $data['file']->store('question-imports');
        try {
            $rows = $reader->read($data['file']->getRealPath(), $data['file']->getClientOriginalExtension());
        } catch (RuntimeException $exception) {
            Storage::delete($path);
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $questions = collect($rows)->map(function ($row, $index) {
            $options = collect(['A', 'B', 'C', 'D', 'E'])->filter(fn ($label) => trim($row[mb_strtolower($label)] ?? '') !== '')->mapWithKeys(fn ($label) => [$label => trim($row[mb_strtolower($label)])])->all();
            $correct = collect(preg_split('/[,;\s]+/', strtoupper($row['kunci'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))->unique()->values()->all();
            $difficulty = match (mb_strtolower($row['level'] ?? '')) {
                'mudah', 'easy' => 'easy', 'sedang', 'medium' => 'medium', 'sulit', 'hard' => 'hard', default => ''
            };
            $type = count($correct) > 1 || in_array(mb_strtolower($row['tipe'] ?? ''), ['multiple', 'multiple_choice', 'pg kompleks'], true) ? 'multiple_choice' : 'single_choice';
            $errors = [];
            if (! ($row['soal'] ?? '')) {
                $errors[] = 'Kolom soal wajib diisi.';
            }
            if (array_diff(['A', 'B', 'C', 'D'], array_keys($options))) {
                $errors[] = 'Opsi A sampai D wajib diisi.';
            }
            if ($correct === [] || collect($correct)->diff(array_keys($options))->isNotEmpty()) {
                $errors[] = 'Kunci harus menunjuk opsi yang tersedia.';
            }
            if (! ($row['materi'] ?? '')) {
                $errors[] = 'Materi wajib diisi.';
            }
            if (! $difficulty) {
                $errors[] = 'Level harus Mudah, Sedang, atau Sulit.';
            }

            return ['number' => $index + 1, 'content' => $row['soal'] ?? '', 'options' => $options, 'correct' => $type === 'single_choice' ? ($correct[0] ?? '') : $correct, 'domain' => $row['materi'] ?? '', 'subdomain' => $row['submateri'] ?? null, 'difficulty' => $difficulty, 'type' => $type, 'tags' => collect(explode(',', $row['tags'] ?? ''))->map(fn ($tag) => trim($tag))->filter()->values()->all(), 'explanation' => $row['pembahasan'] ?? '', 'valid' => $errors === [], 'errors' => $errors];
        })->all();
        if ($questions === []) {
            Storage::delete($path);
            throw ValidationException::withMessages(['file' => 'Spreadsheet tidak memiliki baris soal.']);
        }
        $valid = collect($questions)->where('valid', true)->count();
        $import = QuestionImport::create(['user_id' => $request->user()->id, 'subject_id' => $data['subject_id'], 'original_filename' => $data['file']->getClientOriginalName(), 'storage_path' => $path, 'status' => 'previewed', 'total_questions' => count($questions), 'valid_questions' => $valid, 'invalid_questions' => count($questions) - $valid, 'payload' => $questions]);

        return redirect()->route('admin.question-imports.show', $import);
    }

    public function show(Request $request, QuestionImport $questionImport)
    {
        $this->authorizeImport($request, $questionImport);

        return Inertia::render('Admin/QuestionImportPreview', [
            'questionImport' => $questionImport->load('subject:id,name'),
        ]);
    }

    public function store(Request $request, QuestionImport $questionImport)
    {
        $this->authorizeImport($request, $questionImport);

        $validQuestions = collect($questionImport->payload)->where('valid', true);
        if ($validQuestions->isEmpty()) {
            return back()->with('error', 'Tidak ada soal valid yang dapat diimpor.');
        }

        $createdIds = DB::transaction(function () use ($request, $questionImport) {
            $lockedImport = QuestionImport::query()->lockForUpdate()->findOrFail($questionImport->id);
            if ($lockedImport->status !== 'previewed') {
                throw ValidationException::withMessages(['import' => 'Dokumen ini sudah pernah diproses.']);
            }

            $validQuestions = collect($lockedImport->payload)->where('valid', true);
            $ids = [];
            foreach ($validQuestions as $item) {
                $question = Question::create([
                    'code' => 'Q-'.strtoupper(Str::random(8)),
                    'subject_id' => $lockedImport->subject_id,
                    'domain' => $item['domain'],
                    'subdomain' => $item['subdomain'] ?? null,
                    'tags' => $item['tags'] ?? [],
                    'difficulty' => $item['difficulty'],
                    'type' => $item['type'] ?? 'single_choice',
                    'content' => $item['content'],
                    'explanation' => $item['explanation'] ?: null,
                    'status' => 'review',
                    'created_by' => $request->user()->id,
                    'question_import_id' => $lockedImport->id,
                ]);

                foreach ($item['options'] as $label => $content) {
                    $correctLabels = is_array($item['correct']) ? $item['correct'] : [$item['correct']];
                    $question->options()->create([
                        'label' => $label,
                        'content' => $content,
                        'is_correct' => in_array($label, $correctLabels, true),
                        'position' => ord($label) - 64,
                    ]);
                }
                DB::table('question_versions')->insert(['question_id' => $question->id, 'version' => 1, 'snapshot' => json_encode($question->load('options')->toArray()), 'created_by' => $request->user()->id, 'created_at' => now()]);
                $ids[] = $question->id;
            }

            $lockedImport->update(['status' => 'completed', 'imported_at' => now()]);
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => 'question.imported',
                'entity_type' => 'question_import',
                'entity_id' => $lockedImport->id,
                'after' => json_encode(['question_ids' => $ids, 'subject_id' => $lockedImport->subject_id]),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return $ids;
        });

        return redirect()->route('admin.questions')->with('success', count($createdIds).' soal berhasil diimpor dan menunggu review.');
    }

    private function authorizeImport(Request $request, QuestionImport $questionImport): void
    {
        abort_unless($questionImport->user_id === $request->user()->id || $request->user()->role === 'super_admin', 403);
    }
}
