<?php

namespace Database\Factories;

use App\Models\Predio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InspeccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'veterinario_id' => User::factory(),
            'predio_id' => Predio::factory(),
            'folio' => 'INS-'.date('Ymd').'-'.fake()->unique()->numerify('###'),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now'),
            'tipo_inspeccion' => fake()->randomElement(['Movilización', 'Dictamen', 'Certificación']),
            'tipo_prueba' => fake()->randomElement(['Tuberculina', 'Brutcelosis', 'Triple']),
            'fecha_inyeccion' => fake()->optional()->dateTimeBetween('-2 months', '-1 month'),
            'hora_inyeccion' => fake()->optional()->time(),
            'fecha_lectura' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'hora_lectura' => fake()->optional()->time(),
            'motivo_prueba' => fake()->optional()->sentence(),
            'funcion_zootecnica' => fake()->randomElement(['Carne', 'Leche', 'Doble propósito', 'Engorda']),
            'sementales' => fake()->numberBetween(0, 5),
            'vacas' => fake()->numberBetween(0, 50),
            'vaquillas' => fake()->numberBetween(0, 20),
            'becerras' => fake()->numberBetween(0, 20),
            'becerros' => fake()->numberBetween(0, 20),
            'observaciones' => fake()->optional()->sentence(),
            'estado' => fake()->randomElement(['borrador', 'sincronizado']),
        ];
    }
}
