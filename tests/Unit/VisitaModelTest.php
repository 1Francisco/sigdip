<?php

namespace Tests\Unit;

use App\Models\Inspeccion;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_visita_tiene_inspeccion()
    {
        $visita = Visita::factory()->create();
        $inspeccion = Inspeccion::factory()->create([
            'visita_id' => $visita->id,
            'predio_id' => $visita->predio_id,
            'veterinario_id' => $visita->veterinario_id,
        ]);

        $this->assertTrue($visita->inspeccion->is($inspeccion));
    }

    public function test_visita_cast_fecha_programada()
    {
        $visita = Visita::factory()->create([
            'fecha_programada' => '2026-07-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $visita->fecha_programada);
        $this->assertEquals('2026-07-15', $visita->fecha_programada->format('Y-m-d'));
    }

    public function test_visita_cast_inyeccion_boolean()
    {
        $visita = Visita::factory()->create(['inyeccion' => true]);

        $this->assertIsBool($visita->inyeccion);
        $this->assertTrue($visita->inyeccion);
    }
}
