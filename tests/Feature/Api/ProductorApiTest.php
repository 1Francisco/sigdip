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

    public function test_show_predio_incluye_otros_predios_del_mismo_hato()
    {
        $productor = Productor::factory()->create(['clave' => 'BF-001']);
        $otroDelHato = Productor::factory()->create(['clave' => 'BF-001']);
        $otroDeOtroHato = Productor::factory()->create(['clave' => 'BD-002']);

        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        Predio::factory()->create(['productor_id' => $otroDelHato->id]);
        Predio::factory()->create(['productor_id' => $otroDeOtroHato->id]);

        $response = $this->getJson("/api/predios/{$predio->id}");

        $response->assertStatus(200);
        $otros = $response->json('data.otros_predios');
        $this->assertCount(1, $otros);
        $this->assertEquals($otroDelHato->id, $otros[0]['productor']['id']);
        $this->assertNotEquals($productor->id, $otros[0]['productor']['id']);
    }

    public function test_show_predio_sin_clave_no_lista_otros_predios()
    {
        $productor = Productor::factory()->create(['clave' => null]);
        $otroSinClave = Productor::factory()->create(['clave' => null]);

        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        Predio::factory()->create(['productor_id' => $otroSinClave->id]);

        $response = $this->getJson("/api/predios/{$predio->id}");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.otros_predios'));
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

    public function test_medico_no_puede_asignar_clave()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->postJson('/api/productores', [
            'nombre' => 'Medico Key Test',
            'apellido_paterno' => 'Productor',
            'curp' => 'KEY890101HSL00010X',
            'upp' => 'UPP-KEY-API',
            'clave' => 'BD-999999',
            'zona' => 'B',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('productores', [
            'curp' => 'KEY890101HSL00010X',
            'clave' => null,
            'zona' => null,
        ]);
    }

    public function test_clave_invalida_rechazada()
    {
        $response = $this->postJson('/api/productores', [
            'nombre' => 'Invalida Key',
            'apellido_paterno' => 'Test',
            'curp' => 'INV890101HSL00000X',
            'upp' => 'UPP-INV-01',
            'clave' => 'XD-123',
            'zona' => 'B',
        ]);

        $response->assertStatus(422);
    }

    public function test_clave_ad_es_valida()
    {
        $response = $this->postJson('/api/productores', [
            'nombre' => 'Admin AD',
            'apellido_paterno' => 'Test',
            'curp' => 'ADM890101HSL00001X',
            'upp' => 'UPP-AD-01',
            'clave' => 'AD-123456',
            'zona' => 'A',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('productores', [
            'curp' => 'ADM890101HSL00001X',
            'clave' => 'AD-123456',
            'zona' => 'A',
        ]);
    }

    // --- updateCoordenadas API ---

    public function test_api_actualiza_coordenadas()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->postJson("/api/predios/{$predio->id}/coordenadas", [
            'latitud' => 25.5,
            'longitud' => -108.0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('predios', [
            'id' => $predio->id,
            'latitud' => 25.5,
            'longitud' => -108.0,
        ]);
    }

    public function test_api_medico_no_actualiza_coordenadas_ajenas()
    {
        Role::firstOrCreate(['name' => 'Medico_Campo']);
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $productor = Productor::factory()->create(['medico_id' => null]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->postJson("/api/predios/{$predio->id}/coordenadas", [
            'latitud' => 25.0,
            'longitud' => -108.0,
        ]);

        $response->assertStatus(403);
    }

    public function test_api_coordenadas_invalidas()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->postJson("/api/predios/{$predio->id}/coordenadas", [
            'latitud' => 999,
            'longitud' => -108.0,
        ]);

        $response->assertStatus(422);
    }

    // --- desvincular-de-hato API ---

    public function test_api_vincular_a_hato_rechaza_productor_de_otro_hato()
    {
        $productor = Productor::factory()->create(['clave' => 'BA-0001']);
        $deOtroHato = Productor::factory()->create(['clave' => 'BF-9999']);

        $response = $this->postJson("/api/productores/{$productor->id}/vincular-a-hato", [
            'productor_id' => $deOtroHato->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Este productor ya pertenece al hato BF-9999. Gestiona ese hato para desvincularlo antes de asignarlo.');
        $this->assertSame('BF-9999', $deOtroHato->fresh()->clave);
    }

    public function test_api_desvincula_productor_de_hato()
    {
        $productor = Productor::factory()->create(['clave' => 'BA-0001']);

        $response = $this->postJson("/api/productores/{$productor->id}/desvincular-de-hato");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertNull($productor->fresh()->clave);
    }

    public function test_api_desvincular_productor_sin_clave()
    {
        $productor = Productor::factory()->create(['clave' => null]);

        $response = $this->postJson("/api/productores/{$productor->id}/desvincular-de-hato");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_api_desvincular_productor_no_encontrado()
    {
        $response = $this->postJson('/api/productores/999999/desvincular-de-hato');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
