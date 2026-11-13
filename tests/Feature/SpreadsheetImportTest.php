<?php

namespace Tests\Feature;

use App\Models\ParticipantImport;
use App\Models\QuestionImport;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpreadsheetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_question_xlsx_template_can_be_previewed_and_imported(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@tkajuara.id')->firstOrFail();
        $subjectId = DB::table('subjects')->where('code', 'MAT')->value('id');
        $file = new UploadedFile(public_path('downloads/template-import-soal.xlsx'), 'template-import-soal.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($admin)->post('/admin/questions/import/spreadsheet/preview', ['subject_id' => $subjectId, 'file' => $file])->assertRedirect();
        $import = QuestionImport::latest('id')->firstOrFail();
        $this->assertSame(2, $import->valid_questions);
        $this->actingAs($admin)->post("/admin/questions/import/{$import->id}")->assertRedirect('/admin/questions');
        $this->assertSame(2, DB::table('questions')->where('question_import_id', $import->id)->count());
    }

    public function test_official_participant_xlsx_template_has_row_level_preview(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@tkajuara.id')->firstOrFail();
        $eventId = DB::table('events')->value('id');
        $schoolId = DB::table('schools')->where('npsn', '20100101')->value('id');
        $file = new UploadedFile(public_path('downloads/template-import-peserta.xlsx'), 'template-import-peserta.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($admin)->post('/admin/participants/import/preview', ['event_id' => $eventId, 'school_id' => $schoolId, 'file' => $file])->assertRedirect();
        $import = ParticipantImport::latest('id')->firstOrFail();
        $this->assertSame(1, $import->total_rows);
        $this->assertSame(1, $import->valid_rows);
        $this->assertSame(0, $import->invalid_rows);
    }
}
