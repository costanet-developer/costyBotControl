<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_se_carga(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('CostyBO');
        $response->assertSee('Iniciar Sesión');
    }

    public function test_dashboard_requiere_autenticacion(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_exitoso_redirige_al_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_fallo_muestra_error(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'noexiste@test.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
