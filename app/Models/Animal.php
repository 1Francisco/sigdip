<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Animal extends Model
{
    use HasFactory;

    protected $table = 'animales';

    protected $fillable = [
        'numero_arete_siniiga',
        'edad',
        'raza',
        'sexo',
        'predio_id',
    ];

    /**
     * Get the predio that the animal belongs to.
     */
    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }

    /**
     * Get the inspection details for the animal.
     */
    public function detallesInspeccion(): HasMany
    {
        return $this->hasMany(DetalleInspeccion::class);
    }
}
