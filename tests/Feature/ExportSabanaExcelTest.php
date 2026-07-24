<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use App\Exports\InspeccionExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportSabanaExcelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $medico;

    private Predio $predio;

    private Inspeccion $inspeccion;

    private Productor $productor;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->medico = User::factory()->create();
        $this->medico->assignRole('Medico_Campo');

        $this->productor = Productor::factory()->create();
        $this->predio = Predio::factory()->create(['productor_id' => $this->productor->id]);

        $this->inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'veterinario_id' => $this->admin->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_prueba' => 'PPC',
        ]);
    }

    private function parseExcelFromExport(?string $zona = null, ?string $tipoActividad = null, ?string $medicoId = null): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $export = new InspeccionExport($zona, $tipoActividad, $medicoId);
        $content = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        $tmpFile = tempnam(sys_get_temp_dir(), 'sabana_test_').'.xlsx';
        file_put_contents($tmpFile, $content);
        $spreadsheet = IOFactory::load($tmpFile);
        unlink($tmpFile);

        return $spreadsheet;
    }

    public function test_admin_puede_descargar_excel()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.excel.download'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename=sabana_dictamenes_'.date('d-m-Y').'.xlsx');
    }

    public function test_medico_puede_descargar_excel()
    {
        $response = $this->actingAs($this->medico)->get(route('reportes.excel.download'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_excel_incluye_datos_de_inspeccion()
    {
        DetalleInspeccion::factory()->count(5)->create([
            'inspeccion_id' => $this->inspeccion->id,
            'animal_id' => Animal::factory()->create(['predio_id' => $this->predio->id])->id,
            'resultado_prueba' => 'Negativo',
        ]);

        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $this->inspeccion->id,
            'animal_id' => Animal::factory()->create(['predio_id' => $this->predio->id])->id,
            'resultado_prueba' => 'Positivo',
        ]);

        $spreadsheet = $this->parseExcelFromExport();
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('CLAVE', $sheet->getCell('A1')->getValue());
        $this->assertSame('PROBADOS', $sheet->getCell('I3')->getValue());
        $this->assertSame('NEGATIVOS', $sheet->getCell('J3')->getValue());
        $this->assertSame('REACTORES', $sheet->getCell('K3')->getValue());

        $folio = $this->inspeccion->folio;
        $claveCelda = $sheet->getCell('A4')->getValue();
        $this->assertStringContainsString($folio, $claveCelda);

        $this->assertSame(6, (int) $sheet->getCell('I4')->getValue(), 'Debe mostrar 6 animales probados');
        $this->assertSame(5, (int) $sheet->getCell('J4')->getValue(), 'Debe mostrar 5 negativos');
        $this->assertSame(1, (int) $sheet->getCell('K4')->getValue(), 'Debe mostrar 1 reactor');

        $spreadsheet->disconnectWorksheets();
    }
}
