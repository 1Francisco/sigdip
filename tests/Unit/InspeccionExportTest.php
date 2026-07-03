<?php

namespace Tests\Unit;

use App\Exports\InspeccionExport;
use App\Models\Animal;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspeccionExportTest extends TestCase
{
    use RefreshDatabase;

    private Inspeccion $inspeccion;

    private Productor $productor;

    private Predio $predio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productor = Productor::factory()->create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'López',
        ]);
        $this->predio = Predio::factory()->create([
            'productor_id' => $this->productor->id,
            'nombre_rancho' => 'Rancho Export',
            'clave_unidad_produccion' => 'CUP-EXPORT',
            'municipio' => 'Culiacán',
            'localidad' => 'Badiraguato',
            'latitud' => 25.0,
            'longitud' => -108.0,
        ]);
        $this->inspeccion = Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'folio' => 'EXP-001',
            'fecha_inyeccion' => '2026-06-15',
            'fecha_lectura' => '2026-06-18',
            'funcion_zootecnica' => 'Carne',
            'sementales' => 2,
            'vacas' => 10,
            'vaquillas' => 3,
            'becerras' => 5,
            'becerros' => 4,
            'observaciones' => 'Sin novedad',
        ]);
    }

    public function test_headings_24_columnas()
    {
        $export = new InspeccionExport;
        $headings = $export->headings();

        $this->assertCount(24, $headings);
        $this->assertContains('CLAVE (FOLIO)', $headings);
        $this->assertContains('REACTORES (POSITIVOS)', $headings);
    }

    public function test_map_columnas_principales()
    {
        $export = new InspeccionExport;
        $row = $export->map($this->inspeccion);

        $this->assertEquals($this->inspeccion->id, $row[0]);
        $this->assertEquals('DEFINITIVA', $row[1]);
        $this->assertEquals('EXP-001', $row[2]);
        $this->assertEquals('Rancho Export', $row[3]);
        $this->assertEquals('CUP-EXPORT', $row[4]);
        $this->assertEquals('Juan Pérez López', $row[5]);
    }

    public function test_map_cuenta_resultados()
    {
        $animal1 = Animal::factory()->create(['predio_id' => $this->predio->id]);
        $animal2 = Animal::factory()->create(['predio_id' => $this->predio->id]);
        $animal3 = Animal::factory()->create(['predio_id' => $this->predio->id]);

        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $this->inspeccion->id,
            'animal_id' => $animal1->id,
            'resultado_prueba' => 'Negativo',
        ]);
        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $this->inspeccion->id,
            'animal_id' => $animal2->id,
            'resultado_prueba' => 'Positivo',
        ]);
        DetalleInspeccion::factory()->create([
            'inspeccion_id' => $this->inspeccion->id,
            'animal_id' => $animal3->id,
            'resultado_prueba' => 'Sospechoso',
        ]);

        $export = new InspeccionExport;
        $row = $export->map($this->inspeccion);

        $this->assertEquals(3, $row[17]); // Animales probados
        $this->assertEquals(1, $row[18]); // Negativos
        $this->assertEquals(1, $row[19]); // Sospechosos
        $this->assertEquals(1, $row[20]); // Positivos
    }

    public function test_map_sin_detalles()
    {
        $export = new InspeccionExport;
        $row = $export->map($this->inspeccion);

        $this->assertEquals(0, $row[17]);
        $this->assertEquals(0, $row[18]);
        $this->assertEquals(0, $row[19]);
        $this->assertEquals(0, $row[20]);
    }

    public function test_map_municipio_fallback()
    {
        $this->predio->update(['municipio' => null]);
        $this->predio->update(['localidad' => 'Localidad Fallback']);

        $export = new InspeccionExport;
        $row = $export->map($this->inspeccion->fresh());

        $this->assertEquals('Localidad Fallback', $row[6]);
    }

    public function test_map_fecha_nula_devuelve_vacio()
    {
        $this->inspeccion->update(['fecha_inyeccion' => null, 'fecha_lectura' => null]);

        $export = new InspeccionExport;
        $row = $export->map($this->inspeccion->fresh());

        $this->assertEquals('', $row[15]);
        $this->assertEquals('', $row[16]);
    }

    public function test_map_poblacion_total()
    {
        $export = new InspeccionExport;
        $row = $export->map($this->inspeccion);

        $this->assertEquals(24, $row[8]); // 2+10+3+5+4
    }

    public function test_map_fechas_formateadas()
    {
        $export = new InspeccionExport;
        $row = $export->map($this->inspeccion);

        $this->assertEquals('15/06/2026', $row[15]);
        $this->assertEquals('18/06/2026', $row[16]);
    }

    public function test_collection_eager_loads_relations()
    {
        $export = new InspeccionExport;
        $collection = $export->collection();

        $this->assertGreaterThanOrEqual(1, $collection->count());

        $first = $collection->first();
        $this->assertTrue($first->relationLoaded('predio'));
        $this->assertTrue($first->relationLoaded('detalles'));
        $this->assertNotNull($first->predio);
        $this->assertNotNull($first->predio->productor);
    }

    public function test_date_range_filter()
    {
        Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'fecha' => '2026-01-01',
        ]);
        Inspeccion::factory()->create([
            'predio_id' => $this->predio->id,
            'fecha' => '2026-06-01',
        ]);

        $export = new InspeccionExport('2026-05-01', '2026-07-01');
        $collection = $export->collection();

        $this->assertCount(2, $collection);
    }
}
