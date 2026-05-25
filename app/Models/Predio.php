<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Predio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_rancho',
        'clave_unidad_produccion',
        'latitud',
        'longitud',
        'domicilio',
        'municipio',
        'localidad',
        'productor_id',
    ];

    /**
     * Get the productor that owns the predio.
     */
    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class);
    }

    /**
     * Get all animals in the predio.
     */
    public function animales(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    /**
     * Get all inspections for the predio.
     */
    public function inspecciones(): HasMany
    {
        return $this->hasMany(Inspeccion::class);
    }
}
