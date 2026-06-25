<?php

namespace Tests\Feature\Api;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductorApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->user = User::factory()->create();
        $this->user->assignRole('Administrador');
        Sanctum::actingAs($this->user);
    }

    public function test_index()
    {
        Productor::factory()->count(3)->create();

        $response = $this->getJson('/api/productores');

        $response->assertStatus(200);
    }

    public function test_show()
    {
        $productor = Productor::factory()->create();

        $response = $this->getJson("/api/productores/{$productor->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $productor->id);
    }

    public function test_store()
    {
        $response = $this->postJson('/api/productores', [
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'curp' => 'MALO890101HSL00000',
            'upp' => 'UPP-API-001',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('productores', ['nombre' => 'María']);
    }

    public function test_update()
    {
        $productor = Productor::factory()->create();

        $response = $this->putJson("/api/productores/{$productor->id}", [
            'nombre' => 'Actualizado',
            'apellido_paterno' => 'Nuevo',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', ['id' => $productor->id, 'nombre' => 'Actualizado']);
    }

    public function test_predios()
    {
        $productor = Productor::factory()->create();
        Predio::factory()->count(2)->create(['productor_id' => $productor->id]);

        $response = $this->getJson('/api/predios');

        $response->assertStatus(200);
    }

    public function test_store_rancho()
    {
        $productor = Productor::factory()->create();

        $response = $this->postJson('/api/predios', [
            'productor_id' => $productor->id,
            'nombre_rancho' => 'Rancho API',
            'clave_unidad_produccion' => 'CUP-API-001',
            'localidad' => 'Mocorito',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('predios', ['nombre_rancho' => 'Rancho API']);
    }

    public function test_update_rancho()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->putJson("/api/predios/{$predio->id}", [
            'nombre_rancho' => 'Rancho Actualizado',
            'clave_unidad_produccion' => $predio->clave_unidad_produccion,
            'productor_id' => $productor->id,
            'localidad' => 'Guasave',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('predios', ['id' => $predio->id, 'nombre_rancho' => 'Rancho Actualizado']);
    }

    public function test_destroy_productor()
    {
        $productor = Productor::factory()->create();

        $response = $this->deleteJson("/api/productores/{$productor->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('productores', ['id' => $productor->id]);
    }

    public function test_destroy_predio()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->deleteJson("/api/predios/{$predio->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('predios', ['id' => $predio->id]);
    }

    public function test_search_productores()
    {
        Productor::factory()->create(['nombre' => 'Juan']);
        Productor::factory()->create(['nombre' => 'Pedro']);

        $response = $this->getJson('/api/productores?search=Juan');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_search_predios()
    {
        $productor = Productor::factory()->create(['nombre' => 'Carlos', 'apellido_paterno' => 'Lopez']);
        Predio::factory()->create([
            'nombre_rancho' => 'Rancho Sol',
            'clave_unidad_produccion' => 'CUP-SOL',
            'localidad' => 'LocalidadX',
            'productor_id' => $productor->id,
        ]);
        Predio::factory()->create([
            'nombre_rancho' => 'Rancho Luna',
            'clave_unidad_produccion' => 'CUP-LUNA',
            'localidad' => 'LocalidadY',
            'productor_id' => $productor->id,
        ]);

        $response = $this->getJson('/api/predios?search=Sol');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_show_predio()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->getJson("/api/predios/{$predio->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $predio->id)
            ->assertJsonPath('data.productor.id', $productor->id);
    }

    public function test_medico_solo_ve_sus_productores_asignados()
    {
        Role::firstOrCreate(['name' => 'Medico_Campo']);
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $asignado = Productor::factory()->create(['medico_id' => $medico->id]);
        $otro = Productor::factory()->create(['medico_id' => null]);

        $response = $this->getJson('/api/productores');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($asignado->id, $ids);
        $this->assertNotContains($otro->id, $ids);
    }
}
