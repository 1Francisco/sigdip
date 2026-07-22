<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportesRendimientoTest extends TestCase
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

    public function test_admin_can_visit_rendimiento_page()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento'))
            ->assertStatus(200);
    }

    public function test_medico_campo_cannot_access_rendimiento()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $this->actingAs($medico)
            ->get(route('reportes.rendimiento'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('reportes.rendimiento'))
            ->assertRedirect(route('login'));
    }

    public function test_filters_render_without_error()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento', [
                'fecha_desde' => '2024-01-01',
                'fecha_hasta' => '2024-12-31',
                'estado' => 'sincronizado',
                'zona' => 'A',
            ]))
            ->assertStatus(200);
    }

    public function test_excel_export_returns_file()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.excel'))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pdf_export_returns_file()
    {
        Inspeccion::factory()->create([
            'tipo_prueba' => 'PPC',
            'estado' => 'sincronizado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.pdf'))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_empty_redirects_when_no_inspecciones()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.pdf', ['fecha_desde' => '2000-01-01', 'fecha_hasta' => '2000-01-02']))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_pdf_limit_exceeded_redirects()
    {
        $predio = Predio::factory()->create();
        Inspeccion::factory(51)->create([
            'predio_id' => $predio->id,
            'tipo_prueba' => 'PPC',
            'estado' => 'sincronizado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.pdf'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_rendimiento_page_shows_kpis_with_data()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create(['zona' => 'A']);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(5)->create([
            'veterinario_id' => $medico->id,
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento'))
            ->assertStatus(200);

        $response->assertSee('5');
        $response->assertSee($medico->name);
    }

    public function test_rendimiento_page_shows_medicos_table()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(2)->create([
            'veterinario_id' => $medico->id,
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento'))
            ->assertStatus(200);

        $response->assertSee($medico->name);
        $response->assertSee('Detalle de Rendimiento');
    }

    public function test_rendimiento_detalle_medico()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->create([
            'veterinario_id' => $medico->id,
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento', ['medico_id' => $medico->id]))
            ->assertStatus(200);

        $response->assertSee('Últimas inspecciones');
    }

    public function test_rendimiento_excel_with_filters()
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.excel', [
                'zona' => 'A',
                'estado' => 'sincronizado',
            ]))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
