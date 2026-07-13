<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    public function test_store_asigna_rol_medico_campo()
    {
        $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Nuevo Medico',
            'email' => 'medico@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'medico@test.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Medico_Campo'));
    }

    public function test_store_no_asigna_administrador()
    {
        $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Otro',
            'email' => 'otro@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'otro@test.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasRole('Administrador'));
        $this->assertTrue($user->hasRole('Medico_Campo'));
    }

    public function test_medico_no_puede_acceder_a_gestion_usuarios()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $this->actingAs($medico)->get(route('usuarios.index'))->assertStatus(403);
        $this->actingAs($medico)->get(route('usuarios.create'))->assertStatus(403);
    }

    public function test_medico_no_puede_crear_usuarios()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $this->actingAs($medico)->post(route('usuarios.store'), [
            'name' => 'Intruso',
            'email' => 'intruso@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'intruso@test.com']);
    }
}
