<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
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

    public function test_index_solo_para_admin()
    {
        $response = $this->actingAs($this->admin)->get(route('usuarios.index'));

        $response->assertStatus(200);
    }

    public function test_medico_no_puede_acceder()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->actingAs($medico)->get(route('usuarios.index'));

        $response->assertStatus(403);
    }

    public function test_create()
    {
        $response = $this->actingAs($this->admin)->get(route('usuarios.create'));

        $response->assertStatus(200);
    }

    public function test_store()
    {
        $response = $this->followingRedirects()->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'zona' => 'A',
            'actividad' => 'Barrido',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com', 'zona' => 'A', 'actividad' => 'Barrido']);
    }

    public function test_edit()
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('usuarios.edit', $usuario));

        $response->assertStatus(200);
    }

    public function test_show()
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('usuarios.show', $usuario));

        $response->assertStatus(200);
    }

    public function test_update()
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($this->admin)->patch(route('usuarios.update', $usuario), [
            'name' => 'Actualizado',
            'email' => 'actualizado@test.com',
            'zona' => 'B',
            'actividad' => 'Buffer',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'Actualizado', 'zona' => 'B', 'actividad' => 'Buffer']);
    }

    public function test_destroy()
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('usuarios.destroy', $usuario));

        $response->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }

    public function test_admin_no_puede_eliminarse_a_si_mismo()
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('usuarios.destroy', $this->admin));

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
