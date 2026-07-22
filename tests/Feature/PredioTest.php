<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PredioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Productor $productor;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->productor = Productor::factory()->create();
    }

    public function test_index()
    {
        Predio::factory()->count(3)->create(['productor_id' => $this->productor->id]);

        $response = $this->actingAs($this->admin)->get(route('predios.index'));

        $response->assertStatus(200);
    }

    public function test_create()
    {
        $response = $this->actingAs($this->admin)->get(route('predios.create'));

        $response->assertStatus(200);
    }

    public function test_store()
    {
        $response = $this->followingRedirects()->actingAs($this->admin)->post(route('predios.store'), [
            'productor_id' => $this->productor->id,
            'nombre_rancho' => 'Rancho Nuevo',
            'clave_unidad_produccion' => 'CUP-001',
            'localidad' => 'Culiacán',
            'municipio' => 'Culiacán',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('predios', ['nombre_rancho' => 'Rancho Nuevo']);
    }

    public function test_edit()
    {
        $predio = Predio::factory()->create(['productor_id' => $this->productor->id]);

        $response = $this->actingAs($this->admin)->get(route('predios.edit', $predio));

        $response->assertStatus(200);
    }

    public function test_update()
    {
        $predio = Predio::factory()->create(['productor_id' => $this->productor->id]);

        $response = $this->followingRedirects()->actingAs($this->admin)->patch(route('predios.update', $predio), [
            'nombre_rancho' => 'Rancho Modificado',
            'clave_unidad_produccion' => $predio->clave_unidad_produccion,
            'productor_id' => $this->productor->id,
            'localidad' => 'Culiacán',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('predios', ['id' => $predio->id, 'nombre_rancho' => 'Rancho Modificado']);
    }

    public function test_show()
    {
        $predio = Predio::factory()->create(['productor_id' => $this->productor->id]);

        $response = $this->actingAs($this->admin)->get(route('predios.show', $predio));

        $response->assertStatus(200);
    }

    public function test_destroy()
    {
        $predio = Predio::factory()->create(['productor_id' => $this->productor->id]);

        $response = $this->actingAs($this->admin)->delete(route('predios.destroy', $predio));

        $response->assertRedirect(route('predios.index'));
        $this->assertDatabaseMissing('predios', ['id' => $predio->id]);
    }

    public function test_medico_solo_ve_predios_asignados()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productorAsignado = Productor::factory()->create(['medico_id' => $medico->id]);
        $predioAsignado = Predio::factory()->create(['productor_id' => $productorAsignado->id]);

        $productorOtro = Productor::factory()->create(['medico_id' => null]);
        $predioOtro = Predio::factory()->create(['productor_id' => $productorOtro->id]);

        $response = $this->actingAs($medico)->get(route('predios.index'));

        $response->assertStatus(200);
        $response->assertSee($predioAsignado->nombre_rancho);
        $response->assertDontSee($predioOtro->nombre_rancho);
    }

    public function test_show_paginates_animals()
    {
        $predio = Predio::factory()->create(['productor_id' => $this->productor->id]);
        Animal::factory()->count(15)->create(['predio_id' => $predio->id]);

        $response = $this->actingAs($this->admin)->get(route('predios.show', $predio));

        $response->assertStatus(200);
        $response->assertViewHas('animales');
        $this->assertCount(10, $response->viewData('animales'));
    }
}
