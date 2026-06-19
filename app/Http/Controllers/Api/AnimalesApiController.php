<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnimalesApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        $query = Animal::with('predio.productor');

        if ($request->has('predio_id')) {
            $query->where('predio_id', $request->predio_id);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_arete_siniiga', 'like', "%{$search}%")
                    ->orWhere('raza', 'like', "%{$search}%")
                    ->orWhereHas('predio', function ($pq) use ($search) {
                        $pq->where('nombre_rancho', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min((int) ($request->get('perPage', 20)), 100);
        $animales = $query->latest()->paginate($perPage);

        $data = collect($animales->items())->map(fn (Animal $animal) => $this->toArray($animal));

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $animales->currentPage(),
                'last_page' => $animales->lastPage(),
                'per_page' => $animales->perPage(),
                'total' => $animales->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $user = request()->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        $animal = Animal::with('predio.productor')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->toArray($animal),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        try {
            $validated = $request->validate([
                'numero_arete_siniiga' => 'required|string|max:255|unique:animales',
                'predio_id' => 'required|exists:predios,id',
                'raza' => 'nullable|string|max:255',
                'sexo' => 'nullable|string|max:50',
                'edad' => 'nullable|integer|min:0',
            ]);

            $animal = Animal::create($validated);
            $animal->load('predio.productor');

            return response()->json([
                'success' => true,
                'message' => 'Animal registrado con éxito.',
                'data' => $this->toArray($animal),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        try {
            $animal = Animal::findOrFail($id);

            $validated = $request->validate([
                'numero_arete_siniiga' => 'required|string|max:255|unique:animales,numero_arete_siniiga,'.$animal->id,
                'predio_id' => 'required|exists:predios,id',
                'raza' => 'nullable|string|max:255',
                'sexo' => 'nullable|string|max:50',
                'edad' => 'nullable|integer|min:0',
            ]);

            $animal->update($validated);
            $animal->load('predio.productor');

            return response()->json([
                'success' => true,
                'message' => 'Animal actualizado con éxito.',
                'data' => $this->toArray($animal),
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $user = request()->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        try {
            $animal = Animal::findOrFail($id);
            $animal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Animal eliminado con éxito.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.',
            ], 404);
        }
    }

    private function toArray(Animal $animal): array
    {
        return [
            'id' => $animal->id,
            'numero_arete_siniiga' => $animal->numero_arete_siniiga,
            'raza' => $animal->raza,
            'sexo' => $animal->sexo,
            'edad' => $animal->edad,
            'predio_id' => $animal->predio_id,
            'predio' => $animal->predio ? [
                'id' => $animal->predio->id,
                'nombre_rancho' => $animal->predio->nombre_rancho,
                'clave_unidad_produccion' => $animal->predio->clave_unidad_produccion,
                'municipio' => $animal->predio->municipio,
                'productor' => $animal->predio->productor ? [
                    'id' => $animal->predio->productor->id,
                    'nombre' => $animal->predio->productor->nombre,
                    'apellido_paterno' => $animal->predio->productor->apellido_paterno,
                ] : null,
            ] : null,
        ];
    }
}
