<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_formulas', function (Blueprint $table) {
            $table->string('scoring_mode', 20)->default('classic')->after('partial_scoring');
        });

        Schema::create('ptn_programs', function (Blueprint $table) {
            $table->id();
            $table->string('university_name');
            $table->string('university_code', 20)->nullable();
            $table->string('program_name');
            $table->string('degree', 10)->default('S1');
            $table->string('category', 20)->default('SAINTEK');
            $table->decimal('passing_score', 6, 2);
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('applicants')->nullable();
            $table->timestamps();
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('target_program_1_id')->nullable()->after('city')->constrained('ptn_programs')->nullOnDelete();
            $table->foreignId('target_program_2_id')->nullable()->after('target_program_1_id')->constrained('ptn_programs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_program_2_id');
            $table->dropConstrainedForeignId('target_program_1_id');
        });

        Schema::dropIfExists('ptn_programs');

        Schema::table('scoring_formulas', function (Blueprint $table) {
            $table->dropColumn('scoring_mode');
        });
    }
};
