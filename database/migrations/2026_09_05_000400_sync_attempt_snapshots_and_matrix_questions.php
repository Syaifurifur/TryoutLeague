<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('exam_attempts', 'scoring_formula_id')) {
            Schema::table('exam_attempts', fn (Blueprint $table) => $table->foreignId('scoring_formula_id')->nullable()->after('exam_session_id')->constrained('scoring_formulas')->nullOnDelete());
        }
        if (! Schema::hasColumn('attempt_questions', 'question_snapshot')) {
            Schema::table('attempt_questions', fn (Blueprint $table) => $table->json('question_snapshot')->nullable()->after('question_id'));
        }
        if (! Schema::hasColumn('questions', 'matrix_rows')) {
            Schema::table('questions', fn (Blueprint $table) => $table->json('matrix_rows')->nullable()->after('content'));
        }
        if (! Schema::hasColumn('answers', 'matrix_answers')) {
            Schema::table('answers', fn (Blueprint $table) => $table->json('matrix_answers')->nullable()->after('selected_option_ids'));
        }
        foreach (DB::table('events')->pluck('id') as $eventId) {
            if (! DB::table('scoring_formulas')->where('event_id', $eventId)->exists()) {
                DB::table('scoring_formulas')->insert(['event_id' => $eventId, 'version' => 1, 'correct_points' => 1, 'wrong_points' => 0, 'unanswered_points' => 0, 'scale_min' => 200, 'scale_max' => 800, 'partial_scoring' => false, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        if (! DB::table('certificate_templates')->exists()) {
            DB::table('certificate_templates')->insert(['name' => 'Template Utama TKA', 'title' => 'SERTIFIKAT', 'subtitle' => 'TRYOUT TKA', 'primary_color' => '#082b61', 'accent_color' => '#f59e0b', 'body_text' => 'Telah mengikuti tryout dan menyelesaikan seluruh proses penilaian.', 'signatory_name' => 'Tim Akademik', 'signatory_title' => 'Penyelenggara', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('answers', 'matrix_answers')) {
            Schema::table('answers', fn (Blueprint $table) => $table->dropColumn('matrix_answers'));
        }
        if (Schema::hasColumn('questions', 'matrix_rows')) {
            Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('matrix_rows'));
        }
    }
};
