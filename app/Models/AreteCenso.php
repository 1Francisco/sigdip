<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreteCenso extends Model
{
    use HasFactory;

    protected $table = 'aretes_censo';

    protected $fillable = [
        'numero_arete', 'productor_id', 'predio_id',
        'raza', 'sexo', 'fecha_nacimiento', 'edad_meses',
        'sacrificio', 'archivo_origen',
    ];

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class);
    }

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }
}
