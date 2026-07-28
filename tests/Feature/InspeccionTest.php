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
        $productorBD = Productor::factory()->create(['clave' => 'BD-123456', 'zona' => 'B']);
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
        $productorBD = Productor::factory()->create(['clave' => 'BD-123456', 'zona' => 'B']);
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

    public function test_update_con_productor_ad_usa_umbral_2_meses()
    {
        $productorAD = Productor::factory()->create(['clave' => 'AD-987654', 'zona' => 'A']);
        $predioAD = Predio::factory()->create(['productor_id' => $productorAD->id]);

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predioAD->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('inspecciones.update', $inspeccion), [
                'predio_id' => $predioAD->id,
                'fecha' => now()->format('Y-m-d'),
                'estado' => 'borrador',
                'animales' => [
                    [
                        'identificador' => 'MX-ARETE-AD-UPDATE',
                        'edad_meses' => 1,
                        'sexo' => 'H',
                        'raza' => 'Cebú',
                        'resultado' => 'Pendiente',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-ARETE-AD-UPDATE');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('No Aplica', $detalle->resultado_prueba);
        $this->assertEquals('Menor a 2 meses', $detalle->motivo_no_aplica);
    }

    public function test_update_con_productor_sin_clave_usa_umbral_6_meses()
    {
        $productor = Productor::factory()->create(['clave' => null]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->admin->id,
            'estado' => 'borrador',
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('inspecciones.update', $inspeccion), [
                'predio_id' => $predio->id,
                'fecha' => now()->format('Y-m-d'),
                'estado' => 'borrador',
                'animales' => [
                    [
                        'identificador' => 'MX-ARETE-SIN-CLAVE',
                        'edad_meses' => 3,
                        'sexo' => 'M',
                        'raza' => 'Brangus',
                        'resultado' => 'Pendiente',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-ARETE-SIN-CLAVE');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('No Aplica', $detalle->resultado_prueba);
        $this->assertEquals('Menor a 6 meses', $detalle->motivo_no_aplica);
    }

    public function test_store_con_edad_cero_no_asigna_motivo()
    {
        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-EDAD-CERO',
                    'edad_meses' => 0,
                    'sexo' => 'H',
                    'resultado' => 'Negativo',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-EDAD-CERO');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('Negativo', $detalle->resultado_prueba);
        $this->assertNull($detalle->motivo_no_aplica);
    }

    public function test_store_con_edad_exactamente_6_no_asigna_motivo()
    {
        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-BOUNDARY-6',
                    'edad_meses' => 6,
                    'sexo' => 'M',
                    'resultado' => 'Negativo',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-BOUNDARY-6');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('Negativo', $detalle->resultado_prueba);
        $this->assertNull($detalle->motivo_no_aplica);
    }

    public function test_store_con_productor_bd_edad_exactamente_2_no_asigna_motivo()
    {
        $productorBD = Productor::factory()->create(['clave' => 'BD-123456', 'zona' => 'B']);
        $predioBD = Predio::factory()->create(['productor_id' => $productorBD->id]);

        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $predioBD->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-BOUNDARY-BD-2',
                    'edad_meses' => 2,
                    'sexo' => 'H',
                    'resultado' => 'Negativo',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-BOUNDARY-BD-2');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('Negativo', $detalle->resultado_prueba);
        $this->assertNull($detalle->motivo_no_aplica);
    }

    public function test_store_con_productor_clave_vacia_usa_6_meses()
    {
        $productor = Productor::factory()->create(['clave' => '']);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'animales' => [
                [
                    'identificador' => 'MX-CLAVE-VACIA',
                    'edad_meses' => 3,
                    'sexo' => 'H',
                    'resultado' => 'Pendiente',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $detalle = DetalleInspeccion::whereHas('animal', function ($q) {
            $q->where('numero_arete_siniiga', 'MX-CLAVE-VACIA');
        })->first();

        $this->assertNotNull($detalle);
        $this->assertEquals('No Aplica', $detalle->resultado_prueba);
        $this->assertEquals('Menor a 6 meses', $detalle->motivo_no_aplica);
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

    public function test_create_with_predio_id_as_admin_resolves_productor_id()
    {
        $response = $this->actingAs($this->admin)->get(route('inspecciones.create', ['predio_id' => $this->predio->id]));

        $response->assertStatus(200);
        $response->assertViewHas('selected_productor_id', $this->predio->productor_id);
        $response->assertViewHas('selected_predio_id', $this->predio->id);
    }

    public function test_create_with_predio_id_as_medico_resolves_productor_id_if_assigned()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        // Assign productor to this medico
        $productor = Productor::factory()->create(['medico_id' => $medico->id]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->actingAs($medico)->get(route('inspecciones.create', ['predio_id' => $predio->id]));

        $response->assertStatus(200);
        $response->assertViewHas('selected_productor_id', $productor->id);
        $response->assertViewHas('selected_predio_id', $predio->id);
    }

    public function test_create_with_predio_id_as_medico_does_not_resolve_productor_id_if_not_assigned()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        // Assign productor to a different user
        $otroMedico = User::factory()->create();
        $productor = Productor::factory()->create(['medico_id' => $otroMedico->id]);
        $predio = Predio::factory()->create(['productor_id' => $productor->id]);

        $response = $this->actingAs($medico)->get(route('inspecciones.create', ['predio_id' => $predio->id]));

        $response->assertStatus(200);
        $response->assertViewHas('selected_productor_id', null);
        $response->assertViewHas('selected_predio_id', $predio->id);
    }

    public function test_store_multi_productor_splits_inspections()
    {
        $extraProductor = Productor::factory()->create();
        $extraPredio = Predio::factory()->create(['productor_id' => $extraProductor->id]);

        $response = $this->actingAs($this->admin)->post(route('inspecciones.store'), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'productores_extra' => [$extraProductor->id],
            'animales' => [
                [
                    'identificador' => 'ARETE-MAIN-001',
                    'edad_meses' => 12,
                    'sexo' => 'H',
                    'raza' => 'Jersey',
                    'resultado' => 'Negativo',
                    'productor_id' => $this->predio->productor_id,
                ],
                [
                    'identificador' => 'ARETE-EXTRA-002',
                    'edad_meses' => 24,
                    'sexo' => 'M',
                    'raza' => 'Holando',
                    'resultado' => 'Negativo',
                    'productor_id' => $extraProductor->id,
                ]
            ],
            'folio' => 'F-1000',
        ]);

        $response->assertSessionHasNoErrors();

        // 2 inspections should be created
        $inspecciones = Inspeccion::all();
        $this->assertCount(2, $inspecciones);

        $mainIns = $inspecciones->where('predio_id', $this->predio->id)->first();
        $extraIns = $inspecciones->where('predio_id', $extraPredio->id)->first();

        $this->assertNotNull($mainIns);
        $this->assertNotNull($extraIns);

        // They must share the same grupo_id
        $this->assertEquals($mainIns->grupo_id, $extraIns->grupo_id);
        $this->assertNotNull($mainIns->grupo_id);

        // Main has Jersey animal, Extra has Holando animal
        $this->assertCount(1, $mainIns->detalles);
        $this->assertEquals('ARETE-MAIN-001', $mainIns->detalles->first()->animal->numero_arete_siniiga);

        $this->assertCount(1, $extraIns->detalles);
        $this->assertEquals('ARETE-EXTRA-002', $extraIns->detalles->first()->animal->numero_arete_siniiga);

        // Folios check
        $this->assertEquals('F-1000', $mainIns->folio);
        $this->assertStringContainsString('F-1000-', $extraIns->folio);
    }

    public function test_update_multi_productor_syncs_and_deletes()
    {
        $extraProductor = Productor::factory()->create();
        $extraPredio = Predio::factory()->create(['productor_id' => $extraProductor->id]);

        $grupoId = 'GRP-TEST-123';
        $mainIns = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'grupo_id' => $grupoId,
            'estado' => 'borrador'
        ]);
        $extraIns = Inspeccion::factory()->create([
            'predio_id' => $extraPredio->id,
            'veterinario_id' => $this->admin->id,
            'grupo_id' => $grupoId,
            'estado' => 'borrador'
        ]);

        // Submit update removing the extra producer completely
        $response = $this->actingAs($this->admin)->patch(route('inspecciones.update', $mainIns), [
            'predio_id' => $this->predio->id,
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            'productores_extra' => [], // No extras
            'animales' => [
                [
                    'identificador' => 'ARETE-MAIN-ONLY',
                    'edad_meses' => 12,
                    'sexo' => 'H',
                    'raza' => 'Jersey',
                    'resultado' => 'Negativo',
                    'productor_id' => $this->predio->productor_id,
                ]
            ],
            'folio' => 'F-2000',
        ]);

        $response->assertSessionHasNoErrors();

        // Extra inspection should be deleted
        $this->assertDatabaseMissing('inspecciones', ['id' => $extraIns->id]);
        $this->assertDatabaseHas('inspecciones', ['id' => $mainIns->id, 'folio' => 'F-2000']);
    }
}
