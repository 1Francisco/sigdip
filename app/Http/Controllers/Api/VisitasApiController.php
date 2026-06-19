<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visita;
use Illuminate\Http\Request;

class VisitasApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Visita::with(['predio.productor', 'veterinario', 'inspeccion']);

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_programada', $request->fecha);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhereHas('predio', function ($pq) use ($search) {
                        $pq->where('nombre_rancho', 'like', "%{$search}%")
                            ->orWhereHas('productor', function ($ppq) use ($search) {
                                $ppq->where('nombre', 'like', "%{$search}%")
                                    ->orWhere('apellido_paterno', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if (! $user->hasRole('Administrador')) {
            $query->where('veterinario_id', $user->id);
        }

        $visitas = $query->orderByDesc('id')->get()->map(fn (Visita $visita) => $this->toArray($visita));

        return response()->json([
            'success' => true,
            'data' => $visitas,
        ]);
    }

    public function show(Request $request, $id)
    {
        $visita = Visita::with(['predio.productor', 'veterinario', 'inspeccion.detalles.animal'])
            ->findOrFail($id);

        $this->authorizeVisita($request, $visita);

        return response()->json([
            'success' => true,
            'data' => $this->toArray($visita, true),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'codigo' => 'nullable|string|unique:visitas,codigo',
            'predio_id' => 'required|exists:predios,id',
            'veterinario_id' => 'nullable|exists:users,id',
            'fecha_programada' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        $visita = Visita::create([
            'codigo' => $validated['codigo'] ?? null,
            'predio_id' => $validated['predio_id'],
            'veterinario_id' => $user->hasRole('Administrador') && ! empty($validated['veterinario_id'])
                ? $validated['veterinario_id']
                : $user->id,
            'fecha_programada' => $validated['fecha_programada'],
            'observaciones' => $validated['observaciones'] ?? null,
            'estado' => 'pendiente',
            'inyeccion' => false,
        ]);

        $visita->load(['predio.productor', 'veterinario', 'inspeccion']);

        return response()->json([
            'success' => true,
            'message' => 'Visita programada con éxito.',
            'data' => $this->toArray($visita, true),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $visita = Visita::with(['predio.productor', 'veterinario', 'inspeccion'])->findOrFail($id);
        $this->authorizeVisita($request, $visita);

        $validated = $request->validate([
            'codigo' => 'nullable|string|unique:visitas,codigo,'.$id,
            'predio_id' => 'required|exists:predios,id',
            'veterinario_id' => 'nullable|exists:users,id',
            'fecha_programada' => 'required|date',
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|in:pendiente,completada,cancelada',
        ]);

        $user = $request->user();

        $visita->update([
            'codigo' => $validated['codigo'] ?? $visita->codigo,
            'predio_id' => $validated['predio_id'],
            'veterinario_id' => $user->hasRole('Administrador') && ! empty($validated['veterinario_id'])
                ? $validated['veterinario_id']
                : $visita->veterinario_id,
            'fecha_programada' => $validated['fecha_programada'],
            'observaciones' => $validated['observaciones'] ?? null,
            'estado' => $validated['estado'] ?? $visita->estado,
        ]);

        $visita->refresh()->load(['predio.productor', 'veterinario', 'inspeccion']);

        return response()->json([
            'success' => true,
            'message' => 'Visita actualizada con éxito.',
            'data' => $this->toArray($visita, true),
        ]);
    }

    public function updateEstado(Request $request, $id)
    {
        $visita = Visita::findOrFail($id);
        $this->authorizeVisita($request, $visita);

        $validated = $request->validate([
            'estado' => 'required|in:pendiente,completada,cancelada',
        ]);

        $visita->update(['estado' => $validated['estado']]);

        return response()->json([
            'success' => true,
            'message' => 'Estado de la visita actualizado.',
        ]);
    }

    public function showByCodigo($codigo)
    {
        $visita = Visita::with(['predio.productor', 'veterinario', 'inspeccion.detalles.animal'])
            ->where('codigo', $codigo)
            ->first();

        if (! $visita) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'data' => $this->toArray($visita, true),
        ]);
    }

    public function checkCodigo($codigo)
    {
        $visita = Visita::with(['predio.productor', 'veterinario'])
            ->where('codigo', $codigo)
            ->first();

        if ($visita) {
            return response()->json([
                'exists' => true,
                'visita' => [
                    'codigo' => $visita->codigo,
                    'fecha_programada' => optional($visita->fecha_programada)->format('Y-m-d'),
                    'predio' => $visita->predio ? [
                        'nombre_rancho' => $visita->predio->nombre_rancho,
                    ] : null,
                    'veterinario' => $visita->veterinario ? [
                        'name' => $visita->veterinario->name,
                    ] : null,
                ],
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function reprogramar(Request $request, $id)
    {
        $visita = Visita::findOrFail($id);
        $this->authorizeVisita($request, $visita);

        $validated = $request->validate([
            'fecha_programada' => 'required|date',
        ]);

        $visita->update([
            'fecha_programada' => $validated['fecha_programada'],
            'estado' => 'pendiente',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visita reprogramada con éxito.',
        ]);
    }

    private function authorizeVisita(Request $request, Visita $visita): void
    {
        $user = $request->user();
        if ($user->hasRole('Administrador')) {
            return;
        }

        abort_unless((int) $visita->veterinario_id === (int) $user->id, 403, 'No tienes permiso para modificar esta visita.');
    }

    private function toArray(Visita $visita, bool $includeInspection = true): array
    {
        return [
            'id' => $visita->id,
            'codigo' => $visita->codigo,
            'predio_id' => $visita->predio_id,
            'veterinario_id' => $visita->veterinario_id,
            'fecha_programada' => optional($visita->fecha_programada)->format('Y-m-d'),
            'estado' => $visita->estado,
            'inyeccion' => (bool) $visita->inyeccion,
            'observaciones' => $visita->observaciones,
            'predio' => $visita->predio ? [
                'id' => $visita->predio->id,
                'nombre' => $visita->predio->nombre_rancho,
                'nombre_rancho' => $visita->predio->nombre_rancho,
                'clave_unidad_produccion' => $visita->predio->clave_unidad_produccion,
                'latitud' => $visita->predio->latitud,
                'longitud' => $visita->predio->longitud,
                'domicilio' => $visita->predio->domicilio,
                'municipio' => $visita->predio->municipio,
                'localidad' => $visita->predio->localidad,
                'productor' => $visita->predio->productor ? [
                    'id' => $visita->predio->productor->id,
                    'nombre' => $visita->predio->productor->nombre,
                    'apellido_paterno' => $visita->predio->productor->apellido_paterno,
                    'apellido_materno' => $visita->predio->productor->apellido_materno,
                    'curp' => $visita->predio->productor->curp,
                    'upp' => $visita->predio->productor->upp,
                    'telefono' => $visita->predio->productor->telefono,
                    'domicilio' => $visita->predio->productor->domicilio,
                    'municipio' => $visita->predio->productor->municipio,
                    'localidad' => $visita->predio->productor->localidad,
                    'estado' => $visita->predio->productor->estado,
                    'email' => $visita->predio->productor->email,
                ] : null,
            ] : null,
            'veterinario' => $visita->veterinario ? [
                'id' => $visita->veterinario->id,
                'name' => $visita->veterinario->name,
                'email' => $visita->veterinario->email,
            ] : null,
            'inspeccion' => $includeInspection && $visita->inspeccion ? [
                'id' => $visita->inspeccion->id,
                'folio' => $visita->inspeccion->folio,
                'estado' => $visita->inspeccion->estado,
                'fecha' => optional($visita->inspeccion->fecha)->format('Y-m-d'),
                'fecha_inyeccion' => optional($visita->inspeccion->fecha_inyeccion)->format('Y-m-d'),
                'hora_inyeccion' => $visita->inspeccion->hora_inyeccion,
                'fecha_lectura' => optional($visita->inspeccion->fecha_lectura)->format('Y-m-d'),
                'hora_lectura' => $visita->inspeccion->hora_lectura,
            ] : null,
        ];
    }
}
