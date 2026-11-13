<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 20)->nullable()->unique();
            $table->string('name');
            $table->string('education_level', 20)->default('SMA');
            $table->string('ownership', 20)->nullable();
            $table->string('province', 120)->nullable()->index();
            $table->string('city', 120)->nullable()->index();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nisn', 20)->nullable()->index();
            $table->string('grade', 10)->default('XII');
            $table->string('major', 80)->nullable();
            $table->string('province', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 15)->nullable();
            $table->boolean('consent')->default(false);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->dateTime('registration_open_at')->nullable();
            $table->dateTime('registration_close_at')->nullable();
            $table->dateTime('event_start_at')->nullable();
            $table->dateTime('event_end_at')->nullable();
            $table->dateTime('result_published_at')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedInteger('quota')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('participant_number', 50);
            $table->string('source', 30)->default('individual');
            $table->string('status', 30)->default('verified')->index();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('checked_in_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'participant_id']);
            $table->unique(['event_id', 'participant_number']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('category', 30)->default('required');
            $table->string('color', 20)->default('#0B63CE');
            $table->timestamps();
        });

        Schema::create('event_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('max_choices')->nullable();
            $table->unique(['event_id', 'subject_id']);
        });

        Schema::create('participant_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->unique(['registration_id', 'subject_id']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 120)->nullable()->index();
            $table->string('difficulty', 20)->default('medium')->index();
            $table->string('type', 30)->default('single_choice');
            $table->longText('content');
            $table->text('explanation')->nullable();
            $table->decimal('weight', 6, 2)->default(1);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('label', 5);
            $table->text('content');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->unique(['question_id', 'label']);
        });

        Schema::create('exam_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('duration_minutes')->default(50);
            $table->string('status', 30)->default('draft');
            $table->boolean('randomize_questions')->default(true);
            $table->boolean('randomize_options')->default(true);
            $table->timestamps();
        });

        Schema::create('exam_package_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unique(['exam_package_id', 'question_id']);
        });

        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_package_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('max_attempts')->default(1);
            $table->string('status', 30)->default('scheduled')->index();
            $table->timestamps();
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('in_progress')->index();
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('submitted_at')->nullable();
            $table->boolean('auto_submitted')->default(false);
            $table->unsignedInteger('tab_switch_count')->default(0);
            $table->timestamps();
            $table->unique(['registration_id', 'exam_session_id']);
        });

        Schema::create('attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->json('option_order')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->unique(['attempt_id', 'question_id']);
            $table->unique(['attempt_id', 'position']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_question_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 80)->unique();
            $table->unsignedInteger('revision')->default(1);
            $table->dateTime('answered_at');
            $table->timestamps();
        });

        Schema::create('integrity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->unique()->constrained('exam_attempts')->cascadeOnDelete();
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('scaled_score', 8, 2)->default(0)->index();
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->unsignedInteger('unanswered_count')->default(0);
            $table->decimal('percentile', 5, 2)->nullable();
            $table->string('status', 30)->default('finalized');
            $table->json('components')->nullable();
            $table->timestamps();
        });

        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->string('scope', 30)->default('national');
            $table->unsignedInteger('rank');
            $table->unsignedInteger('total_participants');
            $table->decimal('score', 8, 2);
            $table->timestamps();
            $table->unique(['event_id', 'registration_id', 'scope']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->unique()->constrained('event_registrations')->cascadeOnDelete();
            $table->string('verification_code', 80)->unique();
            $table->dateTime('issued_at');
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'certificates', 'rankings', 'scores', 'integrity_events', 'answers', 'attempt_questions', 'exam_attempts', 'exam_sessions', 'exam_package_questions', 'exam_packages', 'question_options', 'questions', 'participant_subjects', 'event_subjects', 'subjects', 'event_registrations', 'events', 'participants', 'schools'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
