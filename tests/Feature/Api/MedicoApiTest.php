<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MedicoApiTest extends TestCase
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
        Sanctum::actingAs($this->admin);
    }

    public function test_index()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->getJson('/api/medicos');

        $response->assertStatus(200);
    }

    public function test_store()
    {
        $response = $this->postJson('/api/medicos', [
            'name' => 'Nuevo Médico',
            'email' => 'medico@test.com',
            'password' => 'password',
            'zona' => 'A',
            'actividad' => 'Barrido',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'medico@test.com', 'zona' => 'A', 'actividad' => 'Barrido']);
    }

    public function test_show()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->getJson("/api/medicos/{$medico->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $medico->id)
            ->assertJsonPath('data.name', $medico->name)
            ->assertJsonPath('data.email', $medico->email)
            ->assertJsonStructure([
                'data' => ['zona', 'actividad'],
            ]);
    }

    public function test_update()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->putJson("/api/medicos/{$medico->id}", [
            'name' => 'Médico Actualizado',
            'email' => 'actualizado@test.com',
            'zona' => 'B',
            'actividad' => 'Buffer',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $medico->id,
            'name' => 'Médico Actualizado',
            'email' => 'actualizado@test.com',
            'zona' => 'B',
            'actividad' => 'Buffer',
        ]);
    }

    public function test_update_con_nueva_password()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->putJson("/api/medicos/{$medico->id}", [
            'name' => $medico->name,
            'email' => $medico->email,
            'password' => 'nuevapassword',
            'zona' => 'A',
            'actividad' => 'Cuarentenas Definitivas',
        ]);

        $response->assertStatus(200);
    }

    public function test_show_404()
    {
        $response = $this->getJson('/api/medicos/9999');
        $response->assertStatus(404);
    }

    public function test_destroy()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->deleteJson("/api/medicos/{$medico->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $medico->id]);
    }

    public function test_medico_no_puede_listar_medicos()
    {
        Role::firstOrCreate(['name' => 'Medico_Campo']);
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->getJson('/api/medicos');

        $response->assertStatus(403);
    }
}
