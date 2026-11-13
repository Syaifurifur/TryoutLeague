<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnswerReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_reload_answer_keys_and_explanation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@tkajuara.id')->firstOrFail();
        $data = ['subject_id' => DB::table('subjects')->value('id'), 'domain' => 'Logika', 'difficulty' => 'easy', 'type' => 'multiple_choice', 'content' => 'Pilih bilangan genap.', 'options' => ['1', '2', '3', '4', '5'], 'correct' => [1, 3], 'explanation' => '2 dan 4 habis dibagi dua.'];
        $this->actingAs($admin)->post('/admin/questions', $data)->assertSessionHasNoErrors()->assertRedirect();
        $questionId = DB::table('questions')->where('content', $data['content'])->value('id');
        $this->assertSame(['B', 'D'], DB::table('question_options')->where('question_id', $questionId)->where('is_correct', true)->orderBy('position')->pluck('label')->all());
        $this->actingAs($admin)->get('/admin/questions?q=Pilih%20bilangan%20genap')->assertInertia(fn (Assert $page) => $page
            ->where('questions.data.0.explanation', $data['explanation'])
            ->where('questions.data.0.options.1.is_correct', 1)
            ->where('questions.data.0.options.3.is_correct', 1));
        foreach ([[9], [-1], ['invalid'], [1, 1]] as $invalid) {
            $this->actingAs($admin)->post('/admin/questions', [...$data, 'correct' => $invalid])->assertSessionHasErrors('correct');
        }
        $this->actingAs($admin)->post('/admin/questions', [...$data, 'type' => 'single_choice', 'correct' => [1, 3]])->assertSessionHasErrors('correct');
    }

    public function test_review_is_private_until_submit_and_uses_frozen_content_and_shuffled_labels(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'peserta@tkajuara.id')->firstOrFail();
        $session = DB::table('exam_sessions as es')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('subjects as s', 's.id', '=', 'ep.subject_id')->where('s.code', 'MAT')->value('es.id');
        DB::table('events')->update(['result_published_at' => null]);
        $questionId = DB::table('exam_package_questions')->where('exam_package_id', DB::table('exam_sessions')->where('id', $session)->value('exam_package_id'))->value('question_id');
        DB::table('questions')->where('id', $questionId)->update(['explanation' => 'Pembahasan asli saat ujian dimulai.']);
        $this->actingAs($user)->post("/exam-sessions/{$session}/start")->assertRedirect();
        $attemptId = DB::table('exam_attempts')->where('exam_session_id', $session)->where('registration_id', DB::table('event_registrations')->where('participant_id', $user->participant->id)->value('id'))->value('id');
        $aq = DB::table('attempt_questions')->where('attempt_id', $attemptId)->where('question_id', $questionId)->first();
        $snapshot = json_decode($aq->question_snapshot, true);
        $correctId = collect($snapshot['options'])->firstWhere('is_correct', true)['id'];
        $order = collect($snapshot['options'])->pluck('id')->reject(fn ($id) => $id === $correctId)->prepend($correctId)->values()->all();
        DB::table('attempt_questions')->where('id', $aq->id)->update(['option_order' => json_encode($order)]);
        $this->actingAs($user)->get('/results')->assertInertia(fn (Assert $page) => $page->where('discussion', []));
        $this->actingAs($user)->get("/attempts/{$attemptId}")->assertInertia(fn (Assert $page) => $page->missing('questions.0.explanation')->missing('questions.0.options.0.is_correct'));
        $this->actingAs($user)->putJson("/attempts/{$attemptId}/answers/{$aq->id}", ['option_id' => $correctId, 'idempotency_key' => 'review-answer'])->assertOk();
        // Even changes outside the normal immutable editor cannot change this attempt's review.
        DB::table('questions')->where('id', $questionId)->update(['content' => 'MASTER CHANGED', 'explanation' => 'MASTER EXPLANATION CHANGED']);
        DB::table('question_options')->where('question_id', $questionId)->update(['is_correct' => false]);
        $this->actingAs($user)->post("/attempts/{$attemptId}/submit")->assertRedirect('/results');
        $this->actingAs($user)->get('/results')->assertInertia(function (Assert $page) use ($aq, $snapshot, $attemptId) {
            $page->where('published', false)->where('scores', [])->where('discussion', function ($items) use ($aq, $snapshot, $attemptId) {
                $item = collect($items)->firstWhere('id', $aq->id);

                return $item['content'] === $snapshot['content']
                    && $item['explanation'] === 'Pembahasan asli saat ujian dimulai.'
                    && collect($item['selected_labels'])->all() === ['A']
                    && collect($item['correct_labels'])->all() === ['A']
                    && $item['result'] === 'correct'
                    && collect($items)->every(fn ($row) => $row['attempt_id'] === $attemptId);
            });
        });
        $other = User::where('role', 'participant')->where('id', '!=', $user->id)->firstOrFail();
        $this->actingAs($other)->get('/results')->assertInertia(fn (Assert $page) => $page->where('discussion', fn ($items) => ! collect($items)->contains('attempt_id', $attemptId)));
    }
}
