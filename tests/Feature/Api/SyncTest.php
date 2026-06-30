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

class SyncTest extends TestCase
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

    public function test_catalogos()
    {
        $response = $this->getJson('/api/sync/catalogos');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['productores', 'predios']]);
    }

    public function test_dashboard_stats()
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
    }

    public function test_catalogos_incluye_medico_id_en_productor_de_visita()
    {
        $productorConMedico = Productor::factory()->create([
            'medico_id' => $this->user->id,
        ]);
        $predioConVisita = Predio::factory()->create(['productor_id' => $productorConMedico->id]);
        Visita::factory()->create([
            'predio_id' => $predioConVisita->id,
            'veterinario_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/sync/catalogos');

        $response->assertStatus(200);
        $predios = $response->json('data.predios');
        $this->assertNotEmpty($predios);
        $predio = collect($predios)->firstWhere('id', $predioConVisita->id);
        $this->assertNotNull($predio);
        $productor = $predio['productor'];
        $this->assertNotNull($productor);
        $this->assertEquals($this->user->id, $productor['medico_id']);
    }
}
