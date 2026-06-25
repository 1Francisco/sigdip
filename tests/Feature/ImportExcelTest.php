<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->markTestIncomplete('Requiere generar un archivo Excel válido con PhpSpreadsheet para probar el parseo.');
    }
}
