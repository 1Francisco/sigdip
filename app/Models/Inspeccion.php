<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_inyeccion' => 'date',
        'fecha_lectura' => 'date',
        'vigencia_fecha' => 'date',
        'fecha_prueba_anterior' => 'date',
        'exencion_fecha' => 'date',
        'hato_libre_fecha' => 'date',
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
}
