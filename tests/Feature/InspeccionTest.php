<?php

namespace Tests\Feature;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InspeccionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    public function test_index_requiere_autenticacion()
    {
        $response = $this->get(route('inspecciones.index'));
        $response->assertRedirect('/login');
    }

    public function test_index()
    {
        Inspeccion::factory()->count(3)->create(['predio_id' => $this->predio->id, 'veterinario_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->get(route('inspecciones.index'));

        $response->assertStatus(200);
    }

    public function test_create()
    {
        $response = $this->actingAs($this->admin)->get(route('inspecciones.create'));

        $response->assertStatus(200);
    }

    public function test_store_draft()
    {
        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
        ]);

        if ($response->isRedirection() && ! $response->isRedirect(route('inspecciones.index'))) {
            dump('Redirected to: '.$response->headers->get('Location'));
            if (session('error')) {
                dump('Session error: '.session('error'));
            }
        }
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inspecciones', ['predio_id' => $this->predio->id]);
    }

    public function test_show()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inspecciones.show', $inspeccion));

        $response->assertStatus(200);
    }

    public function test_edit()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($this->admin)->get(route('inspecciones.edit', $inspeccion));

        $response->assertStatus(200);
    }

    public function test_update_draft()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('inspecciones.update', $inspeccion), [
            'observaciones' => 'Actualizado',
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('inspecciones.index'));
        $this->assertDatabaseHas('inspecciones', ['id' => $inspeccion->id, 'observaciones' => 'Actualizado']);
    }

    public function test_destroy_borrador()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('inspecciones.destroy', $inspeccion));

        $response->assertRedirect(route('inspecciones.index'));
        $this->assertDatabaseMissing('inspecciones', ['id' => $inspeccion->id]);
    }
}
