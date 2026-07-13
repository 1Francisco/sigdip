<?php

namespace Tests\Unit;

use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspeccionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspeccion_pertenece_a_visita()
    {
        $visita = Visita::factory()->create();
        $inspeccion = Inspeccion::factory()->create([
            'visita_id' => $visita->id,
            'predio_id' => $visita->predio_id,
            'veterinario_id' => $visita->veterinario_id,
        ]);

        $this->assertTrue($inspeccion->visita->is($visita));
    }

    public function test_inspeccion_tiene_detalles()
    {
        $inspeccion = Inspeccion::factory()->create();
        DetalleInspeccion::factory()->count(3)->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => Animal::factory()->create(['predio_id' => $inspeccion->predio_id])->id,
        ]);

        $this->assertCount(3, $inspeccion->detalles);
    }

    public function test_inspeccion_pertenece_a_veterinario()
    {
        $veterinario = User::factory()->create();
        $inspeccion = Inspeccion::factory()->create(['veterinario_id' => $veterinario->id]);

        $this->assertTrue($inspeccion->veterinario->is($veterinario));
    }

    public function test_inspeccion_pertenece_a_predio()
    {
        $predio = Predio::factory()->create();
        $inspeccion = Inspeccion::factory()->create(['predio_id' => $predio->id]);

        $this->assertTrue($inspeccion->predio->is($predio));
    }
}
