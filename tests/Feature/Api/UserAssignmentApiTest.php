<?php

namespace Tests\Feature\Api;

use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Productor $productorAsignado;

    private Productor $productorDisponible;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

        $this->productorAsignado = Productor::factory()->create(['medico_id' => $this->medico->id]);
        $this->productorDisponible = Productor::factory()->create(['medico_id' => null]);

        Sanctum::actingAs($this->admin);
    }

    public function test_productores_asignables_estructura()
    {
        $response = $this->getJson("/api/usuarios/{$this->medico->id}/productores-asignables");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'medico' => ['id', 'name', 'email'],
                'asignados',
                'disponibles',
            ]);
    }

    public function test_productores_asignables_solo_admin()
    {
        Sanctum::actingAs($this->medico);

        $response = $this->getJson("/api/usuarios/{$this->medico->id}/productores-asignables");

        $response->assertStatus(403);
    }

    public function test_guardar_asignacion_asigna_sin_desasignar_existentes()
    {
        $otroProductor = Productor::factory()->create();

        $response = $this->postJson("/api/usuarios/{$this->medico->id}/asignar-productores", [
            'productor_ids' => [$this->productorDisponible->id, $otroProductor->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('productores', [
            'id' => $this->productorDisponible->id,
            'medico_id' => $this->medico->id,
        ]);
        $this->assertDatabaseHas('productores', [
            'id' => $otroProductor->id,
            'medico_id' => $this->medico->id,
        ]);
        // Existing assignment should NOT be removed
        $this->assertDatabaseHas('productores', [
            'id' => $this->productorAsignado->id,
            'medico_id' => $this->medico->id,
        ]);
    }

    public function test_guardar_asignacion_vacia_no_desasigna()
    {
        $response = $this->postJson("/api/usuarios/{$this->medico->id}/asignar-productores", [
            'productor_ids' => [],
        ]);

        $response->assertStatus(200);
        // Empty array should NOT unassign existing productors
        $this->assertDatabaseHas('productores', [
            'id' => $this->productorAsignado->id,
            'medico_id' => $this->medico->id,
        ]);
    }

    public function test_guardar_asignacion_solo_admin()
    {
        Sanctum::actingAs($this->medico);

        $response = $this->postJson("/api/usuarios/{$this->medico->id}/asignar-productores", [
            'productor_ids' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_desasignar_productor_correcto()
    {
        $response = $this->postJson(
            "/api/usuarios/{$this->medico->id}/desasignar-productor/{$this->productorAsignado->id}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('productores', [
            'id' => $this->productorAsignado->id,
            'medico_id' => null,
        ]);
    }

    public function test_desasignar_productor_no_asignado_404()
    {
        $response = $this->postJson(
            "/api/usuarios/{$this->medico->id}/desasignar-productor/{$this->productorDisponible->id}"
        );

        $response->assertStatus(404);
    }

    public function test_desasignar_productor_solo_admin()
    {
        Sanctum::actingAs($this->medico);

        $response = $this->postJson(
            "/api/usuarios/{$this->medico->id}/desasignar-productor/{$this->productorAsignado->id}"
        );

        $response->assertStatus(403);
    }

    public function test_productores_asignables_incluye_clave()
    {
        Productor::factory()->create(['clave' => 'BP-1234']);

        $response = $this->getJson("/api/usuarios/{$this->medico->id}/productores-asignables");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'medico',
                'asignados' => [['id', 'nombre', 'clave', 'predios_count']],
                'disponibles' => [['id', 'nombre', 'clave', 'predios_count']],
            ]);
    }

    public function test_guardar_asignacion_asigna_hato_completo()
    {
        $miembro1 = Productor::factory()->create(['clave' => 'BAF-5555']);
        $miembro2 = Productor::factory()->create(['clave' => 'BAF-5555']);

        $response = $this->postJson("/api/usuarios/{$this->medico->id}/asignar-productores", [
            'productor_ids' => [$miembro1->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('productores', [
            'id' => $miembro1->id,
            'medico_id' => $this->medico->id,
        ]);
        $this->assertDatabaseHas('productores', [
            'id' => $miembro2->id,
            'medico_id' => $this->medico->id,
        ]);
    }

    public function test_guardar_asignacion_mueve_hato_division_entre_medicos()
    {
        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');

        $miembroA = Productor::factory()->create(['clave' => 'BFC-7777', 'medico_id' => $this->medico->id]);
        $miembroB = Productor::factory()->create(['clave' => 'BFC-7777', 'medico_id' => $otroMedico->id]);

        $response = $this->postJson("/api/usuarios/{$this->medico->id}/asignar-productores", [
            'productor_ids' => [$miembroA->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('productores', [
            'id' => $miembroB->id,
            'medico_id' => $this->medico->id,
        ]);
    }
}
