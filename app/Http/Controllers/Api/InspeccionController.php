<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AreteCenso;
use App\Models\DetalleInspeccion;
use App\Models\Inspeccion;
use App\Models\Visita;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InspeccionController extends Controller
{
    /**
     * Lista de dictámenes del veterinario autenticado (o todos si es admin).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Inspeccion::with(['predio.productor', 'veterinario'])
            ->latest();

        if (! $user->hasRole('Administrador')) {
            $query->where('veterinario_id', $user->id);
        }

        if ($request->has('estado') && in_array($request->estado, ['borrador', 'sincronizado'])) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('q') && $request->q) {
            $query->where('folio', 'like', '%'.trim($request->q).'%');
        }

        $inspecciones = $query->paginate(20)->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => $inspecciones->items(),
            'pagination' => [
                'current_page' => $inspecciones->currentPage(),
                'last_page' => $inspecciones->lastPage(),
                'per_page' => $inspecciones->perPage(),
                'total' => $inspecciones->total(),
            ],
        ]);
    }

    /**
     * Detalle completo de un dictamen.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario', 'visita'])
            ->findOrFail($id);

        if (! $user->hasRole('Administrador') && $inspeccion->veterinario_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permiso para ver este dictamen.',
            ], 403);
        }

        return response()->json(['status' => 'success', 'data' => $inspeccion]);
    }

    /**
     * Actualiza un dictamen (guard: borradores por su autor, finalizados solo admin).
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $inspeccion = Inspeccion::findOrFail($id);

        // Edit Lock Guard (mismo criterio que la web)
        if ($inspeccion->estado !== 'borrador' && ! $user->hasRole('Administrador')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permiso para editar un dictamen finalizado.',
            ], 403);
        }

        if (! $user->hasRole('Administrador') && $inspeccion->veterinario_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permiso para editar este dictamen.',
            ], 403);
        }

        $isDraft = ($request->input('estado', 'borrador')) === 'borrador';

        $rules = [
            'predio_id' => 'required|exists:predios,id',
            'fecha' => 'required|date',
            'folio' => 'required|unique:inspecciones,folio,'.$inspeccion->id,
        ];

        if (! $isDraft) {
            $rules = array_merge($rules, [
                'fecha_inyeccion' => 'required|date',
                'hora_inyeccion' => 'required',
                'fecha_lectura' => 'required|date',
                'hora_lectura' => 'required',
                'animales' => 'required|array|min:1',
                'animales.*.identificador' => 'required',
                'animales.*.resultado' => 'required|in:Negativo,Positivo,Sospechoso',
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $inspeccion->update($request->except(['animales']));

            // Reescribir animales (mismo criterio que la web)
            if ($request->has('animales') && is_array($request->animales)) {
                $inspeccion->detalles()->delete();

                foreach ($request->animales as $item) {
                    if (empty($item['identificador']) && $isDraft) {
                        continue;
                    }

                    $animal = Animal::firstOrCreate(
                        ['numero_arete_siniiga' => $item['identificador']],
                        [
                            'predio_id' => $request->predio_id,
                            'raza' => $item['raza'] ?? 'N/A',
                            'edad' => $item['edad_meses'] ?? 0,
                        ]
                    );

                    DetalleInspeccion::create([
                        'inspeccion_id' => $inspeccion->id,
                        'animal_id' => $animal->id,
                        'edad_meses' => $item['edad_meses'] ?? null,
                        'raza' => $item['raza'] ?? null,
                        'sexo' => $item['sexo'] ?? null,
                        'fierro' => $item['fierro'] ?? null,
                        'resultado_prueba' => $item['resultado'] ?? 'Negativo',
                        'observaciones_animal' => $item['observaciones'] ?? null,
                    ]);
                }
            }

            // Marcar visita como completada si se finalizó
            if (! $isDraft && $inspeccion->visita_id) {
                $visita = Visita::find($inspeccion->visita_id);
                if ($visita && $visita->estado !== 'completada') {
                    $visita->update(['estado' => 'completada']);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Dictamen actualizado.',
                'data' => $inspeccion->fresh(['detalles.animal', 'predio.productor']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un borrador propio.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $inspeccion = Inspeccion::findOrFail($id);

        if (! $user->hasRole('Administrador') && ($inspeccion->estado !== 'borrador' || $inspeccion->veterinario_id !== $user->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo puedes eliminar tus propios borradores.',
            ], 403);
        }

        $inspeccion->detalles()->delete();
        $inspeccion->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Borrador eliminado.',
        ]);
    }

    /**
     * Consulta el censo SINIIGA para autocompletar datos del animal.
     */
    public function buscarArete($numero)
    {
        $numero = trim($numero);

        $animal = AreteCenso::where('numero_arete', $numero)->first();

        if ($animal) {
            return response()->json([
                'success' => true,
                'data' => [
                    'raza' => $animal->raza,
                    'sexo' => $animal->sexo,
                    'edad_meses' => $animal->edad_meses,
                    'fecha_nacimiento' => $animal->fecha_nacimiento,
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Arete no encontrado en el censo'], 404);
    }

    /**
     * Genera el PDF del dictamen (misma vista que la web).
     */
    public function pdf(Request $request, $id)
    {
        $user = $request->user();

        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario'])
            ->findOrFail($id);

        if (! $user->hasRole('Administrador') && $inspeccion->veterinario_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permiso para ver este dictamen.',
            ], 403);
        }

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));

        return $pdf->stream("inspeccion_{$inspeccion->id}.pdf");
    }

    /**
     * Sincroniza los detalles de inspección desde la app móvil.
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inspeccion_id' => 'required|exists:inspecciones,id',
            'detalles' => 'required|array',
            'detalles.*.animal_id' => 'required|exists:animales,id',
            'detalles.*.resultado_prueba' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->detalles as $detalle) {
                $values = collect($detalle)->only([
                    'tipo_arete',
                    'edad_meses',
                    'raza',
                    'sexo',
                    'fierro',
                    'resultado_prueba',
                    'motivo_no_aplica',
                    'observaciones_animal',
                    'agregado_en_lectura',
                ])->all();

                DetalleInspeccion::updateOrCreate(
                    [
                        'inspeccion_id' => $request->inspeccion_id,
                        'animal_id' => $detalle['animal_id'],
                    ],
                    $values
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sincronización exitosa',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar: '.$e->getMessage(),
            ], 500);
        }
    }
}
