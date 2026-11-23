<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_and_registration_pages_render_take_forms(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke Take');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('No. WhatsApp')
            ->assertSee('Alamat blok / RT / RW');
    }

    public function test_registration_creates_a_warga_even_if_admin_role_is_submitted(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Sari Warga',
            'email' => 'sari-warga@example.test',
            'phone' => '081234567890',
            'address' => 'Blok A No. 1, RT 02 / RW 05',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'sari-warga@example.test',
            'role' => 'warga',
        ]);
    }

    public function test_valid_credentials_authenticate_and_redirect_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'warga@example.test',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), [
            'email' => 'warga@example.test',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_an_indonesian_error(): void
    {
        User::factory()->create([
            'email' => 'warga@example.test',
            'password' => 'password123',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'warga@example.test',
                'password' => 'salah',
            ])
            ->assertRedirectBackWithErrors([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
