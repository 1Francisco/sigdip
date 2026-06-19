<?php

namespace Database\Factories;

use App\Models\Productor;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre_rancho' => fake()->company(),
            'clave_unidad_produccion' => strtoupper(fake()->bothify('CUP-#####')),
            'latitud' => fake()->latitude(22, 27),
            'longitud' => fake()->longitude(-109, -105),
            'domicilio' => fake()->streetAddress(),
            'municipio' => fake()->city(),
            'localidad' => fake()->city(),
            'productor_id' => Productor::factory(),
        ];
    }
}
