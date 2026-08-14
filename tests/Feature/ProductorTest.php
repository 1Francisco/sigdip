<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');
    }

    public function test_index()
    {
        Productor::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('productores.index'));

        $response->assertStatus(200);
    }

    public function test_create()
    {
        $response = $this->actingAs($this->admin)->get(route('productores.create'));

        $response->assertStatus(200);
    }

    public function test_create_zona_habilitada_para_admin()
    {
        $response = $this->actingAs($this->admin)->get(route('productores.create'));

        $response->assertStatus(200);
        $response->assertSee('id="zona_select" class="form-select rounded-3 bg-light"', false);
        $response->assertDontSee('id="zona_select" class="form-select rounded-3 bg-light" disabled', false);
    }

    public function test_create_multiple_zona_habilitada_para_admin()
    {
        $response = $this->actingAs($this->admin)->get(route('productores.create-multiple'));

        $response->assertStatus(200);
        $response->assertSee('id="zona_select" class="form-select rounded-3 bg-light"', false);
        $response->assertDontSee('id="zona_select" class="form-select rounded-3 bg-light" disabled', false);
    }

    public function test_create_multiple_con_prefill_zona_habilitada()
    {
        $productor = Productor::factory()->create(['clave' => 'BA-0001', 'zona' => 'B']);

        $response = $this->actingAs($this->admin)
            ->get(route('productores.create-multiple', ['prefill_from_productor_id' => $productor->id]));

        $response->assertStatus(200);
        $response->assertSee('id="zona_select" class="form-select rounded-3 bg-light"', false);
        $response->assertDontSee('id="zona_select" class="form-select rounded-3 bg-light" disabled', false);
    }

    public function test_store()
    {
        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => '',
            'curp' => 'JUPE890101HSL00000',
            'upp' => 'UPP-001',
            'domicilio' => '',
            'municipio' => '',
            'localidad' => '',
            'estado' => '',
            'telefono' => '',
            'email' => '',
        ]);

        if ($response->isRedirection() && ! $response->isRedirect(route('productores.index'))) {
            dump('Redirected to: '.$response->headers->get('Location'));
            if (session('error')) {
                dump('Session error: '.session('error'));
            }
        }
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', ['nombre' => 'Juan']);
    }

    public function test_edit()
    {
        $productor = Productor::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('productores.edit', $productor));

        $response->assertStatus(200);
    }

    public function test_update()
    {
        $productor = Productor::factory()->create();

        $response = $this->actingAs($this->admin)->patch(route('productores.update', $productor), [
            'nombre' => 'Carlos',
            'apellido_paterno' => 'López',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('productores.index'));
        $this->assertDatabaseHas('productores', ['id' => $productor->id, 'nombre' => 'Carlos']);
    }

    public function test_show()
    {
        $productor = Productor::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('productores.show', $productor));

        $response->assertStatus(200);
    }

    public function test_destroy()
    {
        $productor = Productor::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('productores.destroy', $productor));

        $response->assertRedirect(route('productores.index'));
        $this->assertDatabaseMissing('productores', ['id' => $productor->id]);
    }

    // --- storeAjax ---

    public function test_admin_store_ajax_crea_productor()
    {
        $response = $this->actingAs($this->admin)->post('/productores/ajax', [
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'López',
            'curp' => 'JUPE890101HSL00100',
            'upp' => 'UPP-AJAX-001',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('nombre', 'Juan');
        $this->assertDatabaseHas('productores', ['curp' => 'JUPE890101HSL00100']);
    }

    public function test_medico_store_ajax_crea_productor_autoasignado()
    {
        $response = $this->actingAs($this->medico)->post('/productores/ajax', [
            'nombre' => 'Medico',
            'apellido_paterno' => 'Ajax',
            'curp' => 'MEDX890101HSL00100',
            'upp' => 'UPP-AJAX-002',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'curp' => 'MEDX890101HSL00100',
            'medico_id' => $this->medico->id,
        ]);
    }

    public function test_store_ajax_valida_campos_requeridos()
    {
        $response = $this->actingAs($this->admin)->post('/productores/ajax', [
            'apellido_paterno' => 'Solo Apellido',
        ]);

        $response->assertSessionHasErrors(['nombre']);
    }

    public function test_store_ajax_admin_asigna_clave()
    {
        $response = $this->actingAs($this->admin)->post('/productores/ajax', [
            'nombre' => 'Admin',
            'apellido_paterno' => 'Key',
            'curp' => 'KEYX890101HSL00100',
            'upp' => 'UPP-AJAX-003',
            'clave' => 'AD-99999',
            'zona' => 'A',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'curp' => 'KEYX890101HSL00100',
            'clave' => 'AD-99999',
            'zona' => 'A',
        ]);
    }

    public function test_store_ajax_medico_no_asigna_clave()
    {
        $response = $this->actingAs($this->medico)->post('/productores/ajax', [
            'nombre' => 'Medico',
            'apellido_paterno' => 'NoKey',
            'curp' => 'NOKX890101HSL00100',
            'upp' => 'UPP-AJAX-004',
            'clave' => 'AD-88888',
            'zona' => 'A',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'curp' => 'NOKX890101HSL00100',
            'clave' => null,
            'zona' => null,
        ]);
    }

    public function test_store_ajax_curp_unico()
    {
        Productor::factory()->create(['curp' => 'DUPX890101HSL00100']);

        $response = $this->actingAs($this->admin)->post('/productores/ajax', [
            'nombre' => 'Duplicado',
            'apellido_paterno' => 'CURP',
            'curp' => 'DUPX890101HSL00100',
            'upp' => 'UPP-AJAX-005',
        ]);

        $response->assertSessionHasErrors(['curp']);
    }

    public function test_store_with_valid_activity_type_non_seguimiento()
    {
        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Test',
            'apellido_paterno' => 'Activity',
            'tipo_actividad' => 'Barrido',
            'sub_tipo_actividad' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Test',
            'tipo_actividad' => 'Barrido',
            'sub_tipo_actividad' => null,
        ]);
    }

    public function test_store_fails_when_seguimiento_but_no_sub_type()
    {
        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Test',
            'apellido_paterno' => 'Activity',
            'tipo_actividad' => 'Seguimiento',
            'sub_tipo_actividad' => '',
        ]);

        $response->assertSessionHasErrors(['sub_tipo_actividad']);
    }

    public function test_store_succeeds_when_seguimiento_with_valid_sub_type()
    {
        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Test',
            'apellido_paterno' => 'Activity',
            'tipo_actividad' => 'Seguimiento',
            'sub_tipo_actividad' => 'Hatos Relacionados y Expuestos',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Test',
            'tipo_actividad' => 'Seguimiento',
            'sub_tipo_actividad' => 'Hatos Relacionados y Expuestos',
        ]);
    }

    public function test_update_clears_sub_type_when_switching_from_seguimiento()
    {
        $productor = Productor::factory()->create([
            'tipo_actividad' => 'Seguimiento',
            'sub_tipo_actividad' => 'Cuarentena Definitiva',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('productores.update', $productor), [
            'nombre' => 'Carlos',
            'apellido_paterno' => 'López',
            'tipo_actividad' => 'Barrido',
            'sub_tipo_actividad' => 'Cuarentena Definitiva',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'id' => $productor->id,
            'tipo_actividad' => 'Barrido',
            'sub_tipo_actividad' => null,
        ]);
    }

    public function test_autogenerates_clave_for_barrido_federal_zone_a()
    {
        $productor = Productor::create([
            'nombre' => 'Barrido Fed',
            'apellido_paterno' => 'Test',
            'tipo_actividad' => 'Barrido',
            'zona' => 'A',
        ]);

        $this->assertNotNull($productor->clave);
        $this->assertStringStartsWith('BAF-', $productor->clave);
    }

    public function test_autogenerates_clave_for_barrido_estatal_zone_b()
    {
        $productor = Productor::create([
            'nombre' => 'Barrido Est',
            'apellido_paterno' => 'Test',
            'tipo_actividad' => 'Barrido',
            'zona' => 'B',
        ]);

        $this->assertNotNull($productor->clave);
        $this->assertStringStartsWith('BAE-', $productor->clave);
    }

    public function test_autogenerates_clave_for_buffer_escasa_prevalencia_zone_a()
    {
        $productor = Productor::create([
            'nombre' => 'Buffer Esc',
            'apellido_paterno' => 'Test',
            'tipo_actividad' => 'Buffer',
            'zona' => 'A',
        ]);

        $this->assertNotNull($productor->clave);
        $this->assertStringStartsWith('BFE-', $productor->clave);
    }

    public function test_autogenerates_clave_for_buffer_control_zone_b()
    {
        $productor = Productor::create([
            'nombre' => 'Buffer Ctrl',
            'apellido_paterno' => 'Test',
            'tipo_actividad' => 'Buffer',
            'zona' => 'B',
        ]);

        $this->assertNotNull($productor->clave);
        $this->assertStringStartsWith('BFC-', $productor->clave);
    }

    public function test_autogenerates_clave_for_seguimiento()
    {
        $productor = Productor::create([
            'nombre' => 'Seguimiento',
            'apellido_paterno' => 'Test',
            'tipo_actividad' => 'Seguimiento',
            'sub_tipo_actividad' => 'Cuarentena Precautoria',
            'zona' => 'B',
        ]);

        $this->assertNotNull($productor->clave);
        $this->assertStringStartsWith('BP-', $productor->clave);
    }

    public function test_autogenerates_sequential_clave()
    {
        $p1 = Productor::create(['nombre' => 'P1', 'apellido_paterno' => 'T1', 'tipo_actividad' => 'Barrido']);
        $p2 = Productor::create(['nombre' => 'P2', 'apellido_paterno' => 'T2', 'tipo_actividad' => 'Barrido']);

        $this->assertNotEquals($p1->clave, $p2->clave);
        $this->assertStringStartsWith('BAE-', $p1->clave);
        $this->assertStringStartsWith('BAE-', $p2->clave);
    }

    public function test_preview_clave_web_admin()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('productores.preview-clave').'?tipo_actividad=Buffer');

        $response->assertOk();
        $this->assertStringStartsWith('BFC-', $response->json('clave'));
    }

    public function test_preview_clave_web_seguimiento_infiere_zona_default_b()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('productores.preview-clave').'?tipo_actividad=Seguimiento&sub_tipo_actividad=Cuarentena%20Precautoria');

        $response->assertOk();
        $this->assertStringStartsWith('BP-', $response->json('clave'));
        $this->assertEquals('B', $response->json('zona'));
    }

    public function test_preview_clave_web_bloqueada_para_medico()
    {
        $response = $this->actingAs($this->medico)
            ->get(route('productores.preview-clave').'?tipo_actividad=Barrido');

        $response->assertForbidden();
    }

    public function test_preview_clave_api_movil()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/productores/preview-clave?tipo_actividad=Barrido');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertStringStartsWith('BAE-', $response->json('clave'));
    }

    public function test_preview_clave_api_movil_usa_zona_del_medico()
    {
        $this->medico->update(['zona' => 'A']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/productores/preview-clave?tipo_actividad=Seguimiento&sub_tipo_actividad=Cuarentena%20Definitiva&medico_id='.$this->medico->id);

        $response->assertOk();
        $this->assertStringStartsWith('AD-', $response->json('clave'));
        $this->assertEquals('A', $response->json('zona'));
    }

    public function test_preview_clave_no_guarda_registros()
    {
        $this->actingAs($this->admin)
            ->get(route('productores.preview-clave').'?tipo_actividad=Buffer');

        $this->assertDatabaseCount('productores', 0);
    }

    public function test_siguiente_clave_estatica_no_crea_registros()
    {
        $generated = Productor::siguienteClave('Barrido');

        $this->assertStringStartsWith('BAE-', $generated['clave']);
        $this->assertEquals('B', $generated['zona']);
        $this->assertDatabaseCount('productores', 0);
    }

    public function test_store_multiple_producers_with_predios()
    {
        $response = $this->actingAs($this->admin)->post(route('productores.store-multiple'), [
            'productores' => [
                [
                    'nombre' => 'Multi1',
                    'apellido_paterno' => 'P1',
                    'curp' => 'MULT111111HSL00001',
                    'tipo_actividad' => 'Barrido',
                    'registrar_predio' => '1',
                    'nombre_rancho' => 'Rancho Multi 1',
                    'clave_unidad_produccion' => 'UPPMULT1',
                    'predio_municipio' => 'M1',
                    'predio_localidad' => 'L1',
                ],
                [
                    'nombre' => 'Multi2',
                    'apellido_paterno' => 'P2',
                    'curp' => 'MULT222222HSL00002',
                    'tipo_actividad' => 'Buffer',
                    'registrar_predio' => '0',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('productores.index'));

        $this->assertDatabaseHas('productores', ['nombre' => 'Multi1']);
        $this->assertDatabaseHas('productores', ['nombre' => 'Multi2']);
        $this->assertDatabaseHas('predios', ['nombre_rancho' => 'Rancho Multi 1']);
    }

    // --- Tests para buscar endpoint ---

    public function test_buscar_endpoint_returns_productor_con_predio()
    {
        $productor = Productor::factory()->create(['nombre' => 'Juan', 'apellido_paterno' => 'Buscar']);
        Predio::factory()->create([
            'productor_id' => $productor->id,
            'nombre_rancho' => 'Rancho Test',
            'clave_unidad_produccion' => 'CUP-TEST-001',
            'latitud' => '22.123456',
            'longitud' => '-105.123456',
            'domicilio' => 'Domicilio Predio',
            'municipio' => 'Municipio Predio',
            'localidad' => 'Localidad Predio',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('productores.buscar', ['q' => 'Buscar']));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonCount(1);
        $response->assertJsonIsArray();

        $data = $response->json()[0];
        $this->assertEquals('Juan', $data['nombre']);
        $this->assertEquals('Rancho Test', $data['_predio_nombre_rancho']);
        $this->assertEquals('CUP-TEST-001', $data['_predio_clave_unidad_produccion']);
        $this->assertEquals(22.123456, $data['_predio_latitud']);
        $this->assertEquals(-105.123456, $data['_predio_longitud']);
        $this->assertEquals('Domicilio Predio', $data['_predio_domicilio']);
        $this->assertEquals('Municipio Predio', $data['_predio_municipio']);
        $this->assertEquals('Localidad Predio', $data['_predio_localidad']);
    }

    public function test_buscar_endpoint_requiere_minimo_1_caracter()
    {
        Productor::factory()->create(['nombre' => 'Juan']);

        $response = $this->actingAs($this->admin)->getJson(route('productores.buscar', ['q' => '']));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_buscar_endpoint_sin_resultados()
    {
        $response = $this->actingAs($this->admin)->getJson(route('productores.buscar', ['q' => 'zzzzzz']));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_store_no_copia_campos_personales()
    {
        Productor::factory()->create([
            'nombre' => 'Fuente',
            'apellido_paterno' => 'Original',
            'apellido_materno' => 'Copia',
            'curp' => 'FUEN890101HSL00000',
            'upp' => 'UPP-FUENTE',
            'telefono' => '3111234567',
            'email' => 'fuente@test.com',
        ]);

        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Nuevo',
            'apellido_paterno' => 'Productor',
            'apellido_materno' => '',
            'curp' => 'NUEV890101HSL00001',
            'upp' => 'UPP-NUEVO',
            'domicilio' => 'Domicilio Compartido',
            'municipio' => 'Municipio Compartido',
            'localidad' => 'Localidad Compartida',
            'estado' => 'Nayarit',
            'telefono' => '',
            'email' => '',
            'tipo_actividad' => 'Barrido',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', ['nombre' => 'Nuevo']);
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Nuevo',
            'apellido_paterno' => 'Productor',
            'curp' => 'NUEV890101HSL00001',
            'upp' => 'UPP-NUEVO',
            'telefono' => null,
            'email' => null,
            'domicilio' => 'Domicilio Compartido',
            'municipio' => 'Municipio Compartido',
            'localidad' => 'Localidad Compartida',
            'estado' => 'Nayarit',
        ]);
    }

    public function test_store_copia_campos_compartidos()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        Productor::factory()->create([
            'domicilio' => 'Domicilio Fuente',
            'municipio' => 'Municipio Fuente',
            'localidad' => 'Localidad Fuente',
            'estado' => 'Nayarit',
            'tipo_actividad' => 'Barrido',
            'sub_tipo_actividad' => null,
            'medico_id' => $medico->id,
            'clave' => 'BF-123456',
            'zona' => 'B',
        ]);

        $response = $this->actingAs($this->admin)->post(route('productores.store'), [
            'nombre' => 'Compartido',
            'apellido_paterno' => 'Test',
            'curp' => 'COMP890101HSL00002',
            'domicilio' => 'Domicilio Fuente',
            'municipio' => 'Municipio Fuente',
            'localidad' => 'Localidad Fuente',
            'estado' => 'Nayarit',
            'tipo_actividad' => 'Barrido',
            'medico_id' => $medico->id,
            'clave' => 'BF-123456',
            'zona' => 'B',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'nombre' => 'Compartido',
            'domicilio' => 'Domicilio Fuente',
            'municipio' => 'Municipio Fuente',
            'localidad' => 'Localidad Fuente',
            'estado' => 'Nayarit',
            'tipo_actividad' => 'Barrido',
            'medico_id' => $medico->id,
            'clave' => 'BF-123456',
            'zona' => 'B',
        ]);
    }

    public function test_buscar_endpoint_medico_puede_buscar()
    {
        Productor::factory()->create([
            'nombre' => 'Medico',
            'apellido_paterno' => 'Busqueda',
            'medico_id' => $this->medico->id,
        ]);

        $response = $this->actingAs($this->medico)->getJson(route('productores.buscar', ['q' => 'Busqueda']));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nombre' => 'Medico']);
    }

    public function test_admin_puede_desvincular_productor_de_hato()
    {
        $productor = Productor::factory()->create(['clave' => 'BA-0001']);

        $response = $this->actingAs($this->admin)->post(route('productores.desvincular-hato', $productor));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNull($productor->fresh()->clave);
    }

    public function test_admin_no_puede_sobrescribir_hato_de_otro_productor()
    {
        $productor = Productor::factory()->create(['clave' => 'BA-0001']);
        $deOtroHato = Productor::factory()->create(['clave' => 'BF-9999']);

        $response = $this->actingAs($this->admin)->post(route('productores.vincular-existente', $productor), [
            'productor_id' => $deOtroHato->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('BF-9999', $deOtroHato->fresh()->clave);
    }

    public function test_medico_no_autorizado_no_puede_desvincular_productor_de_hato()
    {
        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');

        $productor = Productor::factory()->create([
            'clave' => 'BA-0001',
            'medico_id' => $otroMedico->id,
        ]);

        $response = $this->actingAs($this->medico)->post(route('productores.desvincular-hato', $productor));

        $response->assertStatus(403);
        $this->assertEquals('BA-0001', $productor->fresh()->clave);
    }
}
