<?php

namespace Tests\Feature\Api;

use App\Models\Animal;
use App\Models\Predio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnimalApiTest extends TestCase
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

    public function test_can_list_animales_as_admin()
    {
        Animal::factory()->count(3)->create();

        $response = $this->getJson('/api/animales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_cannot_list_animales_as_medico()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->getJson('/api/animales');

        $response->assertStatus(403);
    }

    public function test_can_show_animal_as_admin()
    {
        $animal = Animal::factory()->create();

        $response = $this->getJson("/api/animales/{$animal->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $animal->id)
            ->assertJsonPath('data.numero_arete_siniiga', $animal->numero_arete_siniiga);
    }

    public function test_cannot_show_animal_as_medico()
    {
        $animal = Animal::factory()->create();
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->getJson("/api/animales/{$animal->id}");

        $response->assertStatus(403);
    }

    public function test_can_create_animal_as_admin()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/animales', [
            'numero_arete_siniiga' => 'MX-TEST-001',
            'predio_id' => $predio->id,
            'raza' => 'Suizo',
            'sexo' => 'Macho',
            'edad' => 24,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.numero_arete_siniiga', 'MX-TEST-001');
        $this->assertDatabaseHas('animales', ['numero_arete_siniiga' => 'MX-TEST-001']);
    }

    public function test_cannot_create_animal_as_medico()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->postJson('/api/animales', [
            'numero_arete_siniiga' => 'MX-TEST-001',
            'predio_id' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_can_update_animal_as_admin()
    {
        $animal = Animal::factory()->create();

        $response = $this->putJson("/api/animales/{$animal->id}", [
            'numero_arete_siniiga' => $animal->numero_arete_siniiga,
            'predio_id' => $animal->predio_id,
            'raza' => 'Angus',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.raza', 'Angus');
        $this->assertDatabaseHas('animales', ['id' => $animal->id, 'raza' => 'Angus']);
    }

    public function test_cannot_update_animal_as_medico()
    {
        $animal = Animal::factory()->create();
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->putJson("/api/animales/{$animal->id}", [
            'numero_arete_siniiga' => $animal->numero_arete_siniiga,
            'predio_id' => $animal->predio_id,
        ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_animal_as_admin()
    {
        $animal = Animal::factory()->create();

        $response = $this->deleteJson("/api/animales/{$animal->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('animales', ['id' => $animal->id]);
    }

    public function test_cannot_delete_animal_as_medico()
    {
        $animal = Animal::factory()->create();
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');
        Sanctum::actingAs($medico);

        $response = $this->deleteJson("/api/animales/{$animal->id}");

        $response->assertStatus(403);
    }

    public function test_create_animal_validation()
    {
        $response = $this->postJson('/api/animales', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['numero_arete_siniiga', 'predio_id']);
    }

    public function test_can_search_animales()
    {
        $predio = Predio::factory()->create();
        Animal::factory()->create(['numero_arete_siniiga' => 'ABC-123', 'predio_id' => $predio->id]);
        Animal::factory()->create(['numero_arete_siniiga' => 'XYZ-456', 'predio_id' => $predio->id]);

        $response = $this->getJson('/api/animales?search=ABC');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_animales_by_predio()
    {
        $predio1 = Predio::factory()->create();
        $predio2 = Predio::factory()->create();
        Animal::factory()->create(['predio_id' => $predio1->id]);
        Animal::factory()->create(['predio_id' => $predio2->id]);

        $response = $this->getJson("/api/animales?predio_id={$predio1->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
