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

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
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
}
