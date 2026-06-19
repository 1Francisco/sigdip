<?php

namespace Tests\Feature\Api;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitaApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->user = User::factory()->create();
        $this->user->assignRole('Administrador');
        Sanctum::actingAs($this->user);

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    public function test_index()
    {
        Visita::factory()->count(3)->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/visitas');

        $response->assertStatus(200);
    }

    public function test_store()
    {
        $response = $this->postJson('/api/visitas', [
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
            'fecha_programada' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
    }

    public function test_show()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/visitas/{$visita->id}");

        $response->assertStatus(200);
    }

    public function test_check_codigo()
    {
        Visita::factory()->create([
            'codigo' => 'V-TEST-001',
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/visitas/check-codigo/V-TEST-001');

        $response->assertStatus(200)
            ->assertJson(['exists' => true]);
    }

    public function test_update()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/visitas/{$visita->id}", [
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
            'fecha_programada' => now()->addDays(3)->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
    }

    public function test_update_estado()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->patchJson("/api/visitas/{$visita->id}/estado", [
            'estado' => 'completada',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('completada', $visita->fresh()->estado);
    }

    public function test_reprogramar()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
            'estado' => 'cancelada',
        ]);

        $response = $this->patchJson("/api/visitas/{$visita->id}/reprogramar", [
            'fecha_programada' => now()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pendiente', $visita->fresh()->estado);
    }

    public function test_check_codigo_no_existe()
    {
        $response = $this->getJson('/api/visitas/check-codigo/NO-EXISTE');

        $response->assertStatus(200)
            ->assertJson(['exists' => false]);
    }

    public function test_search_visitas()
    {
        $predio = Predio::factory()->create(['nombre_rancho' => 'Rancho Especial']);
        Visita::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->user->id,
        ]);
        Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/visitas?search=Especial');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
