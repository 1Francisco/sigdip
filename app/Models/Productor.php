<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

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

            if (empty($productor->clave) && ! empty($productor->tipo_actividad)) {
                $generated = static::siguienteClave(
                    $productor->tipo_actividad,
                    $productor->sub_tipo_actividad,
                    $productor->zona,
                    $productor->medico_id
                );
                $productor->clave = $generated['clave'];
                if ($generated['zona']) {
                    $productor->zona = $generated['zona'];
                }
            }
        });
    }

    /**
     * Calcular la siguiente clave disponible según el tipo de actividad
     * (sin guardar). Barrido => BA-, Buffer => BFC-, Seguimiento => Zona + P/D.
     *
     * @return array{clave: string, zona: ?string}
     */
    public static function siguienteClave(
        string $tipoActividad,
        ?string $subTipoActividad = null,
        ?string $zona = null,
        ?int $medicoId = null
    ): array {
        $tipo = strtolower($tipoActividad);
        $prefix = 'GP';
        $efectivaZona = null;

        if ($tipo === 'barrido') {
            $prefix = 'BA';
        } elseif ($tipo === 'buffer') {
            $prefix = 'BFC';
        } elseif ($tipo === 'seguimiento') {
            $zone = strtoupper($zona ?? '');
            if (empty($zone) && $medicoId) {
                $medico = User::find($medicoId);
                if ($medico && $medico->zona) {
                    $zone = strtoupper($medico->zona);
                }
            }
            if ($zone !== 'A' && $zone !== 'B') {
                $zone = 'B';
            }
            $efectivaZona = $zone;

            $subType = strtolower($subTipoActividad ?? '');
            $typeChar = 'P';
            if (str_contains($subType, 'definitiva')) {
                $typeChar = 'D';
            }
            $prefix = $zone.$typeChar;
        }

        $count = static::where('clave', 'like', $prefix.'-%')->count();
        $number = $count + 1;
        do {
            $clave = $prefix.'-'.str_pad($number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (static::where('clave', $clave)->exists());

        return ['clave' => $clave, 'zona' => $efectivaZona];
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
     * Productores agrupados por clave (hato) en una sola consulta.
     */
    public static function vinculadosPorClave(): Collection
    {
        return static::whereNotNull('clave')
            ->get()
            ->groupBy('clave');
    }

    /**
     * Adjuntar el atributo 'vinculados' (mismos miembros del hato) a cada productor.
     *
     * @param  Collection  $productores
     * @return Collection
     */
    public static function attachVinculados($productores)
    {
        $grupos = static::vinculadosPorClave();

        return $productores->map(function (self $productor) use ($grupos) {
            $productor->setAttribute('vinculados', static::vinculadosArray($productor, $grupos));

            return $productor;
        });
    }

    /**
     * Lista ligera de los demás productores del mismo hato.
     *
     * @param  Collection  $grupos
     */
    private static function vinculadosArray(self $productor, $grupos): array
    {
        if (! filled($productor->clave)) {
            return [];
        }

        return collect($grupos[$productor->clave] ?? [])
            ->reject(fn (self $v) => $v->id === $productor->id)
            ->map(fn (self $v) => [
                'id' => $v->id,
                'nombre_completo' => $v->nombre_completo,
                'nombre' => $v->nombre,
                'apellido_paterno' => $v->apellido_paterno,
                'apellido_materno' => $v->apellido_materno,
                'upp' => $v->upp,
                'telefono' => $v->telefono,
                'clave' => $v->clave,
                'zona' => $v->zona,
            ])
            ->values()
            ->all();
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
