<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Inspeccion $inspeccion;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $this->inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
        ]);
    }

    public function test_stream_pdf()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.stream', $this->inspeccion->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_export_pdf()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.pdf', $this->inspeccion->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_nonexistent_inspeccion()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.stream', 99999));

        $response->assertStatus(404);
    }

    public function test_export_excel()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_reportes_redirect_guest()
    {
        $response = $this->get(route('reportes.stream', $this->inspeccion->id));

        $response->assertRedirect('/login');
    }

    public function test_medico_can_view_pdf()
    {
        Role::firstOrCreate(['name' => 'Medico_Campo']);
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->actingAs($medico)->get(route('reportes.stream', $this->inspeccion->id));

        $response->assertStatus(200);
    }

    public function test_medico_no_puede_exportar_excel()
    {
        Role::firstOrCreate(['name' => 'Medico_Campo']);
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->actingAs($medico)->get(route('reportes.excel'));

        $response->assertStatus(403);
    }
}
