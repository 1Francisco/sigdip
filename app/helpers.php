<?php

use App\Models\Productor;
use Illuminate\Support\Str;

if (! function_exists('generarClaveInterna')) {
    function generarClaveInterna(?Productor $productor): string
    {
        $iniciales = '';

        if ($productor) {
            $nombre = trim($productor->nombre ?? '');
            $apellidoP = trim($productor->apellido_paterno ?? '');
            $apellidoM = trim($productor->apellido_materno ?? '');

            // Primera letra de cada palabra del nombre
            $partes = preg_split('/\s+/', $nombre);
            foreach ($partes as $parte) {
                if ($parte !== '') {
                    $iniciales .= mb_strtoupper(mb_substr($parte, 0, 1));
                }
            }

            // Primera letra de cada apellido
            if ($apellidoP !== '') {
                $iniciales .= mb_strtoupper(mb_substr($apellidoP, 0, 1));
            }
            if ($apellidoM !== '') {
                $iniciales .= mb_strtoupper(mb_substr($apellidoM, 0, 1));
            }
        }

        $random = strtoupper(Str::random(5));
        $fecha = now()->format('dmY');

        return $iniciales.'-'.$random.'-'.$fecha;
    }
}
