<?php

namespace Tests\Feature\Api;

use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
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

    public function test_upload_con_animal_menor_6_meses_setea_motivo()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-MOTIVO-001',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'animales' => [
                        [
                            'identificador' => 'MX-SYNC-MENOR',
                            'edad_meses' => 5,
                            'sexo' => 'H',
                            'raza' => 'Cebú',
                            'resultado' => 'Pendiente',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('detalles_inspeccion', [
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 6 meses',
        ]);
    }

    public function test_upload_con_productor_bd_usa_umbral_2_meses()
    {
        $productorBD = Productor::factory()->create(['clave' => 'BD-123456', 'zona' => 'B']);
        $predioBD = Predio::factory()->create(['productor_id' => $productorBD->id]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-BD-001',
                    'predio_id' => $predioBD->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'animales' => [
                        [
                            'identificador' => 'MX-SYNC-BD-MENOR',
                            'edad_meses' => 1,
                            'sexo' => 'H',
                            'raza' => 'Cebú',
                            'resultado' => 'Pendiente',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('detalles_inspeccion', [
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 2 meses',
        ]);
    }

    public function test_upload_con_motivo_existente_se_conserva()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-MOTIVO-002',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'animales' => [
                        [
                            'identificador' => 'MX-SYNC-CONSERVA',
                            'edad_meses' => 10,
                            'sexo' => 'M',
                            'raza' => 'Suizo',
                            'resultado' => 'No Aplica',
                            'motivo_no_aplica' => 'Otra razón personalizada',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('detalles_inspeccion', [
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Otra razón personalizada',
        ]);
    }

    public function test_upload_con_edad_cero_no_asigna_motivo()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-EDAD-CERO',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'animales' => [
                        [
                            'identificador' => 'MX-SYNC-CERO',
                            'edad_meses' => 0,
                            'sexo' => 'H',
                            'resultado' => 'Negativo',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('detalles_inspeccion', [
            'resultado_prueba' => 'Negativo',
            'motivo_no_aplica' => null,
        ]);
    }

    public function test_upload_recalcula_al_cambiar_de_edad_6_a_3()
    {
        $predio = Predio::factory()->create();
        $inspeccion = Inspeccion::factory()->create([
            'folio' => 'SYNC-BOUNDARY-RECALC',
            'predio_id' => $predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'borrador',
        ]);
        $animal = Animal::factory()->create([
            'numero_arete_siniiga' => 'MX-BOUND-RECALC',
            'predio_id' => $predio->id,
        ]);
        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => $animal->id,
            'edad_meses' => 6,
            'resultado_prueba' => 'Negativo',
            'motivo_no_aplica' => null,
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-BOUNDARY-RECALC',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'animales' => [
                        [
                            'identificador' => 'MX-BOUND-RECALC',
                            'edad_meses' => 3,
                            'resultado' => 'Pendiente',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('detalles_inspeccion', [
            'animal_id' => $animal->id,
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 6 meses',
        ]);
    }

    public function test_upload_actualiza_motivo_cuando_cambia_edad()
    {
        $predio = Predio::factory()->create();
        $inspeccion = Inspeccion::factory()->create([
            'folio' => 'SYNC-RECALC',
            'predio_id' => $predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'borrador',
        ]);
        $animal = Animal::factory()->create([
            'numero_arete_siniiga' => 'MX-RECALC-001',
            'predio_id' => $predio->id,
        ]);
        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => $animal->id,
            'edad_meses' => 10,
            'resultado_prueba' => 'Negativo',
            'motivo_no_aplica' => null,
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-RECALC',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'animales' => [
                        [
                            'identificador' => 'MX-RECALC-001',
                            'edad_meses' => 4,
                            'resultado' => 'Pendiente',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('detalles_inspeccion', [
            'animal_id' => $animal->id,
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 6 meses',
        ]);
    }
}
