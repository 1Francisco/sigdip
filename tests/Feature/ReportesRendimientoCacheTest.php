<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportesRendimientoCacheTest extends TestCase
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

    public function test_cache_store_and_retrieve()
    {
        Cache::put('rendimiento_kpi_global', ['total_inspecciones' => 100], 60);

        $cached = Cache::get('rendimiento_kpi_global');

        $this->assertEquals(['total_inspecciones' => 100], $cached);
    }

    public function test_cache_forgets_on_new_data()
    {
        Cache::put('rendimiento_kpi_global', ['total_inspecciones' => 50], 60);

        Cache::forget('rendimiento_kpi_global');

        $this->assertNull(Cache::get('rendimiento_kpi_global'));
    }

    public function test_page_carga_con_cache_de_inspecciones()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(3)->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);

        Cache::remember('rendimiento_inspecciones_global', 60, function () {
            return Inspeccion::with(['predio.productor', 'veterinario'])
                ->where('estado', 'sincronizado')
                ->get();
        });

        $response = $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento'))
            ->assertStatus(200);

        $response->assertSee('3');
    }
}
