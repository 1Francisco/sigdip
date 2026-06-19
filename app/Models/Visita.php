<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visita extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'predio_id',
        'veterinario_id',
        'fecha_programada',
        'estado',
        'inyeccion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'inyeccion' => 'boolean',
    ];

    /**
     * Get the predio to be visited.
     */
    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }

    /**
     * Get the veterinarian assigned to the visit.
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    /**
     * Get the inspection draft associated with this visit.
     */
    public function inspeccion(): HasOne
    {
        return $this->hasOne(Inspeccion::class);
    }
}
