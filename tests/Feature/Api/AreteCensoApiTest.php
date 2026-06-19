<?php

namespace Tests\Feature\Api;

use App\Models\AreteCenso;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AreteCensoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        Sanctum::actingAs($this->admin);
    }

    public function test_can_list_aretes_censo_as_admin()
    {
        AreteCenso::factory()->count(3)->create();

        $response = $this->getJson('/api/aretes-censo');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_cannot_list_aretes_censo_as_medico()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->getJson('/api/aretes-censo');

        $response->assertStatus(403);
    }

    public function test_can_show_arete_censo_as_admin()
    {
        $arete = AreteCenso::factory()->create();

        $response = $this->getJson("/api/aretes-censo/{$arete->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $arete->id)
            ->assertJsonPath('data.numero_arete', $arete->numero_arete);
    }

    public function test_cannot_show_arete_censo_as_medico()
    {
        $arete = AreteCenso::factory()->create();
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->getJson("/api/aretes-censo/{$arete->id}");

        $response->assertStatus(403);
    }

    public function test_can_create_arete_censo_as_admin()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->postJson('/api/aretes-censo', [
            'numero_arete' => 'CSO-TEST-001',
            'productor_id' => $productor->id,
            'predio_id' => $predio->id,
            'raza' => 'Suizo',
            'sexo' => 'Macho',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.numero_arete', 'CSO-TEST-001');
        $this->assertDatabaseHas('aretes_censo', ['numero_arete' => 'CSO-TEST-001']);
    }

    public function test_cannot_create_arete_censo_as_medico()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->postJson('/api/aretes-censo', [
            'numero_arete' => 'CSO-TEST-001',
            'productor_id' => 1,
            'predio_id' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_can_update_arete_censo_as_admin()
    {
        $arete = AreteCenso::factory()->create();

        $response = $this->putJson("/api/aretes-censo/{$arete->id}", [
            'numero_arete' => $arete->numero_arete,
            'productor_id' => $arete->productor_id,
            'predio_id' => $arete->predio_id,
            'raza' => 'Angus',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.raza', 'Angus');
        $this->assertDatabaseHas('aretes_censo', ['id' => $arete->id, 'raza' => 'Angus']);
    }

    public function test_cannot_update_arete_censo_as_medico()
    {
        $arete = AreteCenso::factory()->create();
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->putJson("/api/aretes-censo/{$arete->id}", [
            'numero_arete' => $arete->numero_arete,
            'productor_id' => $arete->productor_id,
            'predio_id' => $arete->predio_id,
        ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_arete_censo_as_admin()
    {
        $arete = AreteCenso::factory()->create();

        $response = $this->deleteJson("/api/aretes-censo/{$arete->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('aretes_censo', ['id' => $arete->id]);
    }

    public function test_cannot_delete_arete_censo_as_medico()
    {
        $arete = AreteCenso::factory()->create();
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->deleteJson("/api/aretes-censo/{$arete->id}");

        $response->assertStatus(403);
    }

    public function test_create_arete_censo_validation()
    {
        $response = $this->postJson('/api/aretes-censo', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['numero_arete', 'productor_id', 'predio_id']);
    }

    public function test_can_search_aretes_censo()
    {
        Productor::factory()->create(['nombre' => 'Juan']);
        $predio = Predio::factory()->create();
        $productor1 = Productor::factory()->create();
        $productor2 = Productor::factory()->create();
        AreteCenso::factory()->create(['numero_arete' => 'ABC-123', 'productor_id' => $productor1->id, 'predio_id' => $predio->id]);
        AreteCenso::factory()->create(['numero_arete' => 'XYZ-456', 'productor_id' => $productor2->id, 'predio_id' => $predio->id]);

        $response = $this->getJson('/api/aretes-censo?search=ABC');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_aretes_censo_by_productor()
    {
        $predio = Predio::factory()->create();
        $productor1 = Productor::factory()->create();
        $productor2 = Productor::factory()->create();
        AreteCenso::factory()->create(['productor_id' => $productor1->id, 'predio_id' => $predio->id]);
        AreteCenso::factory()->create(['productor_id' => $productor2->id, 'predio_id' => $predio->id]);

        $response = $this->getJson("/api/aretes-censo?productor_id={$productor1->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
