<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('status', 30)->default('previewed')->index();
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('valid_questions')->default(0);
            $table->unsignedInteger('invalid_questions')->default(0);
            $table->json('payload');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('question_import_id')->nullable()->after('created_by')->constrained('question_imports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_import_id');
        });

        Schema::dropIfExists('question_imports');
    }
};
