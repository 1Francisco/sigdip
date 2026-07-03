<?php

namespace Tests\Feature\Api;

use App\Models\AreteCenso;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AreteBuscarTest extends TestCase
{
    use RefreshDatabase;

    private AreteCenso $arete;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        Sanctum::actingAs($admin);

        $productor = Productor::factory()->create();
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);
        $this->arete = AreteCenso::factory()->create([
            'numero_arete' => 'MX-ARETE-12345',
            'productor_id' => $productor->id,
            'predio_id' => $predio->id,
        ]);
    }

    public function test_buscar_arete_encuentra_por_numero()
    {
        $response = $this->getJson('/api/censo/buscar-arete/MX-ARETE-12345');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['raza', 'sexo', 'edad_meses', 'fecha_nacimiento']]);
    }

    public function test_buscar_arete_no_encontrado_404()
    {
        $response = $this->getJson('/api/censo/buscar-arete/NO-EXISTE');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_buscar_arete_exact_match_no_parcial()
    {
        $response = $this->getJson('/api/censo/buscar-arete/ARETE');

        $response->assertStatus(404);
    }

    public function test_buscar_arete_vacio_no_encuentra()
    {
        $response = $this->getJson('/api/censo/buscar-arete/');

        $response->assertStatus(404);
    }

    public function test_buscar_arete_web_route()
    {
        $response = $this->getJson('/api/buscar-arete/MX-ARETE-12345');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
