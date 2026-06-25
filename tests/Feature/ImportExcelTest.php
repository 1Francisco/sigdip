<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportExcelTest extends TestCase
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

    public function test_index_loads_as_admin()
    {
        $response = $this->actingAs($this->admin)->get(route('import.excel.index'));

        $response->assertStatus(200);
    }

    public function test_index_redirects_guest()
    {
        $response = $this->get(route('import.excel.index'));

        $response->assertRedirect('/login');
    }

    public function test_preview_validates_file()
    {
        $response = $this->actingAs($this->admin)->post(route('import.excel.preview'), [
            'archivo' => '',
        ]);

        $response->assertStatus(500);
    }

    public function test_import_validates_file()
    {
        $response = $this->actingAs($this->admin)->post(route('import.excel.import'), []);

        $response->assertSessionHasErrors('archivo');
    }

    public function test_index_denies_medico()
    {
        $medico = User::factory()->create();
        $medico->assignRole('Medico_Campo');

        $response = $this->actingAs($medico)->get(route('import.excel.index'));

        $response->assertStatus(403);
    }

    public function test_preview_con_archivo_valido()
    {
        $spreadsheet = new Spreadsheet;

        // Hoja BASE
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BASE');
        $sheet->setCellValue('A1', 'PRODUCTOR');
        $sheet->setCellValue('B1', 'CURP');
        $sheet->setCellValue('C1', 'UPP');
        $sheet->setCellValue('D1', 'MUNICIPIO');
        $sheet->setCellValue('E1', 'LOCALIDAD');
        $sheet->setCellValue('A2', 'JUAN PEREZ LOPEZ');
        $sheet->setCellValue('B2', 'PELJ800101HDFRRN06');
        $sheet->setCellValue('C2', 'UPP-TEST-001');
        $sheet->setCellValue('D2', 'Tepic');
        $sheet->setCellValue('E2', 'Xalisco');

        // Hoja ARETE
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('ARETE');
        $sheet2->setCellValue('A1', 'ARETE');
        $sheet2->setCellValue('B1', 'RAZA');
        $sheet2->setCellValue('C1', 'SEXO');
        $sheet2->setCellValue('D1', 'FECHA NAC');
        $sheet2->setCellValue('A2', '123456789');
        $sheet2->setCellValue('B2', 'Cebú');
        $sheet2->setCellValue('C2', 'H');
        $sheet2->setCellValue('D2', '2024-01-15');

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'import_test_').'.xlsx';
        $writer->save($tempPath);

        $file = new UploadedFile(
            $tempPath,
            'test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            0,
            true
        );

        $response = $this->actingAs($this->admin)->post(
            route('import.excel.preview'),
            ['archivo' => $file]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'archivo' => 'test.xlsx',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'BASE' => ['headers', 'rows', 'total'],
                'ARETE' => ['headers', 'rows', 'total'],
            ],
        ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, $data['BASE']['total']);
        $this->assertGreaterThan(0, $data['ARETE']['total']);

        $baseRow = $data['BASE']['rows'][0] ?? [];
        $this->assertStringContainsString('JUAN', implode(' ', $baseRow));

        $areteRow = $data['ARETE']['rows'][0] ?? [];
        $this->assertStringContainsString('123456789', implode(' ', $areteRow));

        @unlink($tempPath);
    }
}
