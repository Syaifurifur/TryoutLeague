<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('district', 120)->nullable()->after('city');
            $table->string('pic_name', 120)->nullable()->after('address');
            $table->string('pic_phone', 30)->nullable()->after('pic_name');
        });
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn(['district', 'pic_name', 'pic_phone']));
    }
};
