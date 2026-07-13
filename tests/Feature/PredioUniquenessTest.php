<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PredioUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Productor $productor;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->productor = Productor::factory()->create();
    }

    public function test_store_duplica_clave_unidad_produccion()
    {
        Predio::factory()->create([
            'productor_id' => $this->productor->id,
            'clave_unidad_produccion' => 'CUP-UNICO',
        ]);

        $response = $this->actingAs($this->admin)->post(route('predios.store'), [
            'productor_id' => $this->productor->id,
            'nombre_rancho' => 'Otro Rancho',
            'clave_unidad_produccion' => 'CUP-UNICO',
            'localidad' => 'Culiacán',
        ]);

        $response->assertSessionHasErrors('clave_unidad_produccion');
    }

    public function test_update_permite_misma_clave()
    {
        $predio = Predio::factory()->create([
            'productor_id' => $this->productor->id,
            'clave_unidad_produccion' => 'CUP-UNICO',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('predios.update', $predio), [
            'productor_id' => $this->productor->id,
            'nombre_rancho' => 'Actualizado',
            'clave_unidad_produccion' => 'CUP-UNICO',
            'localidad' => 'Mazatlán',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('predios', ['id' => $predio->id, 'nombre_rancho' => 'Actualizado']);
    }

    public function test_update_rechaza_clave_duplicada_de_otro_predio()
    {
        Predio::factory()->create([
            'productor_id' => $this->productor->id,
            'clave_unidad_produccion' => 'CUP-OTRO',
        ]);

        $predio = Predio::factory()->create([
            'productor_id' => $this->productor->id,
            'clave_unidad_produccion' => 'CUP-MIO',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('predios.update', $predio), [
            'productor_id' => $this->productor->id,
            'nombre_rancho' => 'Actualizado',
            'clave_unidad_produccion' => 'CUP-OTRO',
            'localidad' => 'Mazatlán',
        ]);

        $response->assertSessionHasErrors('clave_unidad_produccion');
    }
}
