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
        'clave',
        'tipo_actividad',
        'sub_tipo_actividad',
    ];

    protected static function booted()
    {
        static::saving(function ($productor) {
            if ($productor->clave && ! $productor->zona) {
                $first = strtoupper($productor->clave)[0] ?? '';
                $productor->zona = in_array($first, ['A', 'B'], true) ? $first : null;
            }

            if (empty($productor->clave) && !empty($productor->tipo_actividad)) {
                $tipo = strtolower($productor->tipo_actividad);
                $prefix = 'GP';
                if ($tipo === 'barrido') {
                    $prefix = 'BF';
                } elseif ($tipo === 'buffer') {
                    $prefix = 'BFC';
                } elseif ($tipo === 'seguimiento') {
                    $zone = strtoupper($productor->zona ?? '');
                    if (empty($zone) && $productor->medico_id) {
                        $medico = \App\Models\User::find($productor->medico_id);
                        if ($medico && $medico->zona) {
                            $zone = strtoupper($medico->zona);
                            $productor->zona = $zone;
                        }
                    }
                    if ($zone !== 'A' && $zone !== 'B') {
                        $zone = 'B';
                        $productor->zona = $zone;
                    }
                    
                    $subType = strtolower($productor->sub_tipo_actividad ?? '');
                    $typeChar = 'P';
                    if (str_contains($subType, 'definitiva')) {
                        $typeChar = 'D';
                    }
                    $prefix = $zone . $typeChar;
                }

                $count = static::where('clave', 'like', $prefix . '-%')->count();
                $number = $count + 1;
                do {
                    $clave = $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
                    $number++;
                } while (static::where('clave', $clave)->exists());

                $productor->clave = $clave;
            }
        });
    }

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

    /**
     * Obtener la edad mínima de prueba en meses según la clave.
     */
    public function getEdadMinimaPruebaAttribute(): int
    {
        if ($this->clave) {
            $clave = strtoupper($this->clave);
            if (strlen($clave) >= 2 && $clave[1] === 'D') {
                return 2;
            }
        }

        return 6;
    }
}
