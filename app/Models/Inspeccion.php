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
