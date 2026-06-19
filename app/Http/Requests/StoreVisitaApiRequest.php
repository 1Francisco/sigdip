<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitaApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'nullable|string|max:50|unique:visitas,codigo_unico',
            'predio_id' => 'required|exists:predios,id',
            'veterinario_id' => 'nullable|exists:users,id',
            'fecha_programada' => 'required|date',
            'observaciones' => 'nullable|string',
        ];
    }
}
