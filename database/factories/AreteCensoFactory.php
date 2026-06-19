<?php

namespace Database\Factories;

use App\Models\Predio;
use App\Models\Productor;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreteCensoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero_arete' => fake()->unique()->bothify('ARE-######'),
            'productor_id' => Productor::factory(),
            'predio_id' => Predio::factory(),
            'raza' => fake()->randomElement(['Angus', 'Brangus', 'Charolais', 'Hereford', 'Cebú']),
            'sexo' => fake()->randomElement(['Macho', 'Hembra']),
            'fecha_nacimiento' => fake()->optional()->dateTimeBetween('-5 years', '-6 months'),
            'edad_meses' => fake()->numberBetween(6, 60),
            'sacrificio' => fake()->boolean(20),
            'archivo_origen' => fake()->optional()->word().'.xlsx',
        ];
    }
}
