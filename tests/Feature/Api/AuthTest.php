<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
    }

    public function test_login_devuelve_token()
    {
        $user = User::factory()->create([
            'email' => 'api@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Administrador');

        $response = $this->postJson('/api/login', [
            'email' => 'api@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_credenciales_invalidas()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'no@existe.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout()
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
    }

    public function test_user_endpoint()
    {
        Role::firstOrCreate(['name' => 'Medico_Campo']);
        $user = User::factory()->create();
        $user->assignRole('Medico_Campo');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('id', $user->id);
    }

    public function test_rutas_protegidas_requieren_token()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
