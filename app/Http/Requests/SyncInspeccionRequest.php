<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncInspeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inspecciones' => 'required|array',
            'inspecciones.*.id' => 'nullable|integer',
            'inspecciones.*.predio_id' => 'required|exists:predios,id',
            'inspecciones.*.veterinario_id' => 'required|exists:users,id',
            'inspecciones.*.estado' => 'required|in:borrador,sincronizado',
            'inspecciones.*.tipo_prueba' => 'nullable|string',
            'inspecciones.*.detalles' => 'nullable|array',
            'inspecciones.*.detalles.*.animal_id' => 'required_with:inspecciones.*.detalles|exists:animales,id',
            'inspecciones.*.detalles.*.resultado_prueba' => 'required_with:inspecciones.*.detalles|in:Positivo,Negativo,Sospechoso,No Aplica',
        ];
    }
}
