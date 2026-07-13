<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebDashboardRendimientoTest extends TestCase
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

    public function test_admin_dashboard_shows_rendimiento_card()
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Rendimiento Veterinarios');
    }

    public function test_admin_dashboard_rendimiento_data()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        Inspeccion::factory()->count(4)->create([
            'veterinario_id' => $medico->id,
            'predio_id' => $predio->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);

        $response->assertSee('Rendimiento Veterinarios');
        $response->assertSee($medico->name);
    }

    public function test_medico_campo_does_not_see_rendimiento_card()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->actingAs($medico)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);

        $response->assertDontSee('Rendimiento Veterinarios');
    }
}
