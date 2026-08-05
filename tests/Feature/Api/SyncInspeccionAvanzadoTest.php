<?php

namespace Tests\Feature\Api;

use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class SyncInspeccionAvanzadoTest extends TestCase
{
    use RefreshDatabase;

    private User $medico;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');
        Sanctum::actingAs($this->medico);
    }

    public function test_sync_guarda_campos_avanzados_del_dictamen()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-ADV-001',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'fecha_prueba_anterior' => '2026-05-01',
                    'dictamen_anterior_no' => 'DICT-ANTERIOR-1',
                    'exencion_no' => 'EX-2026-001',
                    'exencion_fecha' => '2026-05-15',
                    'hato_libre_no' => 'HL-2026-001',
                    'hato_libre_fecha' => '2026-05-20',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', [
            'folio' => 'SYNC-ADV-001',
            'dictamen_anterior_no' => 'DICT-ANTERIOR-1',
            'exencion_no' => 'EX-2026-001',
            'hato_libre_no' => 'HL-2026-001',
        ]);

        $inspeccion = Inspeccion::where('folio', 'SYNC-ADV-001')->first();
        $this->assertNotNull($inspeccion);
        $this->assertSame('2026-05-01', $inspeccion->fecha_prueba_anterior->format('Y-m-d'));
        $this->assertSame('2026-05-15', $inspeccion->exencion_fecha->format('Y-m-d'));
        $this->assertSame('2026-05-20', $inspeccion->hato_libre_fecha->format('Y-m-d'));
    }

    public function test_sync_actualiza_campos_avanzados_en_borrador_existente()
    {
        $predio = Predio::factory()->create();
        Inspeccion::factory()->create([
            'folio' => 'SYNC-ADV-002',
            'predio_id' => $predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'borrador',
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-ADV-002',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                    'exencion_no' => 'EX-2026-999',
                    'hato_libre_no' => 'HL-2026-999',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', [
            'folio' => 'SYNC-ADV-002',
            'exencion_no' => 'EX-2026-999',
            'hato_libre_no' => 'HL-2026-999',
        ]);
    }

    public function test_sync_rechaza_finalizar_antes_de_fecha_de_visita()
    {
        $predio = Predio::factory()->create();
        $visita = Visita::factory()->create([
            'veterinario_id' => $this->medico->id,
            'predio_id' => $predio->id,
            'fecha_programada' => now()->addDays(5)->format('Y-m-d'),
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-FECHA-001',
                    'predio_id' => $predio->id,
                    'visita_id' => $visita->id,
                    'fecha' => now()->format('Y-m-d'),
                    'fecha_inyeccion' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('errores.0.error', 'No se puede finalizar la inyección antes de la fecha programada de la visita ('.now()->addDays(5)->format('d/m/Y').').');
        $this->assertDatabaseMissing('inspecciones', ['folio' => 'SYNC-FECHA-001']);
        $this->assertDatabaseHas('visitas', [
            'id' => $visita->id,
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);
    }

    public function test_sync_rechaza_finalizar_antes_de_fecha_de_lectura()
    {
        $predio = Predio::factory()->create();
        $visita = Visita::factory()->create([
            'veterinario_id' => $this->medico->id,
            'predio_id' => $predio->id,
            'fecha_programada' => now()->subDays(5)->format('Y-m-d'),
            'estado' => 'completada',
            'inyeccion' => true,
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-FECHA-002',
                    'predio_id' => $predio->id,
                    'visita_id' => $visita->id,
                    'fecha' => now()->format('Y-m-d'),
                    'fecha_inyeccion' => now()->subDays(3)->format('Y-m-d'),
                    'fecha_lectura' => now()->addDays(5)->format('Y-m-d'),
                    'estado' => 'sincronizado',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('errores.0.error', 'No se puede finalizar el dictamen antes de la fecha programada de la lectura ('.now()->addDays(5)->format('d/m/Y').').');
        $this->assertDatabaseMissing('inspecciones', ['folio' => 'SYNC-FECHA-002']);
    }

    public function test_sync_permite_borrador_antes_de_fecha_de_visita()
    {
        $predio = Predio::factory()->create();
        $visita = Visita::factory()->create([
            'veterinario_id' => $this->medico->id,
            'predio_id' => $predio->id,
            'fecha_programada' => now()->addDays(5)->format('Y-m-d'),
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-BORRADOR-001',
                    'predio_id' => $predio->id,
                    'visita_id' => $visita->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'borrador',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', ['folio' => 'SYNC-BORRADOR-001']);
    }

    public function test_sync_calcula_fecha_lectura_mas_3_dias()
    {
        $predio = Predio::factory()->create();

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-LECTURA-001',
                    'predio_id' => $predio->id,
                    'fecha' => now()->format('Y-m-d'),
                    'fecha_inyeccion' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', [
            'folio' => 'SYNC-LECTURA-001',
        ]);

        $inspeccion = Inspeccion::where('folio', 'SYNC-LECTURA-001')->first();
        $this->assertNotNull($inspeccion);
        $this->assertSame(now()->addDays(3)->format('Y-m-d'), $inspeccion->fecha_lectura->format('Y-m-d'));
    }

    public function test_patch_actualiza_campos_avanzados_del_dictamen()
    {
        $predio = Predio::factory()->create();
        $inspeccion = Inspeccion::factory()->create([
            'predio_id' => $predio->id,
            'veterinario_id' => $this->medico->id,
            'estado' => 'borrador',
        ]);

        $response = $this->patchJson('/api/inspecciones/'.$inspeccion->id, [
            'exencion_no' => 'EX-PATCH-001',
            'exencion_fecha' => '2026-06-01',
            'hato_libre_no' => 'HL-PATCH-001',
            'hato_libre_fecha' => '2026-06-10',
            'dictamen_anterior_no' => 'DICT-PATCH-1',
            'fecha_prueba_anterior' => '2026-04-01',
            'motivo_prueba' => 'Cuarentenas Definitivas',
            'sementales' => 2,
            'becerros' => 3,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', [
            'id' => $inspeccion->id,
            'exencion_no' => 'EX-PATCH-001',
            'hato_libre_no' => 'HL-PATCH-001',
            'dictamen_anterior_no' => 'DICT-PATCH-1',
            'motivo_prueba' => 'Cuarentenas Definitivas',
            'sementales' => 2,
            'becerros' => 3,
        ]);

        $refreshed = $inspeccion->refresh();
        $this->assertSame('2026-06-01', $refreshed->exencion_fecha->format('Y-m-d'));
        $this->assertSame('2026-06-10', $refreshed->hato_libre_fecha->format('Y-m-d'));
        $this->assertSame('2026-04-01', $refreshed->fecha_prueba_anterior->format('Y-m-d'));
    }

    public function test_sync_completa_visita_cuando_finaliza_dictamen()
    {
        $predio = Predio::factory()->create();
        $visita = Visita::factory()->create([
            'veterinario_id' => $this->medico->id,
            'predio_id' => $predio->id,
            'fecha_programada' => now()->subDays(2)->format('Y-m-d'),
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-VISITA-001',
                    'predio_id' => $predio->id,
                    'visita_id' => $visita->id,
                    'fecha' => now()->format('Y-m-d'),
                    'fecha_inyeccion' => now()->subDays(3)->format('Y-m-d'),
                    'fecha_lectura' => now()->format('Y-m-d'),
                    'estado' => 'sincronizado',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', ['folio' => 'SYNC-VISITA-001']);
        $this->assertDatabaseHas('visitas', [
            'id' => $visita->id,
            'estado' => 'completada',
            'inyeccion' => true,
        ]);
    }

    public function test_sync_no_completa_visita_con_borrador()
    {
        $predio = Predio::factory()->create();
        $visita = Visita::factory()->create([
            'veterinario_id' => $this->medico->id,
            'predio_id' => $predio->id,
            'fecha_programada' => now()->subDays(2)->format('Y-m-d'),
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);

        $response = $this->postJson('/api/sync/inspecciones', [
            'inspecciones' => [
                [
                    'folio' => 'SYNC-VISITA-002',
                    'predio_id' => $predio->id,
                    'visita_id' => $visita->id,
                    'fecha' => now()->format('Y-m-d'),
                    'estado' => 'borrador',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inspecciones', ['folio' => 'SYNC-VISITA-002']);
        $this->assertDatabaseHas('visitas', [
            'id' => $visita->id,
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);
    }

    public function test_buscar_por_clave_scopea_por_medico()
    {
        $productorPropio = Productor::factory()->create([
            'clave' => 'BD-100001',
            'zona' => 'B',
            'medico_id' => $this->medico->id,
        ]);
        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');
        Productor::factory()->create([
            'clave' => 'BD-100002',
            'zona' => 'B',
            'medico_id' => $otroMedico->id,
        ]);

        $response = $this->getJson('/api/productores/buscar-por-clave?clave=BD');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $productorPropio->id]);
    }

    public function test_buscar_por_clave_admin_ve_todos()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        Sanctum::actingAs($admin);

        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');
        Productor::factory()->create([
            'clave' => 'BD-200001',
            'zona' => 'B',
            'medico_id' => $otroMedico->id,
        ]);
        Productor::factory()->create([
            'clave' => 'BD-200002',
            'zona' => 'B',
            'medico_id' => $this->medico->id,
        ]);

        $response = $this->getJson('/api/productores/buscar-por-clave?clave=BD');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_export_excel_api_medico_solo_recibe_sus_inspecciones()
    {
        $otroMedico = User::factory()->create();
        $otroMedico->assignRole('Medico_Campo');

        $predioPropio = Predio::factory()->create();
        $predioOtro = Predio::factory()->create();
        Inspeccion::factory()->create([
            'predio_id' => $predioPropio->id,
            'veterinario_id' => $this->medico->id,
            'folio' => 'SABANA-PROPIO-001',
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);
        Inspeccion::factory()->create([
            'predio_id' => $predioOtro->id,
            'veterinario_id' => $otroMedico->id,
            'folio' => 'SABANA-AJENO-001',
            'fecha' => now()->format('Y-m-d'),
            'estado' => 'sincronizado',
        ]);

        // El médico pasa médico_id del otro usuario: el servidor debe forzar el suyo propio
        $response = $this->getJson('/api/reportes/sabana-excel?medico_id='.$otroMedico->id);
        $response->assertStatus(200);

        $content = $response->baseResponse instanceof BinaryFileResponse
            ? file_get_contents($response->baseResponse->getFile()->getPathname())
            : $response->streamedContent();

        $tmpFile = tempnam(sys_get_temp_dir(), 'sabana_api_').'.xlsx';
        file_put_contents($tmpFile, $content);
        $spreadsheet = IOFactory::load($tmpFile);
        unlink($tmpFile);

        $sheet = $spreadsheet->getActiveSheet();
        $allCells = [];
        foreach ($sheet->toArray() as $row) {
            $allCells = array_merge($allCells, $row);
        }
        $contenido = implode('|', array_map('strval', $allCells));

        $this->assertStringContainsString('SABANA-PROPIO-001', $contenido);
        $this->assertStringNotContainsString('SABANA-AJENO-001', $contenido);

        $spreadsheet->disconnectWorksheets();
    }
}
