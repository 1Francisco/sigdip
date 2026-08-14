<?php

namespace Tests\Feature\Api;

use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InspeccionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $medico;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->user = User::factory()->create();
        $this->user->assignRole('Administrador');
        Sanctum::actingAs($this->user);

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

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

    public function test_pdf_sirve_dictamen_comite_cuando_existe()
    {
        Storage::fake('public');

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $contenido = Pdf::loadHTML('<h1>DICTAMEN OFICIAL DEL COMITE</h1>')->output();
        $path = 'dictamenes_comite/api_'.$inspeccion->id.'.pdf';
        Storage::disk('public')->put($path, $contenido);
        $inspeccion->update(['dictamen_comite_path' => $path]);

        $response = $this->getJson("/api/inspecciones/{$inspeccion->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="'.$inspeccion->buildPdfFilename().'"');
        $this->assertSame($contenido, $response->baseResponse->getFile()->getContent());
    }

    public function test_pdf_con_prototype_fuerza_pdf_generado()
    {
        Storage::fake('public');

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $path = 'dictamenes_comite/api_prototype_'.$inspeccion->id.'.pdf';
        Storage::disk('public')->put($path, Pdf::loadHTML('<h1>DICTAMEN OFICIAL DEL COMITE</h1>')->output());
        $inspeccion->update(['dictamen_comite_path' => $path]);

        $response = $this->getJson("/api/inspecciones/{$inspeccion->id}/pdf?prototype=1");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
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

    public function test_medico_elimina_borrador_propio()
    {
        Sanctum::actingAs($this->medico);

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'borrador',
        ]);

        $response = $this->deleteJson("/api/inspecciones/{$inspeccion->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inspecciones', ['id' => $inspeccion->id]);
    }

    public function test_medico_no_elimina_dictamen_finalizado_propio()
    {
        Sanctum::actingAs($this->medico);

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'finalizado',
        ]);

        $response = $this->deleteJson("/api/inspecciones/{$inspeccion->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('inspecciones', ['id' => $inspeccion->id]);
    }

    public function test_admin_elimina_dictamen_finalizado()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'finalizado',
        ]);

        $response = $this->deleteJson("/api/inspecciones/{$inspeccion->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inspecciones', ['id' => $inspeccion->id]);
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

    public function test_show_incluye_motivo_no_aplica()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $animal = Animal::factory()->create(['predio_id' => $this->predio->id]);
        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => $animal->id,
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 4 meses',
        ]);

        $response = $this->getJson("/api/inspecciones/{$inspeccion->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 4 meses',
        ]);
    }
}
