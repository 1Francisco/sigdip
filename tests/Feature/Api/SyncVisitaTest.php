<?php

namespace Tests\Feature\Api;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncVisitaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        Sanctum::actingAs($this->admin);

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    public function test_upload_visitas_crea_nuevas()
    {
        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [
                    'codigo' => 'VIS-001',
                    'predio_id' => $this->predio->id,
                    'fecha_programada' => '2026-07-15',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('procesados', ['VIS-001']);

        $this->assertDatabaseHas('visitas', [
            'codigo' => 'VIS-001',
            'predio_id' => $this->predio->id,
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);
    }

    public function test_upload_visitas_sin_codigo_da_error()
    {
        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                ['predio_id' => $this->predio->id, 'fecha_programada' => '2026-07-15'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('errores'));
        $this->assertEquals('Falta código de visita', $response->json('errores.0.error'));
    }

    public function test_upload_visitas_codigo_duplicado_ignora()
    {
        Visita::factory()->create(['codigo' => 'VIS-DUP', 'predio_id' => $this->predio->id]);

        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [
                    'codigo' => 'VIS-DUP',
                    'predio_id' => $this->predio->id,
                    'fecha_programada' => '2026-07-20',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('visitas', 1);
    }

    public function test_upload_visitas_asigna_veterinario_autenticado()
    {
        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [
                    'codigo' => 'VIS-AUTH',
                    'predio_id' => $this->predio->id,
                    'fecha_programada' => '2026-07-15',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('visitas', [
            'codigo' => 'VIS-AUTH',
            'veterinario_id' => $this->admin->id,
        ]);
    }

    public function test_upload_visitas_preserva_veterinario_explicito()
    {
        $otro = User::factory()->create();

        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [
                    'codigo' => 'VIS-VET',
                    'predio_id' => $this->predio->id,
                    'veterinario_id' => $otro->id,
                    'fecha_programada' => '2026-07-15',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('visitas', [
            'codigo' => 'VIS-VET',
            'veterinario_id' => $otro->id,
        ]);
    }

    public function test_upload_visitas_ignora_estado_externo()
    {
        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [
                    'codigo' => 'VIS-STATE',
                    'predio_id' => $this->predio->id,
                    'fecha_programada' => '2026-07-15',
                    'estado' => 'completada',
                    'inyeccion' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('visitas', [
            'codigo' => 'VIS-STATE',
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);
    }

    public function test_upload_visitas_error_parcial_no_afecta_otros()
    {
        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [
                    'codigo' => 'VIS-OK',
                    'predio_id' => $this->predio->id,
                    'fecha_programada' => '2026-07-15',
                ],
                [],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('procesados'));
        $this->assertCount(1, $response->json('errores'));
        $this->assertDatabaseHas('visitas', ['codigo' => 'VIS-OK']);
    }

    public function test_upload_visitas_solo_invalidos_no_procesa_nada()
    {
        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [],
                ['sin_codigo' => true],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEmpty($response->json('procesados'));
        $this->assertCount(2, $response->json('errores'));
    }
}
