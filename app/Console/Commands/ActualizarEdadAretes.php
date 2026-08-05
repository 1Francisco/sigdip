<?php

namespace App\Console\Commands;

use App\Models\AreteCenso;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ActualizarEdadAretes extends Command
{
    protected $signature = 'aretes-censo:actualizar-edad';

    protected $description = 'Recalcula edad_meses de todos los aretes con fecha_nacimiento no nula';

    public function handle(): int
    {
        $aretes = AreteCenso::whereNotNull('fecha_nacimiento')->get();
        $count = 0;

        foreach ($aretes as $arete) {
            $edad = Carbon::parse($arete->fecha_nacimiento)->diffInMonths(Carbon::now());
            if ($arete->edad_meses !== $edad) {
                $arete->edad_meses = $edad;
                $arete->saveQuietly();
                $count++;
            }
        }

        $this->info("{$count} aretes actualizados (de {$aretes->count()} con fecha de nacimiento).");

        return Command::SUCCESS;
    }
}
