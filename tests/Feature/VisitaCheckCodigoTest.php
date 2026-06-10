<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Visita;
use App\Models\Productor;
use App\Models\Predio;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;

class VisitaCheckCodigoTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $medico;
    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->medico = User::factory()->create(['name' => 'Dr. Prueba']);
        $this->medico->assignRole('Medico_Campo');

        $this->admin = User::factory()->create(['name' => 'Admin Test']);
        $this->admin->assignRole('Administrador');

        $productor = Productor::create([
            'nombre' => 'Productor Test',
            'apellido_paterno' => 'Apellido',
            'upp' => 'TEST-001',
            'curp' => 'TEST000101HPLXXX',
        ]);

        $this->predio = Predio::create([
            'productor_id' => $productor->id,
            'nombre_rancho' => 'Rancho Test',
            'clave_unidad_produccion' => 'CUP-001',
            'municipio' => 'Test',
            'localidad' => 'Test',
        ]);
    }

    public function test_codigo_existente_devuelve_true_con_datos()
    {
        Visita::create([
            'codigo' => 'V-TEST-20260609-XYZ1',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha_programada' => '2026-06-09',
            'estado' => 'pendiente',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/visitas/check-codigo/V-TEST-20260609-XYZ1');

        $response->assertStatus(200)
            ->assertJson([
                'exists' => true,
            ])
            ->assertJsonStructure([
                'exists',
                'visita' => ['codigo', 'fecha_programada', 'predio', 'veterinario'],
            ]);

        $this->assertEquals('V-TEST-20260609-XYZ1', $response['visita']['codigo']);
        $this->assertEquals('Rancho Test', $response['visita']['predio']['nombre_rancho']);
        $this->assertEquals('Dr. Prueba', $response['visita']['veterinario']['name']);
    }

    public function test_codigo_inexistente_devuelve_false()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/visitas/check-codigo/CODIGO-QUE-NO-EXISTE');

        $response->assertStatus(200)
            ->assertJson(['exists' => false]);
    }

    public function test_crear_visita_con_codigo_duplicado_devuelve_error()
    {
        Visita::create([
            'codigo' => 'V-DUP-20260609-0001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha_programada' => '2026-06-09',
            'estado' => 'pendiente',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/visitas', [
            'codigo' => 'V-DUP-20260609-0001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha_programada' => '2026-06-09',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);
    }

    public function test_by_codigo_endpoint_devuelve_datos_completos()
    {
        Visita::create([
            'codigo' => 'V-FULL-20260609-ABCD',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha_programada' => '2026-06-09',
            'observaciones' => 'Nota de prueba',
            'estado' => 'pendiente',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/visitas/by-codigo/V-FULL-20260609-ABCD');

        $response->assertStatus(200)
            ->assertJson([
                'exists' => true,
                'data' => [
                    'codigo' => 'V-FULL-20260609-ABCD',
                    'predio_id' => $this->predio->id,
                    'observaciones' => 'Nota de prueba',
                    'estado' => 'pendiente',
                ],
            ]);
    }

    public function test_by_codigo_endpoint_codigo_inexistente()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/visitas/by-codigo/NO-EXISTE');

        $response->assertStatus(200)
            ->assertJson(['exists' => false]);
    }

    public function test_by_codigo_requiere_autenticacion()
    {
        $response = $this->getJson('/api/visitas/by-codigo/V-TEST-1234');

        $response->assertStatus(401);
    }

    public function test_upload_visitas_detecta_duplicado_existente()
    {
        Visita::create([
            'codigo' => 'V-SYNC-20260609-0001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha_programada' => '2026-06-09',
            'estado' => 'pendiente',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/sync/visitas', [
            'visitas' => [
                [
                    'codigo' => 'V-SYNC-20260609-0001',
                    'predio_id' => $this->predio->id,
                    'fecha_programada' => '2026-06-09',
                    'observaciones' => 'Intento duplicado',
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertCount(1, $response['procesados']);
        $this->assertStringContainsString('V-SYNC-20260609-0001', $response['procesados'][0]);
    }
}
