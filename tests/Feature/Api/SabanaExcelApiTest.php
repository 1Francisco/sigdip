<?php

namespace Tests\Feature\Api;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SabanaExcelApiTest extends TestCase
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

        $productor = Productor::factory()->create(['zona' => 'A']);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'Tuberculina',
        ]);
    }

    public function test_admin_descarga_sabana_excel_por_api()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/sabana-excel');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_medico_descarga_sabana_excel_por_api()
    {
        Sanctum::actingAs($this->medico);

        $response = $this->getJson('/api/reportes/sabana-excel');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_api_fallback_con_acento_funciona()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/s%C3%A1bana-excel');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_descarga_pdf_de_dictamenes_por_api()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/reportes/sabana-excel/pdf', ['Accept' => 'application/pdf']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_api_pdf_sin_auth_devuelve_401()
    {
        $response = $this->getJson('/api/reportes/sabana-excel/pdf');

        $response->assertStatus(401);
    }

    public function test_api_pdf_rechaza_mas_de_50_dictamenes()
    {
        $productor = Productor::factory()->create(['zona' => 'A']);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        Inspeccion::factory()->count(51)->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'Tuberculina',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/sabana-excel/pdf');

        $response->assertStatus(400);
        $response->assertJsonPath('message', function (string $message) {
            return str_contains($message, '50');
        });
    }
}
