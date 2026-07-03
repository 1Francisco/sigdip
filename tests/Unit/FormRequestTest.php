<?php

namespace Tests\Unit;

use App\Http\Requests\StoreVisitaApiRequest;
use App\Http\Requests\SyncInspeccionRequest;
use App\Http\Requests\UpdateInspeccionApiRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_visita_api_request_rules()
    {
        $request = new StoreVisitaApiRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('codigo', $rules);
        $this->assertArrayHasKey('predio_id', $rules);
        $this->assertArrayHasKey('fecha_programada', $rules);
        $this->assertStringContainsString('required', $rules['predio_id']);
        $this->assertStringContainsString('required', $rules['fecha_programada']);
    }

    public function test_update_inspeccion_api_request_rules()
    {
        $request = new UpdateInspeccionApiRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('observaciones', $rules);
        $this->assertArrayHasKey('estado', $rules);
        $this->assertStringContainsString('nullable', $rules['observaciones']);
        $this->assertStringContainsString('in:borrador,sincronizado', $rules['estado']);
    }

    public function test_sync_inspeccion_request_rules()
    {
        $request = new SyncInspeccionRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('inspecciones', $rules);
        $this->assertArrayHasKey('inspecciones.*.predio_id', $rules);
        $this->assertArrayHasKey('inspecciones.*.estado', $rules);
        $this->assertArrayHasKey('inspecciones.*.detalles', $rules);
        $this->assertStringContainsString('required', $rules['inspecciones']);
        $this->assertStringContainsString('required', $rules['inspecciones.*.predio_id']);
    }
}
