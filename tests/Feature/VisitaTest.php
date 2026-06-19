<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    public function test_index()
    {
        Visita::factory()->count(3)->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('visitas.index'));

        $response->assertStatus(200);
    }

    public function test_create()
    {
        $response = $this->actingAs($this->admin)->get(route('visitas.create'));

        $response->assertStatus(200);
    }

    public function test_store()
    {
        $response = $this->followingRedirects()->actingAs($this->admin)->post(route('visitas.store'), [
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha_programada' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('visitas', ['predio_id' => $this->predio->id]);
    }

    public function test_medico_solo_puede_asignarse_a_si_mismo()
    {
        $response = $this->followingRedirects()->actingAs($this->medico)->post(route('visitas.store'), [
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha_programada' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('visitas', ['veterinario_id' => $this->medico->id]);
    }

    public function test_edit()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('visitas.edit', $visita));

        $response->assertStatus(200);
    }

    public function test_update_estado()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('visitas.updateEstado', $visita), [
            'estado' => 'completada',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('completada', $visita->fresh()->estado);
    }

    public function test_show()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('visitas.show', $visita));

        $response->assertStatus(200);
    }

    public function test_update()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('visitas.update', $visita), [
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'fecha_programada' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $response->assertSessionHas('success');
    }

    public function test_destroy()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('visitas.destroy', $visita));

        $response->assertRedirect(route('visitas.index'));
        $this->assertDatabaseMissing('visitas', ['id' => $visita->id]);
    }

    public function test_reprogramar()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'cancelada',
        ]);

        $nuevaFecha = now()->addWeek()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->patch(route('visitas.reprogramar', $visita), [
            'fecha_programada' => $nuevaFecha,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('pendiente', $visita->fresh()->estado);
    }
}
