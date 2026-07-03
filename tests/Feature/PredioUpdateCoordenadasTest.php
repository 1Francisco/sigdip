<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PredioUpdateCoordenadasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Productor $productorDelMedico;

    private Productor $productorDeOtro;

    private Predio $predioDelMedico;

    private Predio $predioDeOtro;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');

        $this->productorDelMedico = Productor::factory()->create(['medico_id' => $this->medico->id]);
        $this->productorDeOtro = Productor::factory()->create(['medico_id' => $otroMedico->id]);

        $this->predioDelMedico = Predio::factory()->create(['productor_id' => $this->productorDelMedico->id]);
        $this->predioDeOtro = Predio::factory()->create(['productor_id' => $this->productorDeOtro->id]);
    }

    public function test_admin_actualiza_coordenadas()
    {
        $response = $this->actingAs($this->admin)->post(
            route('predios.updateCoordenadas', $this->predioDelMedico),
            ['latitud' => 25.5, 'longitud' => -108.0]
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('predios', [
            'id' => $this->predioDelMedico->id,
            'latitud' => 25.5,
            'longitud' => -108.0,
        ]);
    }

    public function test_medico_actualiza_coordenadas_de_su_predio()
    {
        $response = $this->actingAs($this->medico)->post(
            route('predios.updateCoordenadas', $this->predioDelMedico),
            ['latitud' => 26.0, 'longitud' => -109.0]
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_medico_no_actualiza_coordenadas_de_otro_predio()
    {
        $response = $this->actingAs($this->medico)->post(
            route('predios.updateCoordenadas', $this->predioDeOtro),
            ['latitud' => 26.0, 'longitud' => -109.0]
        );

        $response->assertStatus(403);
    }

    public function test_coordenadas_invalidas()
    {
        $response = $this->actingAs($this->admin)->post(
            route('predios.updateCoordenadas', $this->predioDelMedico),
            ['latitud' => 999, 'longitud' => -108.0]
        );

        $response->assertStatus(422);
    }

    public function test_latitud_fuera_de_rango()
    {
        $response = $this->actingAs($this->admin)->post(
            route('predios.updateCoordenadas', $this->predioDelMedico),
            ['latitud' => -91, 'longitud' => -108.0]
        );

        $response->assertStatus(422);
    }

    public function test_longitud_fuera_de_rango()
    {
        $response = $this->actingAs($this->admin)->post(
            route('predios.updateCoordenadas', $this->predioDelMedico),
            ['latitud' => 25.0, 'longitud' => 181]
        );

        $response->assertStatus(422);
    }

    public function test_guest_redirigido_a_login()
    {
        $response = $this->post(
            route('predios.updateCoordenadas', $this->predioDelMedico),
            ['latitud' => 25.0, 'longitud' => -108.0]
        );

        $response->assertRedirect('/login');
    }
}
