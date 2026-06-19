<?php

namespace Database\Factories;

use App\Models\Predio;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnimalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero_arete_siniiga' => fake()->unique()->bothify('SIN-######'),
            'edad' => fake()->numberBetween(6, 120),
            'raza' => fake()->randomElement(['Angus', 'Brangus', 'Charolais', 'Hereford', 'Simmental', 'Brahman', 'Suizo', 'Cebú']),
            'sexo' => fake()->randomElement(['Macho', 'Hembra']),
            'predio_id' => Predio::factory(),
        ];
    }
}
