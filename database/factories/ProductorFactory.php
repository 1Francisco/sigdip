<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'upp' => strtoupper(fake()->unique()->bothify('UPP-########')),
            'curp' => strtoupper(fake()->unique()->bothify('????######H??####??')),
            'domicilio' => fake()->streetAddress(),
            'municipio' => fake()->city(),
            'localidad' => fake()->city(),
            'estado' => 'Sinaloa',
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
        ];
    }
}
