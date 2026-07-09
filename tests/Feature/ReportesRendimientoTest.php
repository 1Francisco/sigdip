<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
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
        Inspeccion::factory(51)->create([
            'tipo_prueba' => 'PPC',
            'estado' => 'sincronizado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reportes.rendimiento.pdf'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
