<?php

namespace Tests\Feature;

use App\Models\QuestionImport;
use App\Models\User;
use App\Services\DocxQuestionParser;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class QuestionDocxImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_and_confirm_docx_question_import_for_selected_subject(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@tkajuara.id')->firstOrFail();
        $subject = DB::table('subjects')->where('code', 'MAT')->first();

        $response = $this->actingAs($admin)->post('/admin/questions/import/preview', [
            'subject_id' => $subject->id,
            'file' => $this->docxUpload([
                'SOAL: Hasil dari 2 + 2 adalah ...',
                'A. 2',
                'B. 3',
                'C. 4',
                'D. 5',
                'KUNCI: C',
                'MATERI: Aritmetika',
                'LEVEL: Mudah',
                'PEMBAHASAN: Dua ditambah dua sama dengan empat.',
                '---',
                'SOAL: Soal ini sengaja tidak memiliki kunci.',
                'A. Satu',
                'B. Dua',
                'C. Tiga',
                'D. Empat',
                'MATERI: Aritmetika',
                'LEVEL: Sedang',
            ]),
        ]);

        $import = QuestionImport::firstOrFail();
        $response->assertRedirect(route('admin.question-imports.show', $import));
        $this->assertSame(2, $import->total_questions);
        $this->assertSame(1, $import->valid_questions);
        $this->assertSame(1, $import->invalid_questions);

        $this->actingAs($admin)->post(route('admin.question-imports.store', $import))->assertRedirect(route('admin.questions'));

        $this->assertDatabaseHas('questions', [
            'subject_id' => $subject->id,
            'domain' => 'Aritmetika',
            'difficulty' => 'easy',
            'status' => 'review',
            'question_import_id' => $import->id,
        ]);
        $questionId = DB::table('questions')->where('question_import_id', $import->id)->value('id');
        $this->assertSame(4, DB::table('question_options')->where('question_id', $questionId)->count());
        $this->assertDatabaseHas('question_options', ['question_id' => $questionId, 'label' => 'C', 'is_correct' => true]);
        $this->assertSame('completed', $import->fresh()->status);
    }

    public function test_docx_import_rejects_documents_without_question_blocks(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@tkajuara.id')->firstOrFail();
        $subject = DB::table('subjects')->first();

        $this->actingAs($admin)->from('/admin/questions')->post('/admin/questions/import/preview', [
            'subject_id' => $subject->id,
            'file' => $this->docxUpload(['Dokumen tanpa blok soal.']),
        ])->assertRedirect('/admin/questions')->assertSessionHasErrors('file');

        $this->assertDatabaseCount('question_imports', 0);
    }

    public function test_downloadable_docx_template_is_structurally_valid_and_parseable(): void
    {
        $path = public_path('downloads/template-bank-soal-tka.docx');
        $this->assertFileExists($path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->locateName('word/document.xml'));
        $zip->close();

        $questions = app(DocxQuestionParser::class)->parse($path);
        $this->assertCount(1, $questions);
        $this->assertTrue($questions[0]['valid']);
        $this->assertSame('Aljabar', $questions[0]['domain']);
        $this->assertSame('C', $questions[0]['correct']);
    }

    private function docxUpload(array $paragraphs): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'question-docx-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $body = collect($paragraphs)->map(fn (string $line) => '<w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($line, ENT_XML1).'</w:t></w:r></w:p>')->implode('');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'<w:sectPr/></w:body></w:document>');
        $zip->close();

        return new UploadedFile($path, 'bank-soal.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }
}
