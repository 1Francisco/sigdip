<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    $productores = App\Models\Productor::with('predios')->get();
    echo "Count: " . $productores->count() . "\n";
    echo "JSON: " . substr(json_encode($productores), 0, 100) . "...\n";
    
    $view = view('inspecciones.create', [
        'productores' => $productores,
        'visita' => null,
        'selected_productor_id' => null,
        'selected_predio_id' => null
    ])->render();
    
    echo "Rendered length: " . strlen($view) . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
