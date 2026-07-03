<?php

namespace Tests\Unit;

use App\Models\Productor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_clave_con_productor_completo()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'López',
        ]);

        $clave = generarClaveInterna($productor);

        $this->assertMatchesRegularExpression('/^JPL-[A-Z0-9]{5}-\d{8}$/', $clave);
    }

    public function test_genera_clave_con_nombre_compuesto()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'María Elena',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'López',
        ]);

        $clave = generarClaveInterna($productor);

        $this->assertMatchesRegularExpression('/^MEPL-[A-Z0-9]{5}-\d{8}$/', $clave);
    }

    public function test_genera_clave_sin_productor()
    {
        $clave = generarClaveInterna(null);

        $this->assertMatchesRegularExpression('/^-[A-Z0-9]{5}-\d{8}$/', $clave);
    }

    public function test_genera_clave_sin_apellido_materno()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => '',
        ]);

        $clave = generarClaveInterna($productor);

        $this->assertMatchesRegularExpression('/^CR-[A-Z0-9]{5}-\d{8}$/', $clave);
    }

    public function test_genera_clave_con_solo_nombre()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Pedro',
            'apellido_paterno' => '',
            'apellido_materno' => '',
        ]);

        $clave = generarClaveInterna($productor);

        $this->assertMatchesRegularExpression('/^P-[A-Z0-9]{5}-\d{8}$/', $clave);
    }

    public function test_genera_clave_fecha_actual()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Test',
            'apellido_paterno' => 'User',
        ]);

        $clave = generarClaveInterna($productor);
        $hoy = now()->format('dmY');

        $this->assertStringEndsWith($hoy, $clave);
    }

    public function test_genera_clave_random_cinco_caracteres()
    {
        $productor = Productor::factory()->create([
            'nombre' => 'Ana',
            'apellido_paterno' => 'Luz',
        ]);

        $clave1 = generarClaveInterna($productor);
        $clave2 = generarClaveInterna($productor);

        $this->assertNotEquals($clave1, $clave2, 'Dos llamadas con el mismo productor deben generar claves distintas (parte aleatoria)');
    }
}
