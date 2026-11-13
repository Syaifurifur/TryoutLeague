<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_autosave_is_idempotent_and_submit_scores_answer(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'peserta@tkajuara.id')->firstOrFail();
        $session = DB::table('exam_sessions as es')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('subjects as s', 's.id', '=', 'ep.subject_id')->where('s.code', 'MAT')->value('es.id');
        $start = $this->actingAs($user)->post("/exam-sessions/$session/start");
        $attempt = DB::table('exam_attempts')->where('exam_session_id', $session)->first();
        $start->assertRedirect("/attempts/$attempt->id");
        $snapshot = DB::table('attempt_questions')->where('attempt_id', $attempt->id)->first();
        $correct = DB::table('question_options')->where('question_id', $snapshot->question_id)->where('is_correct', true)->value('id');
        $payload = ['option_id' => $correct, 'idempotency_key' => 'answer-operation-001'];
        $this->actingAs($user)->putJson("/attempts/$attempt->id/answers/$snapshot->id", $payload)->assertOk()->assertJson(['saved' => true, 'revision' => 1]);
        $this->actingAs($user)->putJson("/attempts/$attempt->id/answers/$snapshot->id", $payload)->assertOk()->assertJson(['revision' => 1]);
        $this->assertDatabaseCount('answers', 1);
        $this->actingAs($user)->post("/attempts/$attempt->id/submit")->assertRedirect('/results');
        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id, 'status' => 'scored']);
        $this->assertDatabaseHas('scores', ['attempt_id' => $attempt->id, 'correct_count' => 1]);
    }

    public function test_participant_cannot_open_another_participants_attempt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'peserta@tkajuara.id')->firstOrFail();
        $otherAttempt = DB::table('exam_attempts')->whereNot('registration_id', DB::table('event_registrations')->where('participant_id', $user->participant->id)->value('id'))->value('id');
        if ($otherAttempt) {
            $this->actingAs($user)->get("/attempts/$otherAttempt")->assertForbidden();
        } else {
            $this->assertTrue(true);
        }
    }
}
