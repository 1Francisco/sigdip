<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AreteCenso;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CascadeDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    public function test_eliminar_productor_cascadea_predios()
    {
        $productor = Productor::factory()->create();
        Predio::factory()->count(2)->create(['productor_id' => $productor->id]);

        $this->actingAs($this->admin)->delete(route('productores.destroy', $productor));

        $this->assertDatabaseMissing('productores', ['id' => $productor->id]);
        $this->assertDatabaseCount('predios', 0);
    }

    public function test_eliminar_predio_cascadea_animales()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        Animal::factory()->count(3)->create(['predio_id' => $predio->id]);

        $this->actingAs($this->admin)->delete(route('predios.destroy', $predio));

        $this->assertDatabaseMissing('predios', ['id' => $predio->id]);
        $this->assertDatabaseCount('animales', 0);
    }

    public function test_eliminar_inspeccion_cascadea_detalles()
    {
        $predio = Predio::factory()->create();
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
        ]);
        $animal = Animal::factory()->create(['predio_id' => $predio->id]);
        DetalleInspeccion::factory()->count(2)->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => $animal->id,
        ]);

        $this->actingAs($this->admin)->delete(route('inspecciones.destroy', $inspeccion));

        $this->assertDatabaseMissing('inspecciones', ['id' => $inspeccion->id]);
        $this->assertDatabaseCount('detalles_inspeccion', 0);
    }

    public function test_eliminar_animal_via_api_cascadea_detalles()
    {
        $predio = Predio::factory()->create();
        $animal = Animal::factory()->create(['predio_id' => $predio->id]);
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
        ]);
        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $inspeccion->id,
            'animal_id' => $animal->id,
        ]);

        $animal->delete();

        $this->assertDatabaseMissing('animales', ['id' => $animal->id]);
        $this->assertDatabaseCount('detalles_inspeccion', 0);
    }

    public function test_eliminar_predio_via_api_no_deja_inspecciones_huerfanas()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        $predio->delete();

        $this->assertDatabaseMissing('predios', ['id' => $predio->id]);
        $this->assertDatabaseCount('inspecciones', 0);
    }

    public function test_eliminar_productor_via_api_cascadea_aretes_censo()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        AreteCenso::factory()->create([
            'productor_id' => $productor->id,
            'predio_id' => $predio->id,
        ]);

        $productor->delete();

        $this->assertDatabaseMissing('productores', ['id' => $productor->id]);
        $this->assertDatabaseCount('aretes_censo', 0);
    }
}
