<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $medico1;
    private User $medico2;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico1 = User::factory()->create();
        $this->medico1->assignRole('Medico_Campo');

        $this->medico2 = User::factory()->create();
        $this->medico2->assignRole('Medico_Campo');
    }

    public function test_medico_only_sees_assigned_producers()
    {
        // Producer assigned to medico1
        $prod1 = Productor::factory()->create(['medico_id' => $this->medico1->id, 'nombre' => 'Prod Uno']);
        // Producer assigned to medico2
        $prod2 = Productor::factory()->create(['medico_id' => $this->medico2->id, 'nombre' => 'Prod Dos']);

        // Log in as medico1
        $response = $this->actingAs($this->medico1)->get(route('productores.index'));
        $response->assertStatus(200);
        $response->assertSee('Prod Uno');
        $response->assertDontSee('Prod Dos');
    }

    public function test_medico_only_sees_assigned_predios()
    {
        // Producer assigned to medico1 and their predio
        $prod1 = Productor::factory()->create(['medico_id' => $this->medico1->id]);
        $predio1 = Predio::factory()->create(['productor_id' => $prod1->id, 'nombre_rancho' => 'Rancho Uno']);

        // Producer assigned to medico2 and their predio
        $prod2 = Productor::factory()->create(['medico_id' => $this->medico2->id]);
        $predio2 = Predio::factory()->create(['productor_id' => $prod2->id, 'nombre_rancho' => 'Rancho Dos']);

        // Log in as medico1
        $response = $this->actingAs($this->medico1)->get(route('predios.index'));
        $response->assertStatus(200);
        $response->assertSee('Rancho Uno');
        $response->assertDontSee('Rancho Dos');
    }

    public function test_medico_cannot_assign_producer_to_another_medico()
    {
        // Log in as medico1 and try to assign to medico2
        $response = $this->actingAs($this->medico1)->post(route('productores.store'), [
            'nombre' => 'Test',
            'apellido_paterno' => 'Productor',
            'curp' => 'TEST890101HSL00000',
            'upp' => 'UPP-TEST-1',
            'medico_id' => $this->medico2->id,
        ]);

        $response->assertSessionHasNoErrors();
        // The newly created productor should be assigned to medico1, NOT medico2
        $this->assertDatabaseHas('productores', [
            'curp' => 'TEST890101HSL00000',
            'medico_id' => $this->medico1->id,
        ]);
    }

    public function test_admin_can_assign_producer_to_any_medico()
    {
        // Log in as admin and assign to medico2
        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Test Admin',
            'apellido_paterno' => 'Productor',
            'curp' => 'TEST890101HSL00002',
            'upp' => 'UPP-TEST-2',
            'medico_id' => $this->medico2->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'curp' => 'TEST890101HSL00002',
            'medico_id' => $this->medico2->id,
        ]);
    }

    public function test_api_sync_catalogos_filters_for_medico()
    {
        // Producer assigned to medico1 and their predio
        $prod1 = Productor::factory()->create(['medico_id' => $this->medico1->id]);
        $predio1 = Predio::factory()->create(['productor_id' => $prod1->id]);

        // Producer assigned to medico2 and their predio
        $prod2 = Productor::factory()->create(['medico_id' => $this->medico2->id]);
        $predio2 = Predio::factory()->create(['productor_id' => $prod2->id]);

        // Authenticate medico1 in API
        Sanctum::actingAs($this->medico1);

        $response = $this->getJson('/api/sync/catalogos');
        $response->assertStatus(200);

        $data = $response->json('data');
        
        // Assert catalog contains only prod1 and predio1
        $this->assertCount(1, $data['productores']);
        $this->assertEquals($prod1->id, $data['productores'][0]['id']);
        
        $this->assertCount(1, $data['predios']);
        $this->assertEquals($predio1->id, $data['predios'][0]['id']);
    }

    public function test_productor_edad_minima_prueba_accessor()
    {
        // No key
        $prodDefault = Productor::factory()->make(['clave_cuarentena' => null]);
        $this->assertEquals(6, $prodDefault->edad_minima_prueba);

        // Provisional key
        $prodProvisional = Productor::factory()->make(['clave_cuarentena' => 'AP']);
        $this->assertEquals(6, $prodProvisional->edad_minima_prueba);

        // Definitive key
        $prodDefinitive = Productor::factory()->make(['clave_cuarentena' => 'AD']);
        $this->assertEquals(2, $prodDefinitive->edad_minima_prueba);

        $prodDefinitiveB = Productor::factory()->make(['clave_cuarentena' => 'BD']);
        $this->assertEquals(2, $prodDefinitiveB->edad_minima_prueba);
    }

    public function test_admin_can_assign_quarantine_key_and_zone()
    {
        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Admin Test Key',
            'apellido_paterno' => 'Productor',
            'curp' => 'KEY890101HSL00001X',
            'upp' => 'UPP-KEY-1',
            'clave_cuarentena' => 'BD',
            'zona' => 'B',
            'medico_id' => $this->medico2->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'curp' => 'KEY890101HSL00001X',
            'clave_cuarentena' => 'BD',
            'zona' => 'B',
        ]);
    }

    public function test_medico_cannot_assign_quarantine_key_and_zone()
    {
        $response = $this->actingAs($this->medico1)->post(route('productores.store'), [
            'nombre' => 'Medico Test Key',
            'apellido_paterno' => 'Productor',
            'curp' => 'KEY890101HSL00002X',
            'upp' => 'UPP-KEY-2',
            'clave_cuarentena' => 'BD',
            'zona' => 'B',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'curp' => 'KEY890101HSL00002X',
            'clave_cuarentena' => null,
            'zona' => null,
        ]);
    }

    public function test_inspeccion_dynamic_age_limit_validation()
    {
        // 1. Productor with key BP (Provisional - 6 months limit)
        $prodBP = Productor::factory()->create(['medico_id' => $this->medico1->id, 'clave_cuarentena' => 'BP', 'zona' => 'B']);
        $predioBP = Predio::factory()->create(['productor_id' => $prodBP->id]);

        $responseBP = $this->actingAs($this->medico1)->post(route('inspecciones.store'), [
            'predio_id' => $predioBP->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_inspeccion' => 'Movilización',
            'tipo_prueba' => 'P.P.C.',
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'ARETE1',
                    'edad_meses' => 3,
                    'sexo' => 'M',
                    'resultado' => '',
                ]
            ]
        ]);

        $responseBP->assertSessionHasNoErrors();
        // Since limit is 6 and animal is 3 months, result must be forced to 'No Aplica'
        $this->assertDatabaseHas('detalles_inspeccion', [
            'resultado_prueba' => 'No Aplica',
            'motivo_no_aplica' => 'Menor a 3 meses',
        ]);

        // 2. Productor with key BD (Definitive - 2 months limit)
        $prodBD = Productor::factory()->create(['medico_id' => $this->medico1->id, 'clave_cuarentena' => 'BD', 'zona' => 'B']);
        $predioBD = Predio::factory()->create(['productor_id' => $prodBD->id]);

        $responseBD = $this->actingAs($this->medico1)->post(route('inspecciones.store'), [
            'predio_id' => $predioBD->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_inspeccion' => 'Movilización',
            'tipo_prueba' => 'P.P.C.',
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'ARETE2',
                    'edad_meses' => 3, // older than 2 months!
                    'sexo' => 'M',
                    'resultado' => 'Negativo',
                ]
            ]
        ]);

        $responseBD->assertSessionHasNoErrors();
        // Animal is 3 months and limit is 2, so Negativo is allowed and NOT overwritten
        $this->assertDatabaseHas('detalles_inspeccion', [
            'resultado_prueba' => 'Negativo',
            'motivo_no_aplica' => null,
        ]);
    }
}

