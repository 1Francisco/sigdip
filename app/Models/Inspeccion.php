<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspeccion extends Model
{
    use HasFactory;

    protected $table = 'inspecciones';

    protected $fillable = [
        'veterinario_id',
        'predio_id',
        'folio',
        'fecha',
        'tipo_inspeccion',
        'tipo_prueba',
        'fecha_inyeccion',
        'hora_inyeccion',
        'fecha_lectura',
        'hora_lectura',
        'motivo_prueba',
        'funcion_zootecnica',
        'vigencia_fecha',
        'sementales',
        'vacas',
        'vaquillas',
        'becerras',
        'becerros',
        'fecha_prueba_anterior',
        'dictamen_anterior_no',
        'exencion_no',
        'exencion_fecha',
        'hato_libre_no',
        'hato_libre_fecha',
        'observaciones',
        'estado',
        'visita_id',
        'clave_interna',
        'modified_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_inyeccion' => 'date',
        'fecha_lectura' => 'date',
        'vigencia_fecha' => 'date',
        'fecha_prueba_anterior' => 'date',
        'exencion_fecha' => 'date',
        'hato_libre_fecha' => 'date',
        'modified_at' => 'datetime',
    ];

    /**
     * Get the visita that this inspection belongs to.
     */
    public function visita(): BelongsTo
    {
        return $this->belongsTo(Visita::class);
    }

    /**
     * Get the predio that was inspected.
     */
    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }

    /**
     * Get the veterinarian (user) who performed the inspection.
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    /**
     * Get the details of the inspection.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleInspeccion::class, 'inspeccion_id');
    }

    /**
     * Apply los filtros comunes de la Sábana de Excel a la consulta.
     */
    public function scopeApplySabanaFilters($query, ?string $zona = null, ?string $tipoActividad = null, ?string $medicoId = null): void
    {
        if ($medicoId) {
            $query->where('veterinario_id', $medicoId);
        }

        if ($tipoActividad) {
            if ($tipoActividad === 'Cuarentenas Definitivas') {
                $query->where(function ($q) {
                    $q->whereIn('inspecciones.motivo_prueba', ['Cuarentenas Definitivas', 'Definitiva', 'Cuarentena Definitiva'])
                      ->orWhere('inspecciones.motivo_prueba', 'like', '%Definitiva%')
                      ->orWhereHas('predio.productor', function ($pq) {
                          $pq->whereNotNull('clave_cuarentena')
                             ->where('clave_cuarentena', 'like', '%D%');
                      });
                });
            } elseif ($tipoActividad === 'Cuarentenas Precautorias') {
                $query->where(function ($q) {
                    $q->whereIn('inspecciones.motivo_prueba', ['Cuarentenas Precautorias', 'Precautoria', 'Cuarentena Precautoria'])
                      ->orWhere('inspecciones.motivo_prueba', 'like', '%Precautoria%')
                      ->orWhereHas('predio.productor', function ($pq) {
                          $pq->whereNotNull('clave_cuarentena')
                             ->where('clave_cuarentena', 'like', '%P%');
                      });
                });
            } elseif ($tipoActividad === 'Hatos Relacionados y Expuestos') {
                $query->where(function ($q) {
                    $q->whereIn('inspecciones.motivo_prueba', ['Hatos Relacionados y Expuestos', 'Hatos Relacionados', 'Relacionados', 'Expuestos'])
                      ->orWhere('inspecciones.motivo_prueba', 'like', '%Hatos%')
                      ->orWhere('inspecciones.motivo_prueba', 'like', '%Relacionados%');
                });
            } elseif ($tipoActividad === 'Seguimiento') {
                $query->where(function ($q) {
                    $q->whereIn('inspecciones.motivo_prueba', [
                        'Seguimiento',
                        'Cuarentenas Definitivas',
                        'Cuarentenas Precautorias',
                        'Hatos Relacionados y Expuestos',
                        'Hatos Relacionados',
                        'Definitiva',
                        'Precautoria',
                    ])
                    ->orWhere('inspecciones.motivo_prueba', 'like', '%Cuarentena%')
                    ->orWhere('inspecciones.motivo_prueba', 'like', '%Seguimiento%')
                    ->orWhere('inspecciones.motivo_prueba', 'like', '%Hatos Relacionados%')
                    ->orWhereHas('predio.productor', function ($pq) {
                        $pq->whereNotNull('clave_cuarentena')
                           ->where('clave_cuarentena', '!=', '');
                    });
                });
            } elseif ($tipoActividad === 'Buffer') {
                $query->where(function ($q) {
                    $q->where('inspecciones.motivo_prueba', 'like', '%Buffer%')
                      ->orWhere('inspecciones.tipo_prueba', 'like', '%Buffer%')
                      ->orWhere('inspecciones.tipo_inspeccion', 'like', '%Buffer%');
                });
            } elseif ($tipoActividad === 'Barrido') {
                $query->where(function ($q) {
                    $q->where('inspecciones.motivo_prueba', 'like', '%Barrido%')
                      ->orWhere('inspecciones.tipo_prueba', 'like', '%Barrido%')
                      ->orWhere('inspecciones.tipo_inspeccion', 'like', '%Barrido%');
                });
            } else {
                $query->where(function ($q) use ($tipoActividad) {
                    $q->where('inspecciones.motivo_prueba', $tipoActividad)
                      ->orWhere('inspecciones.tipo_prueba', $tipoActividad)
                      ->orWhere('inspecciones.tipo_inspeccion', $tipoActividad);
                });
            }
        }

        if ($zona) {
            $query->whereHas('predio.productor', function ($q) use ($zona) {
                $q->where('zona', $zona);
            });
        }
    }

    /**
     * Generate a sanitized PDF filename for this inspection.
     */
    public function buildPdfFilename(): string
    {
        $productor = $this->predio?->productor;
        $nombre = trim(($productor?->apellido_paterno ?? 'DICTAMEN').' '.($productor?->nombre ?? ''));
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre);
        $nombre = preg_replace('/[^A-Za-z0-9 _-]/', '', $nombre);
        $nombre = preg_replace('/\s+/', '_', trim($nombre));
        $nombre = strtoupper($nombre ?: 'DICTAMEN');
        $fecha = $this->fecha_inyeccion
            ? Carbon::parse($this->fecha_inyeccion)->format('d-m-Y')
            : now()->format('d-m-Y');

        return "DICTAMEN_{$nombre}_{$fecha}.pdf";
    }
}
