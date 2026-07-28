<?php

namespace App\Console\Commands;

use App\Models\Productor;
use Illuminate\Console\Command;

class LimpiarZonaProductoresSinClave extends Command
{
    protected $signature = 'productores:limpiar-zona-sin-clave';

    protected $description = 'Pone zona = null a productores sin clave';

    public function handle()
    {
        $count = Productor::whereNull('clave')
            ->whereNotNull('zona')
            ->update(['zona' => null]);

        $this->info("Se limpiaron {$count} productores (zona = null).");
    }
}
