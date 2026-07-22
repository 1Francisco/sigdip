<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportSabanaExcelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Predio $predio;

    private Inspeccion $inspeccion;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $this->inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'PPC',
        ]);
    }

    public function test_admin_puede_descargar_excel()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_medico_puede_descargar_excel()
    {
        $response = $this->actingAs($this->medico)->get(route('reportes.excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_excel_con_filtro_fechas()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.excel', [
            'desde' => now()->subMonth()->format('Y-m-d'),
            'hasta' => now()->addMonth()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_excel_incluye_datos_de_inspeccion()
    {
        DetalleInspeccion::factory()->count(5)->create([
            'inspeccion_id' => $this->inspeccion->id,
            'animal_id' => Animal::factory()->create(['predio_id' => $this->predio->id])->id,
            'resultado_prueba' => 'Negativo',
        ]);

        $detalle = DetalleInspeccion::factory()->create([
            'inspeccion_id' => $this->inspeccion->id,
            'animal_id' => Animal::factory()->create(['predio_id' => $this->predio->id])->id,
            'resultado_prueba' => 'Positivo',
        ]);
        $this->assertDatabaseHas('detalles_inspeccion', ['id' => $detalle->id, 'resultado_prueba' => 'Positivo']);

        $response = $this->actingAs($this->admin)->get(route('reportes.excel'));

        $response->assertStatus(200);
    }
}
