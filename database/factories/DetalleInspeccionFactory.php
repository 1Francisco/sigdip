<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Inspeccion;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleInspeccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inspeccion_id' => Inspeccion::factory(),
            'animal_id' => Animal::factory(),
            'tipo_arete' => fake()->randomElement(['SINIIGA', 'Arete', 'Fierro']),
            'edad_meses' => fake()->numberBetween(6, 120),
            'raza' => fake()->randomElement(['Angus', 'Brangus', 'Charolais', 'Hereford', 'Cebú']),
            'sexo' => fake()->randomElement(['Macho', 'Hembra']),
            'fierro' => fake()->optional()->bothify('??-####'),
            'resultado_prueba' => fake()->randomElement(['Negativo', 'Sospechoso', 'Positivo']),
            'observaciones_animal' => fake()->optional()->sentence(),
        ];
    }
}
