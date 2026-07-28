<?php

namespace Tests\Feature\Api;

use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncProductorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');
    }

    public function test_upload_productores_crea_nuevos()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Juan',
                    'apellido_paterno' => 'Pérez',
                    'apellido_materno' => 'López',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('productores', [
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'López',
        ]);
    }

    public function test_upload_productores_requiere_campos_obligatorios()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                ['id' => 'offline-001'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_productores_curp_existente_no_duplica()
    {
        Sanctum::actingAs($this->admin);
        Productor::factory()->create(['curp' => 'JUPE890101HSL00001']);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Juan',
                    'apellido_paterno' => 'Pérez',
                    'curp' => 'JUPE890101HSL00001',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('productores', 1);
    }

    public function test_upload_productores_genera_curp_offline()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Sin',
                    'apellido_paterno' => 'CURP',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $productor = Productor::first();
        $this->assertNotNull($productor);
        $this->assertStringStartsWith('OFFLINE-', $productor->curp);
    }

    public function test_upload_productores_admin_asigna_medico()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Admin',
                    'apellido_paterno' => 'Assign',
                    'medico_id' => $this->medico->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Admin',
            'medico_id' => $this->medico->id,
        ]);
    }

    public function test_upload_productores_medico_forzado_a_si_mismo()
    {
        Sanctum::actingAs($this->medico);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Medico',
                    'apellido_paterno' => 'Forzado',
                    'medico_id' => $this->admin->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Medico',
            'medico_id' => $this->medico->id,
        ]);
    }

    public function test_upload_productores_admin_asigna_clave()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Clave',
                    'apellido_paterno' => 'Test',
                    'clave' => 'AD-12345',
                    'zona' => 'A',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Clave',
            'clave' => 'AD-12345',
            'zona' => 'A',
        ]);
    }

    public function test_upload_productores_medico_no_asigna_clave()
    {
        Sanctum::actingAs($this->medico);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Medico',
                    'apellido_paterno' => 'Key',
                    'clave' => 'AD-12345',
                    'zona' => 'A',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Medico',
            'clave' => null,
            'zona' => null,
        ]);
    }

    public function test_upload_productores_clave_invalida_rechazada()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-001',
                    'nombre' => 'Invalida',
                    'apellido_paterno' => 'Key',
                    'clave' => 'XD-12345',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_productores_devuelve_mapping_offline_id()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/productores', [
            'productores' => [
                [
                    'id' => 'offline-abc-123',
                    'nombre' => 'Mapping',
                    'apellido_paterno' => 'Test',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'productores' => [
                ['offline_id', 'new_id'],
            ],
        ]);
        $this->assertEquals('offline-abc-123', $response->json('productores.0.offline_id'));
    }
}
