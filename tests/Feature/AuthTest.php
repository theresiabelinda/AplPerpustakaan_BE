<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Buat user Admin dan Anggota untuk testing
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);
        User::factory()->create([
            'email' => 'anggota@test.com',
            'password' => bcrypt('password'),
            'role' => 'anggota'
        ]);
    }
    public function user_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token']);
    }
    public function admin_can_access_admin_route()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/members'); // Menguji route Admin

        $response->assertStatus(200);
    }

    /** @test */
    public function anggota_cannot_access_admin_route()
    {
        $anggota = User::where('role', 'anggota')->first();

        $response = $this->actingAs($anggota, 'sanctum')
            ->getJson('/api/admin/members');

        $response->assertStatus(403); // Memastikan Forbidden (Otorisasi Gagal)
    }
}
