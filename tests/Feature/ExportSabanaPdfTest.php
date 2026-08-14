<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportSabanaPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Predio $predio;

    private Inspeccion $inspeccion;

    private Productor $productor;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

        $this->productor = Productor::factory()->create(['zona' => 'A']);
        $this->predio = Predio::factory()->create(['productor_id' => $this->productor->id]);

        $this->inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'Tuberculina',
        ]);
    }

    public function test_admin_puede_descargar_pdf_de_dictamenes()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.sabana.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="dictamenes_sabana_'.date('d-m-Y').'.pdf"');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_medico_puede_descargar_pdf_de_sus_dictamenes()
    {
        Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'Tuberculina',
        ]);

        $response = $this->actingAs($this->medico)->get(route('reportes.sabana.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_medico_sin_dictamenes_recibe_error()
    {
        $response = $this->actingAs($this->medico)->get(route('reportes.sabana.pdf'));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_pdf_respeta_filtro_de_zona()
    {
        $otroProductor = Productor::factory()->create(['zona' => 'B']);
        $otroPredio = Predio::factory()->create(['productor_id' => $otroProductor->id]);
        Inspeccion::factory()->create([
            'predio_id' => $otroPredio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'Tuberculina',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.sabana.pdf', ['zona' => 'A']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="dictamenes_sabana_zona_A.pdf"');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_pdf_rechaza_mas_de_50_dictamenes()
    {
        Inspeccion::factory()->count(51)->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'Tuberculina',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.sabana.pdf'));

        $response->assertStatus(302);
        $response->assertSessionHas('error', function (string $message) {
            return str_contains($message, '50');
        });
    }

    public function test_pdf_sabana_usa_dictamen_comite_valido()
    {
        Storage::fake('public');
        Log::spy();

        $path = 'dictamenes_comite/sabana_'.$this->inspeccion->id.'.pdf';
        Storage::disk('public')->put($path, Pdf::loadHTML('<h1>DICTAMEN OFICIAL DEL COMITE</h1>')->output());
        $this->inspeccion->update(['dictamen_comite_path' => $path]);

        $response = $this->actingAs($this->admin)->get(route('reportes.sabana.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
        Log::shouldNotHaveReceived('warning');
    }

    public function test_guest_es_redirigido_al_login()
    {
        $response = $this->get(route('reportes.sabana.pdf'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
