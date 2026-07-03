<?php

namespace Tests\Feature;

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

    public function test_store_ajax_admin_asigna_clave_cuarentena()
    {
        $response = $this->actingAs($this->admin)->post('/productores/ajax', [
            'nombre' => 'Admin',
            'apellido_paterno' => 'Key',
            'curp' => 'KEYX890101HSL00100',
            'upp' => 'UPP-AJAX-003',
            'clave_cuarentena' => 'AD-99999',
            'zona' => 'A',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'curp' => 'KEYX890101HSL00100',
            'clave_cuarentena' => 'AD-99999',
            'zona' => 'A',
        ]);
    }

    public function test_store_ajax_medico_no_asigna_clave_cuarentena()
    {
        $response = $this->actingAs($this->medico)->post('/productores/ajax', [
            'nombre' => 'Medico',
            'apellido_paterno' => 'NoKey',
            'curp' => 'NOKX890101HSL00100',
            'upp' => 'UPP-AJAX-004',
            'clave_cuarentena' => 'AD-88888',
            'zona' => 'A',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productores', [
            'curp' => 'NOKX890101HSL00100',
            'clave_cuarentena' => null,
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
}
