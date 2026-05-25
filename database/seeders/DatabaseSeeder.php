<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Inspeccion;
use App\Models\DetalleInspeccion;
use App\Models\Predio;
use App\Models\Animal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear veterinario por defecto
        $user = User::factory()->create([
            'name' => 'Veterinario Principal',
            'email' => 'veterinario@cefppenay.org',
            'password' => Hash::make('password'),
        ]);

        // 2. Ejecutar seeders base
        $this->call([
            ProductoresAndPrediosSeeder::class,
            AnimalesSeeder::class,
        ]);

        // 3. Crear algunas inspecciones de prueba para el Dashboard
        $predios = Predio::all();
        $animales = Animal::all();

        foreach ($predios as $index => $predio) {
            $inspeccion = Inspeccion::create([
                'veterinario_id' => $user->id,
                'predio_id' => $predio->id,
                'folio' => 'INS-' . date('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'fecha' => now()->subDays(rand(0, 30)),
                'tipo_inspeccion' => 'Movilización',
                'observaciones' => 'Inspección de prueba generada automáticamente.',
                'estado' => 'sincronizado'
            ]);

            // Asignar 10 animales aleatorios a esta inspección
            $animalesRandom = $animales->where('predio_id', $predio->id)->take(10);
            foreach ($animalesRandom as $animal) {
                DetalleInspeccion::create([
                    'inspeccion_id' => $inspeccion->id,
                    'animal_id' => $animal->id,
                    'resultado_prueba' => rand(0, 10) > 8 ? 'Positivo' : 'Negativo',
                ]);
            }
        }
    }
}
