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

class SabanaExcelDataApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Predio $predio;

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
        $this->predio = Predio::factory()->create([
            'productor_id' => $productor->id,
            'nombre_rancho' => 'Rancho Test',
        ]);
    }

    public function test_admin_obtiene_datos_sabana()
    {
        Sanctum::actingAs($this->admin);

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        $animal = Animal::factory()->create(['predio_id' => $this->predio->id]);
        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => $animal->id,
            'resultado_prueba' => 'Negativo',
        ]);

        $response = $this->getJson('/api/reportes/sabana-excel/data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'kpis' => ['total_inspecciones', 'total_probados', 'total_negativos', 'total_reactores'],
                'inspecciones' => [
                    '*' => [
                        'id', 'clave', 'predio', 'upp', 'productor', 'municipio', 'localidad',
                        'prueba', 'funcion_zootecnica', 'fecha', 'probados', 'negativos', 'reactores',
                        'latitud', 'longitud', 'observaciones', 'veterinario',
                    ],
                ],
                'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
                'filter_options' => ['zonas', 'tipos_actividad', 'medicos'],
                'is_admin',
            ]);

        $this->assertTrue($response->json('is_admin'));
        $this->assertEquals(1, $response->json('kpis.total_inspecciones'));
        $this->assertEquals(1, $response->json('kpis.total_probados'));
        $this->assertEquals(1, $response->json('kpis.total_negativos'));
        $this->assertEquals(0, $response->json('kpis.total_reactores'));
        $this->assertCount(1, $response->json('inspecciones'));
    }

    public function test_medico_obtiene_solo_sus_inspecciones()
    {
        $inspeccionPropia = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');
        $inspeccionAjena = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $otroMedico->id,
        ]);

        Animal::factory()->count(2)->create(['predio_id' => $this->predio->id])->each(function ($a) use ($inspeccionPropia) {
            DetalleInspeccion::factory()->create(['inspeccion_id' => $inspeccionPropia->id, 'animal_id' => $a->id]);
        });

        Sanctum::actingAs($this->medico);

        $response = $this->getJson('/api/reportes/sabana-excel/data');

        $response->assertStatus(200);
        $this->assertFalse($response->json('is_admin'));
        $this->assertEquals(1, $response->json('kpis.total_inspecciones'));
        $this->assertCount(1, $response->json('inspecciones'));
        $this->assertEquals($inspeccionPropia->id, $response->json('inspecciones.0.id'));
    }

    public function test_filtro_por_zona()
    {
        Sanctum::actingAs($this->admin);

        $productorB = Productor::factory()->create(['zona' => 'B']);
        $predioB = Predio::factory()->create(['productor_id' => $productorB->id]);

        Inspeccion::factory()->create(['predio_id' => $this->predio->id, 'veterinario_id' => $this->admin->id]);
        Inspeccion::factory()->create(['predio_id' => $predioB->id, 'veterinario_id' => $this->admin->id]);

        $response = $this->getJson('/api/reportes/sabana-excel/data?zona=A');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('kpis.total_inspecciones'));
    }

    public function test_filtro_por_medico()
    {
        Sanctum::actingAs($this->admin);

        Inspeccion::factory()->create(['predio_id' => $this->predio->id, 'veterinario_id' => $this->admin->id]);
        Inspeccion::factory()->create(['predio_id' => $this->predio->id, 'veterinario_id' => $this->medico->id]);

        $response = $this->getJson('/api/reportes/sabana-excel/data?medico_id='.$this->medico->id);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('kpis.total_inspecciones'));
    }

    public function test_paginacion_funciona()
    {
        Sanctum::actingAs($this->admin);

        Inspeccion::factory()->count(25)->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        $response = $this->getJson('/api/reportes/sabana-excel/data?page=2');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(25, $response->json('pagination.total'));
        $this->assertEquals(20, $response->json('pagination.per_page'));
        $this->assertCount(5, $response->json('inspecciones'));
    }

    public function test_unauthenticated_returns_401()
    {
        $response = $this->getJson('/api/reportes/sabana-excel/data');

        $response->assertStatus(401);
    }

    public function test_filter_options_incluyen_medicos()
    {
        Sanctum::actingAs($this->admin);

        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');

        $response = $this->getJson('/api/reportes/sabana-excel/data');

        $response->assertStatus(200);
        $medicos = $response->json('filter_options.medicos');
        $ids = collect($medicos)->pluck('id')->sort()->values()->toArray();
        $this->assertContains($this->medico->id, $ids);
        $this->assertContains($otroMedico->id, $ids);
    }
}
