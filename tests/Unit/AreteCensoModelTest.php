<?php

namespace Tests\Unit;

use App\Models\AreteCenso;
use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreteCensoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_arete_censo_fillable_fields()
    {
        $arete = AreteCenso::create([
            'numero_arete' => 'ABC-123',
            'productor_id' => Productor::factory()->create()->id,
            'predio_id' => Predio::factory()->create()->id,
            'raza' => 'Holstein',
            'sexo' => 'Hembra',
            'fecha_nacimiento' => '2020-01-15',
            'edad_meses' => 72,
            'sacrificio' => false,
        ]);

        $this->assertEquals('ABC-123', $arete->numero_arete);
        $this->assertEquals('Holstein', $arete->raza);
    }

    public function test_arete_censo_pertenece_a_productor_y_predio()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $arete = AreteCenso::factory()->create([
            'productor_id' => $productor->id,
            'predio_id' => $predio->id,
        ]);

        $this->assertTrue($arete->productor->is($productor));
        $this->assertTrue($arete->predio->is($predio));
    }
}
