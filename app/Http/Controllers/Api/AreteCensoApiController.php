<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreteCenso;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AreteCensoApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        $query = AreteCenso::with(['productor', 'predio']);

        if ($request->has('productor_id')) {
            $query->where('productor_id', $request->productor_id);
        }

        if ($request->has('predio_id')) {
            $query->where('predio_id', $request->predio_id);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_arete', 'like', "%{$search}%")
                    ->orWhere('raza', 'like', "%{$search}%")
                    ->orWhereHas('productor', function ($pq) use ($search) {
                        $pq->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min((int) ($request->get('perPage', 20)), 100);
        $aretes = $query->latest()->paginate($perPage);

        $data = collect($aretes->items())->map(fn (AreteCenso $a) => $this->toArray($a));

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $aretes->currentPage(),
                'last_page' => $aretes->lastPage(),
                'per_page' => $aretes->perPage(),
                'total' => $aretes->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $user = request()->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        $arete = AreteCenso::with(['productor', 'predio'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->toArray($arete),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Administrador'), 403, 'Solo administradores.');

        try {
            $validated = $request->validate([
                'numero_arete' => 'required|string|max:255|unique:aretes_censo',
                'productor_id' => 'required|exists:productores,id',
                'predio_id' => 'required|exists:predios,id',
                'raza' => 'nullable|string|max:255',
                'sexo' => 'nullable|string|max:50',
                'fecha_nacimiento' => 'nullable|date',
                'edad_meses' => 'nullable|integer|min:0',
                'sacrificio' => 'nullable|boolean',
            ]);

            $arete = AreteCenso::create($validated);
            $arete->load(['productor', 'predio']);

            return response()->json([
                'success' => true,
                'message' => 'Arete del censo registrado con éxito.',
                'data' => $this->toArray($arete),
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
            $arete = AreteCenso::findOrFail($id);

            $validated = $request->validate([
                'numero_arete' => 'required|string|max:255|unique:aretes_censo,numero_arete,'.$arete->id,
                'productor_id' => 'required|exists:productores,id',
                'predio_id' => 'required|exists:predios,id',
                'raza' => 'nullable|string|max:255',
                'sexo' => 'nullable|string|max:50',
                'fecha_nacimiento' => 'nullable|date',
                'edad_meses' => 'nullable|integer|min:0',
                'sacrificio' => 'nullable|boolean',
            ]);

            $arete->update($validated);
            $arete->load(['productor', 'predio']);

            return response()->json([
                'success' => true,
                'message' => 'Arete del censo actualizado con éxito.',
                'data' => $this->toArray($arete),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Arete del censo no encontrado.',
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
            $arete = AreteCenso::findOrFail($id);
            $arete->delete();

            return response()->json([
                'success' => true,
                'message' => 'Arete del censo eliminado con éxito.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Arete del censo no encontrado.',
            ], 404);
        }
    }

    private function toArray(AreteCenso $arete): array
    {
        return [
            'id' => $arete->id,
            'numero_arete' => $arete->numero_arete,
            'raza' => $arete->raza,
            'sexo' => $arete->sexo,
            'fecha_nacimiento' => $arete->fecha_nacimiento,
            'edad_meses' => $arete->edad_meses,
            'sacrificio' => $arete->sacrificio,
            'productor_id' => $arete->productor_id,
            'productor' => $arete->productor ? [
                'id' => $arete->productor->id,
                'nombre' => $arete->productor->nombre,
                'apellido_paterno' => $arete->productor->apellido_paterno,
            ] : null,
            'predio_id' => $arete->predio_id,
            'predio' => $arete->predio ? [
                'id' => $arete->predio->id,
                'nombre_rancho' => $arete->predio->nombre_rancho,
                'clave_unidad_produccion' => $arete->predio->clave_unidad_produccion,
            ] : null,
        ];
    }
}
