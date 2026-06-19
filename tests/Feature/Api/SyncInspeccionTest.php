<?php

namespace Tests\Feature\Api;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncInspeccionTest extends TestCase
{
    use RefreshDatabase;

    private User $medico;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');
        Sanctum::actingAs($this->medico);
    }

    public function test_upload_inspecciones_crea_nuevas()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-TEST-001',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', ['folio' => 'SYNC-TEST-001']);
    }

    public function test_upload_inspecciones_actualiza_existente()
    {
        $predio = Predio::factory()->create();
        Inspeccion::factory()->create([
            'folio' => 'SYNC-TEST-002',
            'predio_id' => $predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'borrador',
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-TEST-002',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', [
            'folio' => 'SYNC-TEST-002',
            'estado' => 'sincronizado',
        ]);
    }

    public function test_upload_inspecciones_con_animales()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-TEST-003',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'animales' => [
                        ['identificador' => 'MX-ARETE-001', 'resultado' => 'Negativo'],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $inspeccion = Inspeccion::where('folio', 'SYNC-TEST-003')->first();
        $this->assertNotNull($inspeccion);
        $this->assertCount(1, $inspeccion->detalles);
    }
}
