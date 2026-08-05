<?php

namespace Tests\Feature;

use App\Models\AreteCenso;
use App\Models\Productor;
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

    public function test_import_con_multiples_productores_asocia_aretes_correctamente()
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

        // Productor 1
        $sheet->setCellValue('A2', 'JUAN PEREZ LOPEZ');
        $sheet->setCellValue('B2', 'PELJ800101HDFRRN06');
        $sheet->setCellValue('C2', 'UPP-TEST-001');
        $sheet->setCellValue('D2', 'Tepic');
        $sheet->setCellValue('E2', 'Xalisco');

        // Productor 2
        $sheet->setCellValue('A3', 'MARIA GOMEZ SANCHEZ');
        $sheet->setCellValue('B3', 'GOSM850505MDFRRN02');
        $sheet->setCellValue('C3', 'UPP-TEST-002');
        $sheet->setCellValue('D3', 'Tepic');
        $sheet->setCellValue('E3', 'San Cayetano');

        // Productor 3 (Edwin Alexis is registered as Alexis Echevarria Sanchez in BASE)
        $sheet->setCellValue('A4', 'ALEXIS ECHEVARRIA SANCHEZ');
        $sheet->setCellValue('B4', 'ECSA900202HDFRRN01');
        $sheet->setCellValue('C4', 'UPP-TEST-003');
        $sheet->setCellValue('D4', 'Tepic');
        $sheet->setCellValue('E4', 'Xalisco');

        // Hoja ARETE
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('ARETE');
        $sheet2->setCellValue('A1', 'ARETE');
        $sheet2->setCellValue('B1', 'RAZA');
        $sheet2->setCellValue('C1', 'SEXO');
        $sheet2->setCellValue('D1', 'FECHA NAC');
        $sheet2->setCellValue('E1', 'PRODUCTOR');

        // Arete 1 - Exact name match with Juan Perez Lopez
        $sheet2->setCellValue('A2', '123456789');
        $sheet2->setCellValue('B2', 'Cebú');
        $sheet2->setCellValue('C2', 'H');
        $sheet2->setCellValue('D2', '2024-01-15');
        $sheet2->setCellValue('E2', 'JUAN PEREZ LOPEZ');

        // Arete 2 - UPP match with Maria Gomez Sanchez
        $sheet2->setCellValue('A3', '987654321');
        $sheet2->setCellValue('B3', 'Angus');
        $sheet2->setCellValue('C3', 'M');
        $sheet2->setCellValue('D3', '2023-05-20');
        $sheet2->setCellValue('E3', 'UPP-TEST-002');

        // Arete 3 - Partial name match with Maria Gomez Sanchez
        $sheet2->setCellValue('A4', '555555555');
        $sheet2->setCellValue('B4', 'Hereford');
        $sheet2->setCellValue('C4', 'H');
        $sheet2->setCellValue('D4', '2022-10-10');
        $sheet2->setCellValue('E4', 'MARIA GOMEZ');

        // Arete 4 - Multi-name match: "JUAN FRANCISCO PEREZ" in arete, "JUAN PEREZ LOPEZ" in base
        $sheet2->setCellValue('A5', '777777777');
        $sheet2->setCellValue('B5', 'Charolais');
        $sheet2->setCellValue('C5', 'M');
        $sheet2->setCellValue('D5', '2021-12-05');
        $sheet2->setCellValue('E5', 'JUAN FRANCISCO PEREZ');

        // Arete 5 - First name word match: "EDWIN ALEXIS" in arete, "ALEXIS ECHEVARRIA SANCHEZ" in base
        $sheet2->setCellValue('A6', '888888888');
        $sheet2->setCellValue('B6', 'Hereford');
        $sheet2->setCellValue('C6', 'H');
        $sheet2->setCellValue('D6', '2023-08-12');
        $sheet2->setCellValue('E6', 'EDWIN ALEXIS');

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
            route('import.excel.import'),
            ['archivo' => $file]
        );

        $response->assertStatus(302); // Redirect back on success
        $response->assertSessionHas('success');

        // Verify databases relations
        $productorJuan = Productor::where('curp', 'PELJ800101HDFRRN06')->first();
        $productorMaria = Productor::where('curp', 'GOSM850505MDFRRN02')->first();
        $productorAlexis = Productor::where('curp', 'ECSA900202HDFRRN01')->first();

        $this->assertNotNull($productorJuan);
        $this->assertNotNull($productorMaria);
        $this->assertNotNull($productorAlexis);

        $arete1 = AreteCenso::where('numero_arete', '123456789')->first();
        $arete2 = AreteCenso::where('numero_arete', '987654321')->first();
        $arete3 = AreteCenso::where('numero_arete', '555555555')->first();
        $arete4 = AreteCenso::where('numero_arete', '777777777')->first();
        $arete5 = AreteCenso::where('numero_arete', '888888888')->first();

        $this->assertNotNull($arete1);
        $this->assertNotNull($arete2);
        $this->assertNotNull($arete3);
        $this->assertNotNull($arete4);
        $this->assertNotNull($arete5);

        $this->assertEquals($productorJuan->id, $arete1->productor_id);
        $this->assertEquals($productorMaria->id, $arete2->productor_id);
        $this->assertEquals($productorMaria->id, $arete3->productor_id);
        $this->assertEquals($productorJuan->id, $arete4->productor_id);
        $this->assertEquals($productorAlexis->id, $arete5->productor_id);

        @unlink($tempPath);
    }
}
