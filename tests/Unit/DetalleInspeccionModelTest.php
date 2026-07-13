<?php

namespace Tests\Unit;

use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetalleInspeccionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_detalle_pertenece_a_inspeccion()
    {
        $inspeccion = Inspeccion::factory()->create();
        $detalle = DetalleInspeccion::factory()->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => Animal::factory()->create(['predio_id' => $inspeccion->predio_id])->id,
        ]);

        $this->assertTrue($detalle->inspeccion->is($inspeccion));
    }

    public function test_detalle_pertenece_a_animal()
    {
        $animal = Animal::factory()->create();
        $detalle = DetalleInspeccion::factory()->create([
            'animal_id' => $animal->id,
            'inspeccion_id' => Inspeccion::factory()->create(['predio_id' => $animal->predio_id])->id,
        ]);

        $this->assertTrue($detalle->animal->is($animal));
    }
}
