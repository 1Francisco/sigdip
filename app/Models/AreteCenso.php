<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreteCenso extends Model
{
    protected $table = 'aretes_censo';

    protected $fillable = [
        'numero_arete', 'productor_id', 'predio_id',
        'raza', 'sexo', 'fecha_nacimiento', 'edad_meses',
        'sacrificio', 'archivo_origen'
    ];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }

    public function predio()
    {
        return $this->belongsTo(Predio::class);
    }
}
