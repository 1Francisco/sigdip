<?php

namespace Tests\Feature\Api;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitaApiDestroyTest extends TestCase
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

    public function test_admin_elimina_visita_web()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('visitas.destroy', $visita));

        $response->assertRedirect(route('visitas.index'));
        $this->assertDatabaseMissing('visitas', ['id' => $visita->id]);
    }

    public function test_medico_elimina_su_propia_visita()
    {
        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->medico)->delete(route('visitas.destroy', $visita));

        $response->assertRedirect(route('visitas.index'));
        $this->assertDatabaseMissing('visitas', ['id' => $visita->id]);
    }

    public function test_medico_no_elimina_visita_ajena()
    {
        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');

        $visita = Visita::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $otroMedico->id,
        ]);

        $response = $this->actingAs($this->medico)->delete(route('visitas.destroy', $visita));

        $response->assertStatus(403);
        $this->assertDatabaseHas('visitas', ['id' => $visita->id]);
    }
}
