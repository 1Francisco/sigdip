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

class RendimientoMergeComiteTest extends TestCase
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

    private function crearInspeccion(?string $contenidoComite = null): Inspeccion
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
        ]);

        if ($contenidoComite !== null) {
            $path = 'dictamenes_comite/merge_'.$inspeccion->id.'.pdf';
            Storage::disk('public')->put($path, $contenidoComite);
            $inspeccion->update(['dictamen_comite_path' => $path]);
        }

        return $inspeccion;
    }

    public function test_merge_usa_dictamen_comite_valido()
    {
        Storage::fake('public');
        Log::spy();

        $this->crearInspeccion(Pdf::loadHTML('<h1>DICTAMEN OFICIAL DEL COMITE</h1>')->output());

        $response = $this->actingAs($this->admin)->get(route('reportes.rendimiento.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
        Log::shouldNotHaveReceived('warning');
    }

    public function test_merge_con_comite_corrupto_cae_a_pdf_generado()
    {
        Storage::fake('public');
        Log::spy();

        $this->crearInspeccion('contenido que no es un pdf valido');

        $response = $this->actingAs($this->admin)->get(route('reportes.rendimiento.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
        Log::shouldHaveReceived('warning');
    }
}
