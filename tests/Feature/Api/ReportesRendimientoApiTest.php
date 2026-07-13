<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportesRendimientoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    public function test_admin_can_access_rendimiento_api()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/rendimiento');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'filters' => [
                    'years',
                    'localidades',
                    'medicos'
                ],
                'kpis' => [
                    'total_inspecciones',
                    'total_visitas',
                    'medicos_activos',
                    'total_animales',
                    'total_reactores'
                ],
                'medicosRendimiento',
                'actividades',
                'nombresPruebas',
                'zonas',
                'cuarentenasD',
                'cuarentenasP',
                'totalSinCuarentena',
                'meses',
                'mensualRows',
                'selectedYear',
                'detalleMedico'
            ]);
    }

    public function test_medico_cannot_access_rendimiento_api()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->getJson('/api/reportes/rendimiento');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_rendimiento_api()
    {
        $response = $this->getJson('/api/reportes/rendimiento');

        $response->assertStatus(401);
    }

    public function test_admin_can_download_excel()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/rendimiento/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_download_mensual_excel()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/rendimiento/mensual/excel');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_download_mensual_pdf()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/rendimiento/mensual/pdf');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
