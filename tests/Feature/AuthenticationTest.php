<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_login_and_is_redirected_to_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);
        $response = $this->post('/login', ['email' => 'peserta@tkajuara.id', 'password' => 'password']);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_admin_can_login_and_is_redirected_to_console(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->post('/login', ['email' => 'admin@tkajuara.id', 'password' => 'password'])->assertRedirect('/admin');
    }

    public function test_duplicate_registration_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);
        $school = DB::table('schools')->first();
        $this->from('/register')->post('/register', ['name' => 'Duplikat', 'email' => 'peserta@tkajuara.id', 'phone' => '08990000', 'password' => 'password', 'password_confirmation' => 'password', 'school_id' => $school->id, 'nisn' => '123', 'grade' => 'XII', 'province' => 'Jawa Barat', 'city' => 'Bekasi', 'consent' => true])->assertRedirect('/register')->assertSessionHasErrors('email');
    }
}
