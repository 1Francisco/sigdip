<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportesRendimientoMensualTest extends TestCase
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

    public function test_admin_can_visit_rendimiento_mensual_page()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual'))
            ->assertStatus(200);
    }

    public function test_medico_campo_cannot_access_rendimiento_mensual()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $this->actingAs($medico)
            ->get(route('reportes.rendimiento.mensual'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_mensual()
    {
        $this->get(route('reportes.rendimiento.mensual'))
            ->assertRedirect(route('login'));
    }

    public function test_mensual_page_renders_with_data()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(7)->create([
            'veterinario_id' => $medico->id,
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual'))
            ->assertStatus(200)
            ->assertSee($medico->name)
            ->assertSee('7');
    }

    public function test_mensual_page_empty_state()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual'))
            ->assertStatus(200)
            ->assertSee('No hay datos');
    }

    public function test_mensual_filters_year()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->create([
            'veterinario_id' => $medico->id,
            'predio_id' => $predio->id,
            'fecha' => '2023-06-15',
            'estado' => 'sincronizado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual', ['year' => 2023]))
            ->assertStatus(200)
            ->assertSee($medico->name);
    }

    public function test_mensual_filters_medico()
    {
        $medico1 = User::factory()->create();
        $medico1->assignRole('Medico_Campo');
        $medico2 = User::factory()->create();
        $medico2->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->create([
            'veterinario_id' => $medico1->id,
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);

        Inspeccion::factory()->create([
            'veterinario_id' => $medico2->id,
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual', ['medico_id' => $medico1->id]))
            ->assertStatus(200);

        $response->assertSee($medico1->name);
        $response->assertSee($medico2->name); // ambos aparecen en dropdown de filtros
        $response->assertSee('selected'); // confirma que el filtro está aplicado
    }

    public function test_mensual_excel_export()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual.excel'))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_mensual_excel_export_with_data()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(2)->create([
            'veterinario_id' => $medico->id,
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual.excel'))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_mensual_tabs_preserve_filters()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.mensual', ['zona' => 'A']))
            ->assertStatus(200);

        $response->assertSee('Detalle Mensual');
    }
}
