<?php

namespace Tests\Feature\Api;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InspeccionApiStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    public function test_update_inspeccion()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->patchJson("/api/inspecciones/{$inspeccion->id}", [
            'observaciones' => 'Actualizado desde API',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', ['id' => $inspeccion->id, 'observaciones' => 'Actualizado desde API']);
    }

    public function test_update_validation_fails()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->patchJson("/api/inspecciones/{$inspeccion->id}", [
            'estado' => 'inexistente',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_requires_auth()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        $response = $this->patchJson("/api/inspecciones/{$inspeccion->id}", [
            'observaciones' => 'Sin auth',
        ]);

        $response->assertStatus(401);
    }

    public function test_medico_only_updates_own_inspeccion()
    {
        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $otroMedico->id,
        ]);

        Sanctum::actingAs($this->medico);

        $response = $this->patchJson("/api/inspecciones/{$inspeccion->id}", [
            'observaciones' => 'No deberia poder',
        ]);

        $response->assertStatus(403);
    }
}
