<?php

namespace Tests\Unit;

use App\Models\Animal;
use App\Models\AreteCenso;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_productor_tiene_predios()
    {
        $productor = Productor::factory()->create();
        Predio::factory()->count(3)->create(['productor_id' => $productor->id]);

        $this->assertCount(3, $productor->predios);
    }

    public function test_predio_pertenece_a_productor()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $this->assertTrue($predio->productor->is($productor));
    }

    public function test_predio_tiene_animales()
    {
        $predio = Predio::factory()->create();
        Animal::factory()->count(5)->create(['predio_id' => $predio->id]);

        $this->assertCount(5, $predio->animales);
    }

    public function test_animal_pertenece_a_predio()
    {
        $predio = Predio::factory()->create();
        $animal = Animal::factory()->create(['predio_id' => $predio->id]);

        $this->assertTrue($animal->predio->is($predio));
    }

    public function test_inspeccion_tiene_detalles()
    {
        $inspeccion = Inspeccion::factory()->create();
        DetalleInspeccion::factory()->count(4)->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => Animal::factory()->create(['predio_id' => $inspeccion->predio_id])->id,
        ]);

        $this->assertCount(4, $inspeccion->detalles);
    }

    public function test_visita_pertenece_a_predio()
    {
        $predio = Predio::factory()->create();
        $visita = Visita::factory()->create(['predio_id' => $predio->id]);

        $this->assertTrue($visita->predio->is($predio));
    }

    public function test_visita_pertenece_a_veterinario()
    {
        $user = User::factory()->create();
        $visita = Visita::factory()->create(['veterinario_id' => $user->id]);

        $this->assertTrue($visita->veterinario->is($user));
    }

    public function test_arete_censo_pertenece_a_productor()
    {
        $productor = Productor::factory()->create();
        $arete = AreteCenso::factory()->create(['productor_id' => $productor->id]);

        $this->assertTrue($arete->productor->is($productor));
    }

    public function test_arete_censo_pertenece_a_predio()
    {
        $predio = Predio::factory()->create();
        $arete = AreteCenso::factory()->create(['predio_id' => $predio->id]);

        $this->assertTrue($arete->predio->is($predio));
    }

    public function test_nombre_completo_productor()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'López',
        ]);

        $this->assertEquals('Juan Pérez López', $productor->nombreCompleto);
    }

    public function test_detalle_inspeccion_motivo_no_aplica_es_fillable()
    {
        $inspeccion = Inspeccion::factory()->create();
        $animal = Animal::factory()->create(['predio_id' => $inspeccion->predio_id]);

        $detalle = DetalleInspeccion::create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => $animal->id,
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 6 meses',
        ]);

        $this->assertEquals('Menor a 6 meses', $detalle->motivo_no_aplica);
        $this->assertDatabaseHas('detalles_inspeccion', [
            'id' => $detalle->id,
            'motivo_no_aplica' => 'Menor a 6 meses',
        ]);
    }

    // --- Auto-zona desde clave_cuarentena ---

    public function test_auto_deriva_zona_a_desde_clave_ad()
    {
        $productor = Productor::create([
            'nombre' => 'Test',
            'apellido_paterno' => 'Zona',
            'clave_cuarentena' => 'AD-12345',
            'curp' => 'ZONA890101HSL00001',
            'upp' => 'UPP-ZONA-1',
        ]);

        $this->assertEquals('A', $productor->zona);
    }

    public function test_auto_deriva_zona_b_desde_clave_bd()
    {
        $productor = Productor::create([
            'nombre' => 'Test',
            'apellido_paterno' => 'Zona',
            'clave_cuarentena' => 'BD-12345',
            'curp' => 'ZONA890101HSL00002',
            'upp' => 'UPP-ZONA-2',
        ]);

        $this->assertEquals('B', $productor->zona);
    }

    public function test_auto_deriva_zona_con_minusculas()
    {
        $productor = Productor::create([
            'nombre' => 'Test',
            'apellido_paterno' => 'Zona',
            'clave_cuarentena' => 'ad-12345',
            'curp' => 'ZONA890101HSL00003',
            'upp' => 'UPP-ZONA-3',
        ]);

        $this->assertEquals('A', $productor->zona);
    }

    public function test_no_sobrescribe_zona_existente()
    {
        $productor = Productor::create([
            'nombre' => 'Test',
            'apellido_paterno' => 'Zona',
            'clave_cuarentena' => 'AD-12345',
            'zona' => 'B',
            'curp' => 'ZONA890101HSL00004',
            'upp' => 'UPP-ZONA-4',
        ]);

        $this->assertEquals('B', $productor->zona);
    }

    public function test_sin_clave_no_deriva_zona()
    {
        $productor = Productor::factory()->create(['clave_cuarentena' => null, 'zona' => null]);

        $this->assertNull($productor->zona);
    }

    public function test_clave_invalida_no_asigna_zona()
    {
        $productor = Productor::create([
            'nombre' => 'Test',
            'apellido_paterno' => 'Zona',
            'clave_cuarentena' => 'XD-12345',
            'curp' => 'ZONA890101HSL00005',
            'upp' => 'UPP-ZONA-5',
        ]);

        $this->assertNull($productor->zona);
    }

    // --- buildPdfFilename ---

    public function test_build_pdf_filename_con_productor()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
        ]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'fecha_inyeccion' => '2026-06-15',
        ]);

        $filename = $inspeccion->buildPdfFilename();

        $this->assertEquals('DICTAMEN_PEREZ_JUAN_15-06-2026.pdf', $filename);
    }

    public function test_build_pdf_filename_sin_productor_usando_make()
    {
        $predio = Predio::factory()->create();
        $inspeccion = Inspeccion::factory()->make([
            'predio_id' => $predio->id,
            'fecha_inyeccion' => null,
        ]);
        $inspeccion->predio->productor = null;

        $filename = $inspeccion->buildPdfFilename();

        $hoy = now()->format('d-m-Y');
        $this->assertStringEndsWith("_{$hoy}.pdf", $filename);
    }

    public function test_build_pdf_filename_sin_fecha_inyeccion_usa_now()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Ana',
            'apellido_paterno' => 'Luz',
        ]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'fecha_inyeccion' => null,
        ]);

        $filename = $inspeccion->buildPdfFilename();

        $hoy = now()->format('d-m-Y');
        $this->assertStringEndsWith("_{$hoy}.pdf", $filename);
    }

    public function test_build_pdf_filename_caracteres_especiales()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'José María',
            'apellido_paterno' => 'Pérez',
        ]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'fecha_inyeccion' => '2026-07-03',
        ]);

        $filename = $inspeccion->buildPdfFilename();

        $this->assertEquals('DICTAMEN_PEREZ_JOSE_MARIA_03-07-2026.pdf', $filename);
    }

    public function test_build_pdf_filename_formato_regex()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
        ]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'fecha_inyeccion' => '2026-01-01',
        ]);

        $filename = $inspeccion->buildPdfFilename();

        $this->assertMatchesRegularExpression(
            '/^DICTAMEN_[A-Z_]+_\d{2}-\d{2}-\d{4}\.pdf$/',
            $filename
        );
    }

    public function test_build_pdf_filename_sin_productor_default()
    {
        $predio = Predio::factory()->create();
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'fecha_inyeccion' => '2026-06-15',
        ]);

        $filename = $inspeccion->buildPdfFilename();

        $this->assertStringContainsString('DICTAMEN_', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }
}
