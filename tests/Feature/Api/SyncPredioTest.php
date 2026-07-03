<?php

namespace Tests\Feature\Api;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncPredioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Productor $productor;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        Sanctum::actingAs($this->admin);

        $this->productor = Productor::factory()->create();
    }

    public function test_upload_predios_crea_nuevos()
    {
        $response = $this->postJson('/api/sync/predios', [
            'predios' => [
                [
                    'id' => 'offline-predio-001',
                    'nombre_rancho' => 'Rancho Nuevo',
                    'productor_id' => $this->productor->id,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('predios', [
            'nombre_rancho' => 'Rancho Nuevo',
            'productor_id' => $this->productor->id,
        ]);
    }

    public function test_upload_predios_requiere_campos_obligatorios()
    {
        $response = $this->postJson('/api/sync/predios', [
            'predios' => [
                ['id' => 'offline-predio-001'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_predios_valores_default()
    {
        $response = $this->postJson('/api/sync/predios', [
            'predios' => [
                [
                    'id' => 'offline-predio-001',
                    'nombre_rancho' => 'Rancho Default',
                    'productor_id' => $this->productor->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('predios', [
            'nombre_rancho' => 'Rancho Default',
            'localidad' => 'General',
            'municipio' => 'General',
        ]);
    }

    public function test_upload_predios_productor_id_invalido_da_error()
    {
        $response = $this->postJson('/api/sync/predios', [
            'predios' => [
                [
                    'id' => 'offline-predio-001',
                    'nombre_rancho' => 'Rancho Invalido',
                    'productor_id' => 99999,
                ],
            ],
        ]);

        $response->assertStatus(500);
    }

    public function test_upload_predios_multiple()
    {
        $response = $this->postJson('/api/sync/predios', [
            'predios' => [
                [
                    'id' => 'offline-predio-001',
                    'nombre_rancho' => 'Rancho Uno',
                    'productor_id' => $this->productor->id,
                    'clave_unidad_produccion' => 'CUP-MULTI-001',
                ],
                [
                    'id' => 'offline-predio-002',
                    'nombre_rancho' => 'Rancho Dos',
                    'productor_id' => $this->productor->id,
                    'clave_unidad_produccion' => 'CUP-MULTI-002',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('predios', 2);
    }

    public function test_upload_predios_devuelve_mapping()
    {
        $response = $this->postJson('/api/sync/predios', [
            'predios' => [
                [
                    'id' => 'offline-xyz-789',
                    'nombre_rancho' => 'Rancho Mapping',
                    'productor_id' => $this->productor->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'predios' => [
                ['offline_id', 'new_id'],
            ],
        ]);
        $this->assertEquals('offline-xyz-789', $response->json('predios.0.offline_id'));
    }

    public function test_upload_predios_campos_adicionales()
    {
        $response = $this->postJson('/api/sync/predios', [
            'predios' => [
                [
                    'id' => 'offline-predio-001',
                    'nombre_rancho' => 'Rancho Completo',
                    'productor_id' => $this->productor->id,
                    'clave_unidad_produccion' => 'CUP-COMPLETO',
                    'localidad' => 'Mocorito',
                    'municipio' => 'Mocorito',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('predios', [
            'nombre_rancho' => 'Rancho Completo',
            'clave_unidad_produccion' => 'CUP-COMPLETO',
            'localidad' => 'Mocorito',
            'municipio' => 'Mocorito',
        ]);
    }
}
