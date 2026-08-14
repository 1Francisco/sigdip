<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspeccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InspeccionesApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Inspeccion::with(['predio.productor', 'veterinario', 'visita']);

        if ($request->filled('folio')) {
            $query->where('folio', $request->folio);
        }

        if ($request->filled('visita_id')) {
            $query->where('visita_id', $request->visita_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
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

        $perPage = min((int) ($request->get('perPage', 100)), 500);

        $inspecciones = $query->orderByDesc('id')->paginate($perPage);

        $data = collect($inspecciones->items())->map(fn (Inspeccion $inspeccion) => $this->toListArray($inspeccion));

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $inspecciones->currentPage(),
                'last_page' => $inspecciones->lastPage(),
                'per_page' => $inspecciones->perPage(),
                'total' => $inspecciones->total(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $inspeccion = Inspeccion::with([
            'predio.productor',
            'detalles.animal',
            'veterinario',
            'visita.predio.productor',
        ])->findOrFail($id);

        $this->authorizeInspection($request, $inspeccion);

        return response()->json([
            'success' => true,
            'data' => $this->toDetailArray($inspeccion),
        ]);
    }

    public function pdf(Request $request, $id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario', 'visita.predio.productor'])
            ->findOrFail($id);

        $this->authorizeInspection($request, $inspeccion);

        $pdf = Pdf::loadView('reports.inspeccion_pdf', compact('inspeccion'));
        $filename = $inspeccion->buildPdfFilename();

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }

    public function ver(Request $request, $id)
    {
        return $this->pdf($request, $id);
    }

    /**
     * Sube el dictamen oficial del comité (PDF) y lo asocia a la inspección.
     */
    public function uploadDictamenComite(Request $request, $id)
    {
        $inspeccion = Inspeccion::findOrFail($id);
        $this->authorizeInspection($request, $inspeccion);

        $request->validate([
            'dictamen_comite' => 'required|file|mimes:pdf|max:10240',
        ]);

        if (! $request->hasFile('dictamen_comite')) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar el archivo.',
            ], 422);
        }

        if ($inspeccion->dictamen_comite_path) {
            Storage::disk('public')->delete($inspeccion->dictamen_comite_path);
        }

        $path = $request->file('dictamen_comite')->store('dictamenes_comite', 'public');
        $inspeccion->update(['dictamen_comite_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'El dictamen oficial del comité se ha subido y guardado correctamente.',
            'data' => $this->toDetailArray($inspeccion->refresh()->load([
                'predio.productor',
                'detalles.animal',
                'veterinario',
                'visita.predio.productor',
            ])),
        ]);
    }

    /**
     * Descarga el dictamen oficial del comité como archivo adjunto.
     */
    public function downloadDictamenComite(Request $request, $id)
    {
        $inspeccion = Inspeccion::findOrFail($id);
        $this->authorizeInspection($request, $inspeccion);

        if ($inspeccion->dictamen_comite_path) {
            $filePath = Storage::disk('public')->path($inspeccion->dictamen_comite_path);
            if (file_exists($filePath)) {
                $filename = 'DICTAMEN_COMITE_'.($inspeccion->clave_interna ?: $inspeccion->folio ?: $inspeccion->id).'.pdf';

                return response()->file($filePath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]);
            }
        }

        abort(404, 'El archivo solicitado no existe en el servidor.');
    }

    /**
     * Elimina el dictamen oficial del comité de la inspección.
     */
    public function deleteDictamenComite(Request $request, $id)
    {
        $inspeccion = Inspeccion::findOrFail($id);
        $this->authorizeInspection($request, $inspeccion);

        if ($inspeccion->dictamen_comite_path) {
            Storage::disk('public')->delete($inspeccion->dictamen_comite_path);

            $inspeccion->update(['dictamen_comite_path' => null]);

            return response()->json([
                'success' => true,
                'message' => 'El dictamen oficial del comité se ha eliminado correctamente.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No hay ningún dictamen del comité registrado.',
        ], 404);
    }

    public function update(Request $request, $id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario', 'visita.predio.productor'])
            ->findOrFail($id);

        $this->authorizeInspection($request, $inspeccion);

        $validated = $request->validate([
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|in:borrador,sincronizado',
            'fecha_inyeccion' => 'nullable|date',
            'hora_inyeccion' => 'nullable|string',
            'fecha_lectura' => 'nullable|date',
            'hora_lectura' => 'nullable|string',
            'motivo_prueba' => 'nullable|string',
            'funcion_zootecnica' => 'nullable|string',
            'fecha_prueba_anterior' => 'nullable|date',
            'dictamen_anterior_no' => 'nullable|string',
            'exencion_no' => 'nullable|string',
            'exencion_fecha' => 'nullable|date',
            'hato_libre_no' => 'nullable|string',
            'hato_libre_fecha' => 'nullable|date',
            'sementales' => 'nullable|integer|min:0',
            'vacas' => 'nullable|integer|min:0',
            'vaquillas' => 'nullable|integer|min:0',
            'becerras' => 'nullable|integer|min:0',
            'becerros' => 'nullable|integer|min:0',
        ]);

        $inspeccion->update(array_merge($validated, ['modified_at' => now()]));

        return response()->json([
            'success' => true,
            'message' => 'Dictamen actualizado con éxito.',
            'data' => $this->toDetailArray($inspeccion->refresh()->load([
                'predio.productor',
                'detalles.animal',
                'veterinario',
                'visita.predio.productor',
            ])),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $inspeccion = Inspeccion::findOrFail($id);
        $this->authorizeInspection($request, $inspeccion);

        if ($inspeccion->estado !== 'borrador' && ! $request->user()->hasRole('Administrador')) {
            abort(403, 'No tienes permiso para eliminar un dictamen finalizado.');
        }

        $inspeccion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dictamen eliminado con éxito.',
        ]);
    }

    private function authorizeInspection(Request $request, Inspeccion $inspeccion): void
    {
        $user = $request->user();
        if ($user->hasRole('Administrador')) {
            return;
        }

        abort_unless((int) $inspeccion->veterinario_id === (int) $user->id, 403, 'No tienes permiso para acceder a este dictamen.');
    }

    private function toListArray(Inspeccion $inspeccion): array
    {
        return [
            'id' => $inspeccion->id,
            'folio' => $inspeccion->folio,
            'fecha' => optional($inspeccion->fecha)->format('Y-m-d'),
            'estado' => $inspeccion->estado,
            'predio' => $inspeccion->predio ? [
                'id' => $inspeccion->predio->id,
                'nombre_rancho' => $inspeccion->predio->nombre_rancho,
                'localidad' => $inspeccion->predio->localidad,
                'productor' => $inspeccion->predio->productor ? [
                    'id' => $inspeccion->predio->productor->id,
                    'nombre' => $inspeccion->predio->productor->nombre,
                    'apellido_paterno' => $inspeccion->predio->productor->apellido_paterno,
                ] : null,
            ] : null,
            'veterinario' => $inspeccion->veterinario ? [
                'id' => $inspeccion->veterinario->id,
                'name' => $inspeccion->veterinario->name,
            ] : null,
            'visita_id' => $inspeccion->visita_id,
            'clave_interna' => $inspeccion->clave_interna,
            'modified_at' => optional($inspeccion->modified_at)->format('Y-m-d\TH:i:s'),
            'modified_at_formatted' => optional($inspeccion->modified_at)->format('d/m/Y H:i'),
        ];
    }

    private function toDetailArray(Inspeccion $inspeccion): array
    {
        return [
            'id' => $inspeccion->id,
            'folio' => $inspeccion->folio,
            'fecha' => optional($inspeccion->fecha)->format('Y-m-d'),
            'estado' => $inspeccion->estado,
            'predio_id' => $inspeccion->predio_id,
            'visita_id' => $inspeccion->visita_id,
            'clave_interna' => $inspeccion->clave_interna,
            'modified_at' => optional($inspeccion->modified_at)->format('Y-m-d\TH:i:s'),
            'modified_at_formatted' => optional($inspeccion->modified_at)->format('d/m/Y H:i'),
            'tipo_prueba' => $inspeccion->tipo_prueba,
            'motivo_prueba' => $inspeccion->motivo_prueba,
            'funcion_zootecnica' => $inspeccion->funcion_zootecnica,
            'fecha_inyeccion' => optional($inspeccion->fecha_inyeccion)->format('Y-m-d'),
            'hora_inyeccion' => $inspeccion->hora_inyeccion,
            'fecha_lectura' => optional($inspeccion->fecha_lectura)->format('Y-m-d'),
            'hora_lectura' => $inspeccion->hora_lectura,
            'vigencia_fecha' => optional($inspeccion->vigencia_fecha)->format('Y-m-d'),
            'sementales' => $inspeccion->sementales,
            'vacas' => $inspeccion->vacas,
            'vaquillas' => $inspeccion->vaquillas,
            'becerras' => $inspeccion->becerras,
            'becerros' => $inspeccion->becerros,
            'observaciones' => $inspeccion->observaciones,
            'predio' => $inspeccion->predio ? [
                'id' => $inspeccion->predio->id,
                'nombre' => $inspeccion->predio->nombre_rancho,
                'nombre_rancho' => $inspeccion->predio->nombre_rancho,
                'clave_unidad_produccion' => $inspeccion->predio->clave_unidad_produccion,
                'latitud' => $inspeccion->predio->latitud,
                'longitud' => $inspeccion->predio->longitud,
                'domicilio' => $inspeccion->predio->domicilio,
                'municipio' => $inspeccion->predio->municipio,
                'localidad' => $inspeccion->predio->localidad,
                'productor' => $inspeccion->predio->productor ? [
                    'id' => $inspeccion->predio->productor->id,
                    'nombre' => $inspeccion->predio->productor->nombre,
                    'apellido_paterno' => $inspeccion->predio->productor->apellido_paterno,
                    'apellido_materno' => $inspeccion->predio->productor->apellido_materno,
                    'curp' => $inspeccion->predio->productor->curp,
                    'upp' => $inspeccion->predio->productor->upp,
                    'telefono' => $inspeccion->predio->productor->telefono,
                    'domicilio' => $inspeccion->predio->productor->domicilio,
                    'municipio' => $inspeccion->predio->productor->municipio,
                    'localidad' => $inspeccion->predio->productor->localidad,
                    'estado' => $inspeccion->predio->productor->estado,
                    'email' => $inspeccion->predio->productor->email,
                ] : null,
            ] : null,
            'veterinario' => $inspeccion->veterinario ? [
                'id' => $inspeccion->veterinario->id,
                'name' => $inspeccion->veterinario->name,
                'email' => $inspeccion->veterinario->email,
            ] : null,
            'visita' => $inspeccion->visita ? [
                'id' => $inspeccion->visita->id,
                'fecha_programada' => optional($inspeccion->visita->fecha_programada)->format('Y-m-d'),
                'estado' => $inspeccion->visita->estado,
            ] : null,
            'detalles' => $inspeccion->detalles->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'animal_id' => $detalle->animal_id,
                    'tipo_arete' => $detalle->tipo_arete,
                    'edad_meses' => $detalle->edad_meses,
                    'raza' => $detalle->raza,
                    'sexo' => $detalle->sexo,
                    'fierro' => $detalle->fierro,
                    'resultado_prueba' => $detalle->resultado_prueba,
                    'motivo_no_aplica' => $detalle->motivo_no_aplica,
                    'observaciones_animal' => $detalle->observaciones_animal,
                    'agregado_en_lectura' => (bool) $detalle->agregado_en_lectura,
                    'animal' => $detalle->animal ? [
                        'id' => $detalle->animal->id,
                        'numero_arete_siniiga' => $detalle->animal->numero_arete_siniiga,
                        'raza' => $detalle->animal->raza,
                        'sexo' => $detalle->animal->sexo,
                        'edad' => $detalle->animal->edad,
                    ] : null,
                ];
            })->values(),
            'dictamen_comite' => $inspeccion->dictamen_comite_path ? [
                'path' => $inspeccion->dictamen_comite_path,
                'url' => url("/api/inspecciones/{$inspeccion->id}/download-dictamen-comite"),
            ] : null,
            'pdf_url' => url("/api/inspecciones/{$inspeccion->id}/pdf"),
        ];
    }
}
