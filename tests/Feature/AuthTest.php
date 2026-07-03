<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);
    }

    public function test_muestra_formulario_login()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_login_con_credenciales_validas()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->assignRole('Administrador');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_con_credenciales_invalidas()
    {
        $response = $this->post('/login', [
            'email' => 'no@existe.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_logout()
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_redirige_a_login_sin_autenticar()
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_ve_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(200);
    }
}
