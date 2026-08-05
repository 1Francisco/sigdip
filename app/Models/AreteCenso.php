<?php

namespace App\Models;

use Carbon\Carbon;
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

    protected static function booted(): void
    {
        static::saving(function (self $arete) {
            if ($arete->fecha_nacimiento && $arete->isDirty('fecha_nacimiento')) {
                $arete->edad_meses = Carbon::parse($arete->fecha_nacimiento)->diffInMonths(Carbon::now());
            }
        });
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class);
    }

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }
}
