<?php

namespace Tests\Feature\Api;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardApiRendimientoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');
    }

    public function test_admin_gets_rendimiento_data()
    {
        Sanctum::actingAs($this->admin);

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(3)->create([
            'veterinario_id' => $this->admin->id,
            'predio_id' => $predio->id,
        ]);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'rendimientoVeterinarios',
        ]);
        $response->assertJsonFragment(['isAdmin' => true]);
    }

    public function test_medico_campo_does_not_get_rendimiento()
    {
        Sanctum::actingAs($this->medico);

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->create([
            'veterinario_id' => $this->medico->id,
            'predio_id' => $predio->id,
        ]);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        $response->assertJsonMissing(['rendimientoVeterinarios']);
        $response->assertJsonFragment(['isAdmin' => false]);
    }

    public function test_unauthenticated_gets_401()
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(401);
    }

    public function test_rendimiento_data_is_accurate()
    {
        Sanctum::actingAs($this->admin);

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(5)->create([
            'veterinario_id' => $this->admin->id,
            'predio_id' => $predio->id,
        ]);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'totalInspecciones' => 5,
        ]);

        $rendimiento = $response->json('rendimientoVeterinarios');
        $this->assertNotEmpty($rendimiento);
        $this->assertEquals(5, $rendimiento[0]['total']);
    }

    public function test_rendimiento_empty_when_no_inspecciones()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('rendimientoVeterinarios'));
    }
}
