<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Productor extends Model
{
    use HasFactory;

    protected $table = 'productores';

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'upp',
        'curp',
        'domicilio',
        'municipio',
        'localidad',
        'estado',
        'telefono',
        'email',
        'medico_id',
        'zona',
        'clave_cuarentena',
    ];

    /**
     * Get all predios for the productor.
     */
    public function predios(): HasMany
    {
        return $this->hasMany(Predio::class);
    }

    /**
     * Aretes del censo para este productor.
     */
    public function aretesCenso(): HasMany
    {
        return $this->hasMany(AreteCenso::class);
    }

    /**
     * Get the medico (official MVZ) responsible for this producer.
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    /**
     * Get all visits through predios.
     */
    public function visitas(): HasManyThrough
    {
        return $this->hasManyThrough(Visita::class, Predio::class);
    }

    /**
     * Nombre completo legible para selectores y listados.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ], fn ($part) => filled($part))));
    }
}
