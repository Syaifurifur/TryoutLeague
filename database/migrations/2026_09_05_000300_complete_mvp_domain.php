<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('description');
            $table->boolean('allow_individual_registration')->default(true)->after('banner_path');
            $table->boolean('allow_school_registration')->default(true)->after('allow_individual_registration');
            $table->dateTime('subject_selection_open_at')->nullable()->after('registration_close_at');
            $table->dateTime('subject_selection_close_at')->nullable()->after('subject_selection_open_at');
            $table->longText('terms')->nullable()->after('settings');
        });

        Schema::create('subject_selection_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('before_subject_ids')->nullable();
            $table->json('after_subject_ids');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('subdomain', 120)->nullable()->after('domain');
            $table->json('tags')->nullable()->after('subdomain');
            $table->string('image_path')->nullable()->after('content');
            $table->foreignId('reviewed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
        });

        Schema::create('question_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['question_id', 'version']);
        });

        Schema::create('question_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_package_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();
            $table->unique(['exam_package_id', 'position']);
        });

        Schema::table('exam_package_questions', function (Blueprint $table) {
            $table->foreignId('exam_section_id')->nullable()->after('exam_package_id')->constrained('exam_sections')->nullOnDelete();
        });

        Schema::create('scoring_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->decimal('correct_points', 8, 2)->default(1);
            $table->decimal('wrong_points', 8, 2)->default(0);
            $table->decimal('unanswered_points', 8, 2)->default(0);
            $table->decimal('scale_min', 8, 2)->default(200);
            $table->decimal('scale_max', 8, 2)->default(800);
            $table->boolean('partial_scoring')->default(false);
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['event_id', 'version']);
        });

        Schema::table('scores', function (Blueprint $table) {
            $table->foreignId('scoring_formula_id')->nullable()->after('attempt_id')->constrained('scoring_formulas')->nullOnDelete();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedInteger('package_version')->default(1)->after('exam_session_id');
            $table->foreignId('scoring_formula_id')->nullable()->after('package_version')->constrained('scoring_formulas')->nullOnDelete();
            $table->string('submit_type', 20)->nullable()->after('submitted_at');
            $table->string('device_fingerprint')->nullable()->after('tab_switch_count');
            $table->json('ip_history')->nullable()->after('device_fingerprint');
            $table->dateTime('last_activity_at')->nullable()->after('ip_history');
            $table->dateTime('last_autosave_at')->nullable()->after('last_activity_at');
            $table->string('score_status', 20)->default('pending')->after('last_autosave_at');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->json('selected_option_ids')->nullable()->after('question_option_id');
        });

        Schema::table('attempt_questions', function (Blueprint $table) {
            $table->json('question_snapshot')->nullable()->after('question_id');
        });

        Schema::create('answer_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_question_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 80)->unique();
            $table->unsignedInteger('revision');
            $table->json('selected_option_ids');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->default('SERTIFIKAT');
            $table->string('subtitle')->nullable();
            $table->string('primary_color', 20)->default('#082b61');
            $table->string('accent_color', 20)->default('#f59e0b');
            $table->text('body_text')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('certificate_template_id')->nullable()->after('registration_id')->constrained('certificate_templates')->nullOnDelete();
        });

        Schema::create('participant_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('status', 30)->default('previewed');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->json('payload');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_imports');
        Schema::table('certificates', fn (Blueprint $table) => $table->dropConstrainedForeignId('certificate_template_id'));
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('answer_mutations');
        Schema::table('attempt_questions', fn (Blueprint $table) => $table->dropColumn('question_snapshot'));
        Schema::table('answers', fn (Blueprint $table) => $table->dropColumn('selected_option_ids'));
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scoring_formula_id');
            $table->dropColumn(['package_version', 'submit_type', 'device_fingerprint', 'ip_history', 'last_activity_at', 'last_autosave_at', 'score_status']);
        });
        Schema::table('scores', fn (Blueprint $table) => $table->dropConstrainedForeignId('scoring_formula_id'));
        Schema::dropIfExists('scoring_formulas');
        Schema::table('exam_package_questions', fn (Blueprint $table) => $table->dropConstrainedForeignId('exam_section_id'));
        Schema::dropIfExists('exam_sections');
        Schema::dropIfExists('question_comments');
        Schema::dropIfExists('question_versions');
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['subdomain', 'tags', 'image_path', 'reviewed_at']);
        });
        Schema::dropIfExists('subject_selection_histories');
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['banner_path', 'allow_individual_registration', 'allow_school_registration', 'subject_selection_open_at', 'subject_selection_close_at', 'terms']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('school_id'));
    }
};
