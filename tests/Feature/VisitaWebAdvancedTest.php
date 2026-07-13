<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitaWebAdvancedTest extends TestCase
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

        $productor = Productor::factory()->create(['medico_id' => $this->medico->id]);
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
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

    public function test_update_estado_invalido()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('visitas.updateEstado', $visita), [
            'estado' => 'inexistente',
        ]);

        $response->assertSessionHasErrors('estado');
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

    public function test_medico_puede_reprogramar_su_propia_visita()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'cancelada',
        ]);

        $nuevaFecha = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->medico)->patch(route('visitas.reprogramar', $visita), [
            'fecha_programada' => $nuevaFecha,
        ]);

        $response->assertSessionHas('success');
    }
}
