<?php

namespace App\Observers;

use App\Models\Inspeccion;
use Illuminate\Support\Facades\Cache;

class InspeccionObserver
{
    public function created(Inspeccion $inspeccion): void
    {
        $this->clearRendimientoCache();
    }

    public function updated(Inspeccion $inspeccion): void
    {
        $this->clearRendimientoCache();
    }

    public function deleted(Inspeccion $inspeccion): void
    {
        $this->clearRendimientoCache();
    }

    private function clearRendimientoCache(): void
    {
        Cache::flush();
    }
}
