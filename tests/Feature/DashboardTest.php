<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);
    }

    public function test_admin_puede_ver_dashboard()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_medico_puede_ver_dashboard()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->actingAs($medico)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_dashboard_redirige_sin_autenticar()
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect('/login');
    }
}
