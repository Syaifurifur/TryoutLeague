<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoOperatorSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = DB::table('schools')->where('name', 'SMAN 1 Bekasi')->value('id');
        if ($schoolId) {
            User::updateOrCreate(['email' => 'sekolah@tkajuara.id'], ['name' => 'Operator SMAN 1 Bekasi', 'phone' => '081200000003', 'role' => 'school_operator', 'school_id' => $schoolId, 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('password')]);
        }
    }
}
