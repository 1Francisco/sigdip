<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspeccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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

        if (!$user->hasRole('Administrador')) {
            $query->where('veterinario_id', $user->id);
        }

        $inspecciones = $query->orderByDesc('id')->get()->map(fn (Inspeccion $inspeccion) => $this->toListArray($inspeccion));

        return response()->json([
            'success' => true,
            'data' => $inspecciones,
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
        $filename = $this->buildPdfFilename($inspeccion);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function update(Request $request, $id)
    {
        $inspeccion = Inspeccion::with(['predio.productor', 'detalles.animal', 'veterinario', 'visita.predio.productor'])
            ->findOrFail($id);

        $this->authorizeInspection($request, $inspeccion);

        $validated = $request->validate([
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|in:borrador,sincronizado',
        ]);

        $inspeccion->update($validated);

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
                    'observaciones_animal' => $detalle->observaciones_animal,
                    'animal' => $detalle->animal ? [
                        'id' => $detalle->animal->id,
                        'numero_arete_siniiga' => $detalle->animal->numero_arete_siniiga,
                        'raza' => $detalle->animal->raza,
                        'sexo' => $detalle->animal->sexo,
                        'edad' => $detalle->animal->edad,
                    ] : null,
                ];
            })->values(),
            'pdf_url' => url("/api/inspecciones/{$inspeccion->id}/pdf"),
        ];
    }

    private function buildPdfFilename(Inspeccion $inspeccion): string
    {
        $productor = $inspeccion->predio?->productor;
        $nombre = trim(($productor?->apellido_paterno ?? 'DICTAMEN') . ' ' . ($productor?->nombre ?? ''));
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre);
        $nombre = preg_replace('/[^A-Za-z0-9 _-]/', '', $nombre);
        $nombre = preg_replace('/\s+/', '_', trim($nombre));
        $nombre = strtoupper($nombre ?: 'DICTAMEN');
        $fecha = $inspeccion->fecha_inyeccion
            ? \Carbon\Carbon::parse($inspeccion->fecha_inyeccion)->format('d-m-Y')
            : now()->format('d-m-Y');

        return "DICTAMEN_{$nombre}_{$fecha}.pdf";
    }
}
