<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Predio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnimalTest extends TestCase
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
    }

    public function test_index()
    {
        $this->actingAs($this->admin)->get(route('animales.index'))->assertStatus(200);
    }

    public function test_create()
    {
        $this->actingAs($this->admin)->get(route('animales.create'))->assertStatus(200);
    }

    public function test_store()
    {
        $predio = Predio::factory()->create();

        $this->actingAs($this->admin)->post(route('animales.store'), [
            'numero_arete_siniiga' => 'MX-TEST-001',
            'predio_id' => $predio->id,
            'raza' => 'Suizo',
            'sexo' => 'Macho',
            'edad' => 24,
        ])->assertRedirect(route('animales.index'));

        $this->assertDatabaseHas('animales', ['numero_arete_siniiga' => 'MX-TEST-001']);
    }

    public function test_show()
    {
        $animal = Animal::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('animales.show', $animal->id))
            ->assertStatus(200);
    }

    public function test_edit()
    {
        $animal = Animal::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('animales.edit', $animal->id))
            ->assertStatus(200);
    }

    public function test_update()
    {
        $animal = Animal::factory()->create();

        $this->actingAs($this->admin)->put(route('animales.update', $animal->id), [
            'numero_arete_siniiga' => 'MX-TEST-UPD',
            'predio_id' => $animal->predio_id,
            'raza' => 'Angus',
        ])->assertRedirect(route('animales.index'));

        $this->assertDatabaseHas('animales', ['numero_arete_siniiga' => 'MX-TEST-UPD']);
    }

    public function test_destroy()
    {
        $animal = Animal::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('animales.destroy', $animal->id))
            ->assertRedirect(route('animales.index'));

        $this->assertDatabaseMissing('animales', ['id' => $animal->id]);
    }

    public function test_medico_no_puede_acceder()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $this->actingAs($medico)
            ->get(route('animales.index'))
            ->assertForbidden();
    }
}
