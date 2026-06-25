<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAssignmentTest extends TestCase
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

    public function test_admin_puede_ver_pagina_asignacion()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('usuarios.asignar-productores', $this->medico1));

        $response->assertStatus(200);
        $response->assertSee($this->medico1->name);
    }

    public function test_medico_no_puede_acceder_asignacion()
    {
        $response = $this->actingAs($this->medico2)
            ->get(route('usuarios.asignar-productores', $this->medico1));

        $response->assertStatus(403);
    }

    public function test_admin_asigna_productores_a_medico()
    {
        $prod1 = Productor::factory()->create(['nombre' => 'Prod Asignado']);
        $prod2 = Productor::factory()->create(['nombre' => 'Prod No Asignado']);

        $response = $this->actingAs($this->admin)
            ->post(route('usuarios.guardar-asignacion', $this->medico1), [
                'productor_ids' => [$prod1->id],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('usuarios.show', $this->medico1));

        $this->assertDatabaseHas('productores', [
            'id' => $prod1->id,
            'medico_id' => $this->medico1->id,
        ]);
        $this->assertDatabaseHas('productores', [
            'id' => $prod2->id,
            'medico_id' => null,
        ]);
    }

    public function test_admin_desasigna_todos_los_productores()
    {
        $prod1 = Productor::factory()->create(['medico_id' => $this->medico1->id]);
        $prod2 = Productor::factory()->create(['medico_id' => $this->medico1->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('usuarios.guardar-asignacion', $this->medico1), [
                'productor_ids' => [],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'id' => $prod1->id,
            'medico_id' => null,
        ]);
        $this->assertDatabaseHas('productores', [
            'id' => $prod2->id,
            'medico_id' => null,
        ]);
    }

    public function test_admin_reasigna_productores_entre_medicos()
    {
        $prod = Productor::factory()->create(['medico_id' => $this->medico1->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('usuarios.guardar-asignacion', $this->medico2), [
                'productor_ids' => [$prod->id],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productores', [
            'id' => $prod->id,
            'medico_id' => $this->medico2->id,
        ]);
    }

    public function test_admin_puede_desasignar_productor_individual()
    {
        $prod = Productor::factory()->create(['medico_id' => $this->medico1->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('usuarios.desasignar-productor', [$this->medico1, $prod]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('usuarios.show', $this->medico1));
        $this->assertDatabaseHas('productores', [
            'id' => $prod->id,
            'medico_id' => null,
        ]);
    }

    public function test_desasignar_productor_ajeno_devuelve_404()
    {
        $prod = Productor::factory()->create(['medico_id' => $this->medico2->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('usuarios.desasignar-productor', [$this->medico1, $prod]));

        $response->assertStatus(404);
    }

    public function test_show_muestra_productores_asignados()
    {
        $prod = Productor::factory()->create([
            'medico_id' => $this->medico1->id,
            'nombre' => 'Prod Visible',
        ]);
        Predio::factory()->count(2)->create(['productor_id' => $prod->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('usuarios.show', $this->medico1));

        $response->assertStatus(200);
        $response->assertSee('Prod Visible');
        $response->assertSee('2');
    }

    public function test_index_muestra_conteo_productores()
    {
        Productor::factory()->count(3)->create(['medico_id' => $this->medico1->id]);
        Productor::factory()->count(1)->create(['medico_id' => $this->medico2->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('usuarios.index'));

        $response->assertStatus(200);
        $response->assertSee('3');
        $response->assertSee('1');
    }

    public function test_asignar_view_muestra_checkboxes_y_secciones()
    {
        Productor::factory()->count(3)->create([
            'medico_id' => $this->medico1->id,
        ]);
        Productor::factory()->count(2)->create(['medico_id' => null]);

        $response = $this->actingAs($this->admin)
            ->get(route('usuarios.asignar-productores', $this->medico1));

        $response->assertStatus(200);
        $response->assertSee('Asignados Actualmente');
        $response->assertSee('Productores Disponibles');
        $response->assertSee('productor-checkbox');
        $response->assertSee('Guardar Asignaci'."\xC3\xB3".'n');
        $response->assertSee(route('usuarios.guardar-asignacion', $this->medico1));
    }

    public function test_asignar_view_muestra_empty_state_sin_asignados()
    {
        Productor::factory()->count(2)->create(['medico_id' => null]);

        $response = $this->actingAs($this->admin)
            ->get(route('usuarios.asignar-productores', $this->medico1));

        $response->assertStatus(200);
        $response->assertSee('No hay productores asignados a este m'."\xC3\xA9".'dico.');
    }

    public function test_asignar_view_muestra_empty_state_todos_asignados()
    {
        Productor::factory()->count(2)->create(['medico_id' => $this->medico1->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('usuarios.asignar-productores', $this->medico1));

        $response->assertStatus(200);
        $response->assertSee('Todos los productores est'."\xC3\xA1".'n asignados a alg'."\xC3\xBA".'n m'."\xC3\xA9".'dico.');
    }
}
