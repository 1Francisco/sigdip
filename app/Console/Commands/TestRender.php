<?php

namespace App\Console\Commands;

use App\Models\Productor;
use Illuminate\Console\Command;

class TestRender extends Command
{
    protected $signature = 'test:render';

    protected $description = 'Test rendering the create blade template';

    public function handle()
    {
        try {
            $productores = Productor::with('predios')->get();
            $view = view('inspecciones.create', [
                'productores' => $productores,
                'visita' => null,
                'selected_productor_id' => null,
                'selected_predio_id' => null,
            ])->render();
            $this->info('Rendered length: '.strlen($view));
        } catch (\Throwable $e) {
            $this->error('ERROR: '.$e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
