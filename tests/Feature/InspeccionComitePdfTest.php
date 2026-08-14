<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InspeccionComitePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Inspeccion $inspeccion;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        Storage::fake('public');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $this->inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
        ]);
    }

    private function adjuntarDictamenComite(): string
    {
        $contenido = Pdf::loadHTML('<h1>DICTAMEN OFICIAL DEL COMITE</h1>')->output();
        $path = 'dictamenes_comite/test_'.$this->inspeccion->id.'.pdf';
        Storage::disk('public')->put($path, $contenido);
        $this->inspeccion->update(['dictamen_comite_path' => $path]);

        return $contenido;
    }

    public function test_stream_pdf_sirve_dictamen_comite_cuando_existe()
    {
        $comite = $this->adjuntarDictamenComite();

        $response = $this->actingAs($this->admin)->get(route('reportes.stream', $this->inspeccion->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="'.$this->inspeccion->buildPdfFilename().'"');
        $this->assertSame($comite, $response->baseResponse->getFile()->getContent());
    }

    public function test_stream_pdf_con_prototype_fuerza_pdf_generado()
    {
        $this->adjuntarDictamenComite();

        $response = $this->actingAs($this->admin)->get(route('reportes.stream', [$this->inspeccion->id, 'prototype' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    public function test_export_pdf_descarga_dictamen_comite_cuando_existe()
    {
        $comite = $this->adjuntarDictamenComite();

        $response = $this->actingAs($this->admin)->get(route('reportes.pdf', $this->inspeccion->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString($this->inspeccion->buildPdfFilename(), $response->headers->get('Content-Disposition'));
        $this->assertSame($comite, $response->baseResponse->getFile()->getContent());
    }

    public function test_export_pdf_sin_comite_genera_pdf()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.pdf', $this->inspeccion->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }
}
