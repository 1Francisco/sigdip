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
}
