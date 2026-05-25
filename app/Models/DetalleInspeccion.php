<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleInspeccion extends Model
{
    use HasFactory;

    protected $table = 'detalles_inspeccion';

    protected $fillable = [
        'inspeccion_id',
        'animal_id',
        'tipo_arete',
        'edad_meses',
        'raza',
        'sexo',
        'fierro',
        'resultado_prueba',
        'observaciones_animal',
    ];

    /**
     * Get the inspection that this detail belongs to.
     */
    public function inspeccion(): BelongsTo
    {
        return $this->belongsTo(Inspeccion::class);
    }

    /**
     * Get the animal that was inspected in this detail.
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
