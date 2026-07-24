<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DescargarApkTest extends TestCase
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

    public function test_guest_redirected_to_login()
    {
        $response = $this->get(route('descargar.apk'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('descargar.apk'));

        if (file_exists(public_path('sigdip.apk'))) {
            $response->assertStatus(200)
                ->assertHeader('Content-Disposition', 'attachment; filename=sigdip.apk');
        } else {
            $response->assertRedirect();
        }
    }

    public function test_medico_can_access()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->actingAs($medico)
            ->get(route('descargar.apk'));

        if (file_exists(public_path('sigdip.apk'))) {
            $response->assertHeader('Content-Disposition', 'attachment; filename=sigdip.apk');
        } else {
            $response->assertRedirect();
        }
    }
}
