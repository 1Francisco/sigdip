<?php

namespace Database\Seeders;

use App\Models\Productor;
use App\Models\Predio;
use Illuminate\Database\Seeder;

class ProductoresAndPrediosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productores = [
            [
                'nombre' => 'Juan Pérez García',
                'rpp' => 'RPP-102938',
                'curp' => 'PEGJ800101HNTLRR01',
                'telefono' => '3111234567',
                'rancho' => 'Rancho La Esperanza',
                'cup' => '18-001-0001-001',
                'localidad' => 'Tepic'
            ],
            [
                'nombre' => 'María Elena Rodríguez',
                'rpp' => 'RPP-456789',
                'curp' => 'ROEM850505MNTLRR02',
                'telefono' => '3119876543',
                'rancho' => 'El Mirador',
                'cup' => '18-001-0002-005',
                'localidad' => 'Xalisco'
            ],
            [
                'nombre' => 'Roberto Sánchez Ruiz',
                'rpp' => 'RPP-112233',
                'curp' => 'SARR750315HNTLRR03',
                'telefono' => '3115556677',
                'rancho' => 'San José',
                'cup' => '18-002-0010-012',
                'localidad' => 'Santiago Ixcuintla'
            ],
            [
                'nombre' => 'Ana Lucía Méndez',
                'rpp' => 'RPP-445566',
                'curp' => 'MEAL901212MNTLRR04',
                'telefono' => '3114443322',
                'rancho' => 'La Providencia',
                'cup' => '18-003-0015-020',
                'localidad' => 'Compostela'
            ],
            [
                'nombre' => 'Carlos Alberto Flores',
                'rpp' => 'RPP-778899',
                'curp' => 'FLAC820606HNTLRR05',
                'telefono' => '3111112233',
                'rancho' => 'Santa Fe',
                'cup' => '18-004-0020-030',
                'localidad' => 'Ixtlán del Río'
            ],
        ];

        foreach ($productores as $data) {
            $productor = Productor::create([
                'nombre' => $data['nombre'],
                'upp' => $data['rpp'], // Usamos rpp del array como upp
                'curp' => $data['curp'],
                'telefono' => $data['telefono'],
                'localidad' => $data['localidad']
            ]);

            Predio::create([
                'nombre_rancho' => $data['rancho'],
                'clave_unidad_produccion' => $data['cup'],
                'localidad' => $data['localidad'],
                'productor_id' => $productor->id,
            ]);
        }
    }
}
