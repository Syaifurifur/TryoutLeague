<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MvpAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ac01_valid_registration_creates_unique_number_and_dashboard_access(): void
    {
        $this->seed(DatabaseSeeder::class);
        $event = DB::table('events')->first();
        $school = DB::table('schools')->first();
        $response = $this->post('/register', ['event_id' => $event->id, 'name' => 'Peserta Baru', 'email' => 'baru@example.id', 'phone' => '081299999999', 'password' => 'password123', 'password_confirmation' => 'password123', 'school_id' => $school->id, 'nisn' => '9988776655', 'grade' => 'XII', 'province' => 'Jawa Barat', 'city' => 'Bekasi', 'consent' => true]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('event_registrations', ['event_id' => $event->id, 'status' => 'eligible']);
        $this->assertNotEmpty(DB::table('event_registrations')->latest('id')->value('participant_number'));
    }

    public function test_ac03_start_locks_package_formula_and_question_snapshot(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $session] = $this->participantAndMathSession();
        $this->actingAs($user)->post("/exam-sessions/{$session}/start")->assertRedirect();
        $attempt = DB::table('exam_attempts')->where('exam_session_id', $session)->latest('id')->first();
        $this->assertNotNull($attempt->scoring_formula_id);
        $this->assertSame(1, $attempt->package_version);
        $snapshot = DB::table('attempt_questions')->where('attempt_id', $attempt->id)->first();
        $this->assertNotEmpty($snapshot->question_snapshot);
        $this->assertNotEmpty($snapshot->option_order);
    }

    public function test_ac06_expired_attempt_is_auto_submitted_and_scored(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $session] = $this->participantAndMathSession();
        $this->actingAs($user)->post("/exam-sessions/{$session}/start");
        $attemptId = DB::table('exam_attempts')->where('exam_session_id', $session)->latest('id')->value('id');
        DB::table('exam_attempts')->where('id', $attemptId)->update(['expires_at' => now()->subSecond()]);
        $this->artisan('attempts:expire')->assertSuccessful();
        $this->assertDatabaseHas('exam_attempts', ['id' => $attemptId, 'status' => 'scored', 'submit_type' => 'auto', 'auto_submitted' => true]);
        $this->assertDatabaseHas('scores', ['attempt_id' => $attemptId, 'status' => 'calculated']);
    }

    public function test_ac08_scoring_uses_formula_locked_when_attempt_started(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $session] = $this->participantAndMathSession();
        $this->actingAs($user)->post("/exam-sessions/{$session}/start");
        $attempt = DB::table('exam_attempts')->where('exam_session_id', $session)->latest('id')->first();
        $oldFormula = $attempt->scoring_formula_id;
        $eventId = DB::table('event_registrations')->where('id', $attempt->registration_id)->value('event_id');
        DB::table('scoring_formulas')->where('id', $oldFormula)->update(['status' => 'retired']);
        DB::table('scoring_formulas')->insert(['event_id' => $eventId, 'version' => 2, 'correct_points' => 5, 'wrong_points' => -1, 'unanswered_points' => 0, 'scale_min' => 0, 'scale_max' => 1000, 'partial_scoring' => false, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($user)->post("/attempts/{$attempt->id}/submit")->assertRedirect('/results');
        $this->assertDatabaseHas('scores', ['attempt_id' => $attempt->id, 'scoring_formula_id' => $oldFormula]);
    }

    public function test_ac09_results_are_hidden_until_admin_finalizes_and_publishes(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $session] = $this->participantAndMathSession();
        $eventId = DB::table('exam_packages as p')->join('exam_sessions as s', 's.exam_package_id', '=', 'p.id')->where('s.id', $session)->value('p.event_id');
        DB::table('events')->where('id', $eventId)->update(['result_published_at' => null]);
        $this->actingAs($user)->post("/exam-sessions/{$session}/start");
        $attemptId = DB::table('exam_attempts')->where('exam_session_id', $session)->latest('id')->value('id');
        $this->actingAs($user)->post("/attempts/{$attemptId}/submit");
        $this->actingAs($user)->get('/results')->assertInertia(fn (Assert $page) => $page->where('published', false)->where('scores', []));
        $admin = User::where('email', 'admin@tkajuara.id')->firstOrFail();
        $this->actingAs($admin)->post("/admin/events/{$eventId}/publish-results")->assertStatus(409);
        $this->actingAs($admin)->from('/admin/events')->post("/admin/events/{$eventId}/finalize")->assertRedirect('/admin/events');
        $this->actingAs($admin)->from('/admin/events')->post("/admin/events/{$eventId}/publish-results")->assertRedirect('/admin/events');
        $this->actingAs($user)->get('/results')->assertInertia(fn (Assert $page) => $page->where('published', true)->has('scores'));
    }

    public function test_ac10_school_operator_only_receives_own_school_participants(): void
    {
        $this->seed(DatabaseSeeder::class);
        $operator = User::where('email', 'sekolah@tkajuara.id')->firstOrFail();
        $response = $this->actingAs($operator)->get('/school');
        $response->assertOk()->assertSee('Ahmad Fauzan')->assertDontSee('Felicia Anggraini');
    }

    public function test_subject_selection_is_locked_after_deadline(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'peserta@tkajuara.id')->firstOrFail();
        DB::table('events')->update(['subject_selection_close_at' => now()->subMinute()]);
        $optional = DB::table('subjects')->where('category', 'optional')->value('id');
        $this->actingAs($user)->from('/subjects')->put('/subjects', ['subjects' => [$optional]])->assertRedirect('/subjects')->assertSessionHasErrors('subjects');
    }

    public function test_matrix_question_can_be_answered_and_scored_from_frozen_snapshot(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $session] = $this->participantAndMathSession();
        $packageId = DB::table('exam_sessions')->where('id', $session)->value('exam_package_id');
        $subjectId = DB::table('exam_packages')->where('id', $packageId)->value('subject_id');
        $questionId = DB::table('questions')->insertGetId([
            'code' => 'MAT-MATRIX-001',
            'subject_id' => $subjectId,
            'domain' => 'Aljabar',
            'difficulty' => 'medium',
            'type' => 'matrix',
            'content' => 'Tentukan benar atau salah untuk setiap pernyataan.',
            'matrix_rows' => json_encode([
                ['statement' => '2 + 2 = 4', 'correct' => 0],
                ['statement' => '5 adalah bilangan genap', 'correct' => 1],
            ]),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $trueId = DB::table('question_options')->insertGetId(['question_id' => $questionId, 'label' => 'A', 'content' => 'Benar', 'is_correct' => false, 'position' => 1]);
        $falseId = DB::table('question_options')->insertGetId(['question_id' => $questionId, 'label' => 'B', 'content' => 'Salah', 'is_correct' => false, 'position' => 2]);
        DB::table('exam_package_questions')->insert([
            'exam_package_id' => $packageId,
            'question_id' => $questionId,
            'position' => DB::table('exam_package_questions')->where('exam_package_id', $packageId)->max('position') + 1,
        ]);

        $this->actingAs($user)->post("/exam-sessions/{$session}/start")->assertRedirect();
        $attemptId = DB::table('exam_attempts')->where('exam_session_id', $session)->latest('id')->value('id');
        $attemptQuestionId = DB::table('attempt_questions')->where('attempt_id', $attemptId)->where('question_id', $questionId)->value('id');
        $this->actingAs($user)->putJson("/attempts/{$attemptId}/answers/{$attemptQuestionId}", [
            'matrix_answers' => [0 => $trueId, 1 => $falseId],
            'idempotency_key' => 'matrix-answer-1',
        ])->assertOk();
        $this->actingAs($user)->post("/attempts/{$attemptId}/submit")->assertRedirect('/results');

        $this->assertDatabaseHas('scores', ['attempt_id' => $attemptId, 'correct_count' => 1]);
        $this->assertDatabaseHas('answers', ['attempt_question_id' => $attemptQuestionId]);
        $this->actingAs($user)->get('/results')->assertInertia(fn (Assert $page) => $page->where('discussion', function ($items) use ($attemptQuestionId) {
            $review = collect($items)->firstWhere('id', $attemptQuestionId);

            return $review['result'] === 'correct'
                && $review['matrix_rows'][0]['selected']['content'] === 'Benar'
                && $review['matrix_rows'][1]['correct']['content'] === 'Salah';
        }));
    }

    public function test_editing_a_question_in_a_published_package_creates_a_new_draft(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@tkajuara.id')->firstOrFail();
        $question = DB::table('questions as q')
            ->join('exam_package_questions as pq', 'pq.question_id', '=', 'q.id')
            ->join('exam_packages as p', 'p.id', '=', 'pq.exam_package_id')
            ->where('p.status', 'published')
            ->select('q.*')
            ->first();
        $options = DB::table('question_options')->where('question_id', $question->id)->orderBy('position')->get();
        $beforeCount = DB::table('questions')->count();

        $this->actingAs($admin)->from('/admin/questions')->post("/admin/questions/{$question->id}", [
            'subject_id' => $question->subject_id,
            'domain' => $question->domain,
            'difficulty' => $question->difficulty,
            'type' => $question->type,
            'content' => 'Versi revisi yang tidak boleh mengubah paket terbit.',
            'options' => $options->pluck('content')->all(),
            'correct' => $options->values()->map(fn ($option, $index) => $option->is_correct ? $index : null)->filter(fn ($index) => $index !== null)->values()->all(),
        ])->assertRedirect('/admin/questions');

        $this->assertSame($beforeCount + 1, DB::table('questions')->count());
        $this->assertSame($question->content, DB::table('questions')->where('id', $question->id)->value('content'));
        $this->assertDatabaseHas('questions', ['content' => 'Versi revisi yang tidak boleh mengubah paket terbit.', 'status' => 'draft', 'version' => $question->version + 1]);
    }

    private function participantAndMathSession(): array
    {
        $user = User::where('email', 'peserta@tkajuara.id')->firstOrFail();
        $session = DB::table('exam_sessions as es')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('subjects as s', 's.id', '=', 'ep.subject_id')->where('s.code', 'MAT')->value('es.id');

        return [$user, $session];
    }
}
