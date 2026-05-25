<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Predio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnimalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $predios = Predio::all();
        $razas = ['Angus', 'Hereford', 'Charolais', 'Brahman', 'Holstein', 'Jersey'];
        $sexos = ['Macho', 'Hembra'];

        if ($predios->isEmpty()) {
            $this->command->error('No hay predios para asignar animales. Ejecuta ProductoresAndPrediosSeeder primero.');
            return;
        }

        for ($i = 1; $i <= 200; $i++) {
            Animal::create([
                'numero_arete_siniiga' => '70' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'edad' => rand(6, 48), // Edad en meses
                'raza' => $razas[array_rand($razas)],
                'sexo' => $sexos[array_rand($sexos)],
                'predio_id' => $predios->random()->id,
            ]);
        }
    }
}
