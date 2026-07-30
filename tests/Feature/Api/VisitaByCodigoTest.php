<?php

namespace Tests\Feature\Api;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitaByCodigoTest extends TestCase
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

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    protected function authenticate(): void
    {
        Sanctum::actingAs($this->admin);
    }

    public function test_encuentra_visita_por_codigo()
    {
        $this->authenticate();
        $visita = Visita::factory()->create([
            'codigo' => 'V-ABCD-001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        $response = $this->getJson('/api/visitas/by-codigo/V-ABCD-001');

        $response->assertStatus(200)
            ->assertJsonPath('exists', true)
            ->assertJsonStructure([
                'exists',
                'data' => [
                    'id', 'codigo', 'predio_id', 'veterinario_id', 'fecha_programada',
                    'estado', 'inyeccion', 'observaciones',
                    'predio' => ['id', 'nombre', 'nombre_rancho'],
                    'veterinario' => ['id', 'name', 'email'],
                ],
            ]);
        $this->assertEquals($visita->id, $response->json('data.id'));
    }

    public function test_no_encuentra_codigo_inexistente()
    {
        $this->authenticate();
        $response = $this->getJson('/api/visitas/by-codigo/NO-EXISTE');

        $response->assertStatus(200)
            ->assertJsonPath('exists', false);
        $this->assertArrayNotHasKey('data', $response->json());
    }

    public function test_medico_encuentra_visita_por_codigo()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        Visita::factory()->create([
            'codigo' => 'V-MED-001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $medico->id,
        ]);

        $response = $this->getJson('/api/visitas/by-codigo/V-MED-001');

        $response->assertStatus(200)->assertJsonPath('exists', true);
    }

    public function test_unauthenticated_returns_401()
    {
        $response = $this->getJson('/api/visitas/by-codigo/V-TEST');

        $response->assertStatus(401);
    }

    public function test_by_codigo_incluye_inspeccion_si_existe()
    {
        $this->authenticate();
        $visita = Visita::factory()->create([
            'codigo' => 'V-INSP-001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'visita_id' => $visita->id,
        ]);

        $response = $this->getJson('/api/visitas/by-codigo/V-INSP-001');

        $response->assertStatus(200)
            ->assertJsonPath('exists', true)
            ->assertJsonStructure([
                'data' => ['inspeccion' => ['id', 'folio', 'estado', 'fecha']],
            ]);
        $this->assertEquals($inspeccion->id, $response->json('data.inspeccion.id'));
    }
}
