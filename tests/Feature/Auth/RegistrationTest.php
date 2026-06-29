<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'nip' => '199501012020011002',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'phone_number' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
            'profile_photo' => \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Pendaftaran berhasil! Akun Anda sedang menunggu verifikasi oleh Administrator.');
    }
}
