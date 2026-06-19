<?php

namespace Tests\Feature;

use App\Models\AreteCenso;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AreteCensoTest extends TestCase
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
        $this->actingAs($this->admin)->get(route('aretes-censo.index'))->assertStatus(200);
    }

    public function test_create()
    {
        $this->actingAs($this->admin)->get(route('aretes-censo.create'))->assertStatus(200);
    }

    public function test_store()
    {
        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $this->actingAs($this->admin)->post(route('aretes-censo.store'), [
            'numero_arete' => 'CSO-TEST-001',
            'productor_id' => $productor->id,
            'predio_id' => $predio->id,
            'raza' => 'Suizo',
            'sexo' => 'Macho',
        ])->assertRedirect(route('aretes-censo.index'));

        $this->assertDatabaseHas('aretes_censo', ['numero_arete' => 'CSO-TEST-001']);
    }

    public function test_show()
    {
        $arete = AreteCenso::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('aretes-censo.show', $arete->id))
            ->assertStatus(200);
    }

    public function test_edit()
    {
        $arete = AreteCenso::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('aretes-censo.edit', $arete->id))
            ->assertStatus(200);
    }

    public function test_update()
    {
        $arete = AreteCenso::factory()->create();

        $this->actingAs($this->admin)->put(route('aretes-censo.update', $arete->id), [
            'numero_arete' => 'CSO-TEST-UPD',
            'productor_id' => $arete->productor_id,
            'predio_id' => $arete->predio_id,
        ])->assertRedirect(route('aretes-censo.index'));

        $this->assertDatabaseHas('aretes_censo', ['numero_arete' => 'CSO-TEST-UPD']);
    }

    public function test_destroy()
    {
        $arete = AreteCenso::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('aretes-censo.destroy', $arete->id))
            ->assertRedirect(route('aretes-censo.index'));

        $this->assertDatabaseMissing('aretes_censo', ['id' => $arete->id]);
    }

    public function test_medico_no_puede_acceder()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $this->actingAs($medico)
            ->get(route('aretes-censo.index'))
            ->assertForbidden();
    }
}
