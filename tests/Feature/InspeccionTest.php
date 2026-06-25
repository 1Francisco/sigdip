<?php

namespace Tests\Feature;

use App\Models\DetalleInspeccion;
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

    public function test_store_con_animal_menor_6_meses_setea_motivo()
    {
        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-ARETE-MOTIVO-001',
                    'edad_meses' => 4,
                    'sexo' => 'H',
                    'raza' => 'Cebú',
                    'resultado' => 'Pendiente',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-ARETE-MOTIVO-001');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('No Aplica', $detalle->resultado_prueba);
        $this->assertEquals('Menor a 6 meses', $detalle->motivo_no_aplica);
    }

    public function test_store_con_animal_mayor_6_meses_no_setea_motivo()
    {
        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-ARETE-SIN-MOTIVO-001',
                    'edad_meses' => 12,
                    'sexo' => 'M',
                    'raza' => 'Suizo',
                    'resultado' => 'Negativo',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-ARETE-SIN-MOTIVO-001');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('Negativo', $detalle->resultado_prueba);
        $this->assertNull($detalle->motivo_no_aplica);
    }

    public function test_update_con_animal_menor_6_meses_actualiza_motivo()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('inspecciones.update', $inspeccion), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-ARETE-UPDATE-MOTIVO',
                    'edad_meses' => 3,
                    'sexo' => 'H',
                    'raza' => 'Angus',
                    'resultado' => 'Pendiente',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-ARETE-UPDATE-MOTIVO');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('No Aplica', $detalle->resultado_prueba);
        $this->assertEquals('Menor a 6 meses', $detalle->motivo_no_aplica);
    }

    public function test_store_con_productor_bd_usa_umbral_2_meses()
    {
        $productorBD = Productor::factory()->create(['clave_cuarentena' => 'BD-123456', 'zona' => 'B']);
        $predioBD = Predio::factory()->create(['productor_id' => $productorBD->id]);

        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $predioBD->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-ARETE-BD-001',
                    'edad_meses' => 1,
                    'sexo' => 'H',
                    'raza' => 'Cebú',
                    'resultado' => 'Pendiente',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-ARETE-BD-001');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('No Aplica', $detalle->resultado_prueba);
        $this->assertEquals('Menor a 2 meses', $detalle->motivo_no_aplica);
    }

    public function test_update_con_productor_bd_usa_umbral_2_meses()
    {
        $productorBD = Productor::factory()->create(['clave_cuarentena' => 'BD-123456', 'zona' => 'B']);
        $predioBD = Predio::factory()->create(['productor_id' => $productorBD->id]);

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predioBD->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('inspecciones.update', $inspeccion), [
                'predio_id' => $predioBD->id,
                'fecha' => now()->format('Y-m-d'),
                'estado' => 'borrador',
                'animales' => [
                    [
                        'identificador' => 'MX-ARETE-BD-UPDATE',
                        'edad_meses' => 1,
                        'sexo' => 'H',
                        'raza' => 'Cebú',
                        'resultado' => 'Pendiente',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-ARETE-BD-UPDATE');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('No Aplica', $detalle->resultado_prueba);
        $this->assertEquals('Menor a 2 meses', $detalle->motivo_no_aplica);
    }

    public function test_destroy_completada_rechazada_para_medico()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $medico->id,
            'estado' => 'completada',
        ]);

        $response = $this->actingAs($medico)
            ->delete(route('inspecciones.destroy', $inspeccion));

        $response->assertStatus(403);
        $this->assertDatabaseHas('inspecciones', ['id' => $inspeccion->id]);
    }

    public function test_admin_puede_eliminar_completada()
    {
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'completada',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('inspecciones.destroy', $inspeccion));

        $response->assertRedirect(route('inspecciones.index'));
        $this->assertDatabaseMissing('inspecciones', ['id' => $inspeccion->id]);
    }
}
