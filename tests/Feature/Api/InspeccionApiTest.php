<?php

namespace Tests\Feature\Api;

use App\Models\Animal;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InspeccionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);

        $this->user = User::factory()->create();
        $this->user->assignRole('Administrador');
        Sanctum::actingAs($this->user);

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    public function test_index()
    {
        Inspeccion::factory()->count(3)->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/inspecciones');

        $response->assertStatus(200);
    }

    public function test_show()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/inspecciones/{$inspeccion->id}");

        $response->assertStatus(200);
    }

    public function test_update()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/inspecciones/{$inspeccion->id}", [
            'observaciones' => 'Actualizado desde API',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', ['id' => $inspeccion->id, 'observaciones' => 'Actualizado desde API']);
    }

    public function test_pdf_returns_file()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/inspecciones/{$inspeccion->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_destroy()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/inspecciones/{$inspeccion->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inspecciones', ['id' => $inspeccion->id]);
    }

    public function test_sync_detalles()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $animal1 = Animal::factory()->create(['predio_id' => $this->predio->id]);
        $animal2 = Animal::factory()->create(['predio_id' => $this->predio->id]);

        $response = $this->postJson("/api/inspecciones/{$inspeccion->id}/sync-detalles", [
            'inspeccion_id' => $inspeccion->id,
            'detalles' => [
                ['animal_id' => $animal1->id, 'resultado_prueba' => 'Negativo'],
                ['animal_id' => $animal2->id, 'resultado_prueba' => 'Positivo'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $inspeccion->detalles()->get());
    }

    public function test_search_inspecciones_by_folio()
    {
        Inspeccion::factory()->create([
            'folio' => 'FOL-001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);
        Inspeccion::factory()->create([
            'folio' => 'FOL-002',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/inspecciones?search=FOL-001');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_inspecciones_by_estado()
    {
        Inspeccion::factory()->create([
            'estado' => 'borrador',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);
        Inspeccion::factory()->create([
            'estado' => 'sincronizado',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/inspecciones?estado=borrador');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('borrador', $response->json('data.0.estado'));
    }
}
