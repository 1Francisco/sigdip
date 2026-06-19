<?php

namespace Database\Factories;

use App\Models\Predio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => 'V-'.strtoupper(fake()->unique()->bothify('####??##-####')),
            'predio_id' => Predio::factory(),
            'veterinario_id' => User::factory(),
            'fecha_programada' => fake()->dateTimeBetween('now', '+1 month'),
            'estado' => fake()->randomElement(['pendiente', 'completada', 'cancelada']),
            'inyeccion' => fake()->boolean(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
