<?php

namespace Tests\Feature\Api;

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
}
