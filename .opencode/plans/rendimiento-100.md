# Plan: Llevar Módulo Rendimiento al 100%

## Iteración 1 — Métricas de eficiencia + Ranking en tab Médicos

### 1.1 Controller — `app/Http/Controllers/ReportesRendimientoController.php`

**Modificar método `getMedicosRendimiento()`** (línea 337):

Después de `$visitasStats`, agregar query para estadísticas de animales/reactores por médico:

```php
$detallesStats = DetalleInspeccion::select(
        'inspecciones.veterinario_id',
        DB::raw('COUNT(*) as total_animales'),
        DB::raw("SUM(CASE WHEN detalles_inspeccion.resultado_prueba IN ('Positivo','Sospechoso') THEN 1 ELSE 0 END) as total_reactores")
    )
    ->join('inspecciones', 'detalles_inspeccion.inspeccion_id', '=', 'inspecciones.id')
    ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
    ->join('productores', 'predios.productor_id', '=', 'productores.id')
    ->when($fechaDesde, fn ($q) => $q->whereDate('inspecciones.fecha', '>=', $fechaDesde))
    ->when($fechaHasta, fn ($q) => $q->whereDate('inspecciones.fecha', '<=', $fechaHasta))
    ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
    ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
    ->groupBy('inspecciones.veterinario_id')
    ->get()
    ->keyBy('veterinario_id');
```

En el `map()`, pasar `$detallesStats` al closure y calcular:

```php
$dStats = $detallesStats->get($user->id);
$totalAnimales = $dStats?->total_animales ?? 0;
$totalReactores = $dStats?->total_reactores ?? 0;

$promedioAnimales = $totalInspecciones > 0 ? round($totalAnimales / $totalInspecciones, 1) : 0;
$tasaReactores = $totalAnimales > 0 ? round($totalReactores / $totalAnimales * 100, 1) : 0;
$tasaFinalizacion = $totalVisitas > 0 ? round($visitasCompletadas / $totalVisitas * 100, 1) : 0;

$score = (
    min($totalInspecciones / 50, 1) * 40 +
    min($promedioAnimales / 10, 1) * 30 +
    ($tasaFinalizacion / 100) * 30
);
```

Agregar al objeto retornado:
```php
'total_animales' => $totalAnimales,
'total_reactores' => $totalReactores,
'promedio_animales' => $promedioAnimales,
'tasa_reactores' => $tasaReactores,
'tasa_finalizacion' => $tasaFinalizacion,
'eficiencia_score' => round($score, 1),
```

Al final del método, reemplazar:
```php
return $medicosConDatos->sortByDesc('eficiencia_score')->values();
```

### 1.2 Vista — `resources/views/reportes/rendimiento.blade.php`

**Tabla (líneas 212-262):** Agregar columnas después de "Predios":

```blade
<th class="text-center">Animales</th>
<th class="text-center">Anim/Insp</th>
<th class="text-center">% Reactores</th>
<th class="text-center">% Finalización</th>
<th class="text-center">Eficiencia</th>
```

En el cuerpo del `@forelse`, agregar después de `<td>{{ $m->predios_atendidos }}</td>`:

```blade
<td class="text-center">{{ $m->total_animales }}</td>
<td class="text-center">{{ $m->promedio_animales }}</td>
<td class="text-center">
    @if($m->tasa_reactores > 0)
        <span class="badge bg-danger rounded-pill">{{ $m->tasa_reactores }}%</span>
    @else
        <span class="text-muted">0%</span>
    @endif
</td>
<td class="text-center">
    @if($m->total_visitas > 0)
        <div class="d-flex align-items-center justify-content-center gap-1">
            <div class="progress" style="height:6px;width:60px">
                <div class="progress-bar bg-success" style="width:{{ $m->tasa_finalizacion }}%"></div>
            </div>
            <small>{{ $m->tasa_finalizacion }}%</small>
        </div>
    @else
        <span class="text-muted">—</span>
    @endif
</td>
<td class="text-center">
    @php
        $efClass = $m->eficiencia_score >= 70 ? 'success' : ($m->eficiencia_score >= 40 ? 'warning text-dark' : 'danger');
        $efWidth = min($m->eficiencia_score, 100);
    @endphp
    <div class="d-flex align-items-center justify-content-center gap-1">
        <div class="progress" style="height:8px;width:60px">
            <div class="progress-bar bg-{{ $efClass }}" style="width:{{ $efWidth }}%"></div>
        </div>
        <small class="fw-bold text-{{ $efClass }}">{{ $m->eficiencia_score }}</small>
    </div>
</td>
```

Actualizar `colspan` de la fila vacía de `7` a `11`:
```blade
<td colspan="11" class="text-center text-muted py-4">
```

**Gráfico Chart.js (línea 593):** Cambiar colores del dataset para usar colores basados en eficiencia:

```javascript
const eficienciaData = @json($medicosRendimiento->map(fn($m) => $m->eficiencia_score));
new Chart(document.getElementById('chartMedicos'), {
    type: 'bar',
    data: {
        labels: medicosData.map(m => m.name),
        datasets: [{
            label: 'Inspecciones',
            data: medicosData.map(m => m.total),
            backgroundColor: medicosData.map((m, i) => {
                const ef = eficienciaData[i];
                return ef >= 70 ? '#16a34a' : (ef >= 40 ? '#f59e0b' : '#dc2626');
            }),
            borderRadius: 8,
            barThickness: 35
        }]
    },
    ...
});
```

---

## Iteración 2 — Filtro rápido de año + Localidad

### 2.1 Controller — `app/Http/Controllers/ReportesRendimientoController.php`

**Método `index()`** (línea 20): Agregar después de `$medicoId = $request->get('medico_id')`:

```php
$year = $request->get('year');
$localidad = $request->get('localidad');
```

Agregar años disponibles:
```php
$years = Inspeccion::selectRaw(($driver === 'sqlite' ? "strftime('%Y', fecha)" : "YEAR(fecha)") . ' as year')
    ->distinct()->orderBy('year', 'desc')->pluck('year');
if ($years->isEmpty()) {
    $years = collect([now()->year]);
}
```

Agregar localidades disponibles:
```php
$localidades = Predio::select('localidad')->distinct()->orderBy('localidad')->pluck('localidad');
```

Pasar al compact:
```php
return view('reportes.rendimiento', compact(
    ...
    'year',
    'localidad',
    'years',
    'localidades',
    ...
));
```

**Método `applyCommonFilters()`** (línea 524): Agregar filtros:

```php
if ($localidad) {
    $query->where('predios.localidad', $localidad);
}
```

También agregar en `getKpis()`, `getMedicosRendimiento()`, `getActividadesData()`, `getZonasData()`, `getCuarentenasData()`, `getMesesData()` el parámetro `$localidad` y pasarlo a los queries.

**Actualizar `getKpis()`** para aceptar y usar `$localidad`.
**Actualizar `getKpisCached()`** para incluir `$localidad` en la clave de cache.
**Actualizar `filteredInspecciones()`** para aceptar y usar `$localidad`.

### 2.2 Vista — `resources/views/reportes/rendimiento.blade.php`

En la barra de filtros (al lado de "Zona"), agregar:

```blade
<div class="col-md-2 col-sm-4">
    <label class="form-label small fw-semibold text-secondary mb-1">Año</label>
    <select name="year" class="form-select form-select-sm">
        <option value="">Todos</option>
        @foreach($years as $y)
            <option value="{{ $y }}" @selected((int) $year === (int) $y)>{{ $y }}</option>
        @endforeach
    </select>
</div>
```

Después del filtro de médico, agregar:

```blade
<div class="col-md-2 col-sm-4">
    <label class="form-label small fw-semibold text-secondary mb-1">Localidad</label>
    <select name="localidad" class="form-select form-select-sm">
        <option value="">Todas</option>
        @foreach($localidades as $loc)
            <option value="{{ $loc }}" @selected($localidad === $loc)>{{ $loc }}</option>
        @endforeach
    </select>
</div>
```

---

## Iteración 3 — Comparativa año contra año (YoY) en tab Mes

### 3.1 Controller — `app/Http/Controllers/ReportesRendimientoController.php`

**Modificar `getMesesData()`** (línea 492): Agregar parámetro `$localidad`. Además de los datos actuales, obtener datos del año anterior:

```php
private function getMesesData($fechaDesde, $fechaHasta, $estado, $zona, $medicoId, $localidad)
{
    $dateExpr = $this->dateExprMonth();
    $driver = DB::connection()->getDriverName();
    $yearExpr = $driver === 'sqlite' ? "strftime('%Y', inspecciones.fecha)" : "YEAR(inspecciones.fecha)";

    $currentYear = $year ?: date('Y');
    $prevYear = $currentYear - 1;

    $queryActual = $this->buildMesesQuery($currentYear, $estado, $zona, $medicoId, $localidad);
    $queryAnterior = $this->buildMesesQuery($prevYear, $estado, $zona, $medicoId, $localidad);

    $mesesActual = $queryActual->get()->keyBy('mes');
    $mesesAnterior = $queryAnterior->get()->keyBy('mes');

    $todosLosMeses = collect();
    for ($m = 1; $m <= 12; $m++) {
        $key = sprintf('%s-%02d', $currentYear, $m);
        $prevKey = sprintf('%s-%02d', $prevYear, $m);
        $actual = $mesesActual->get($key);
        $anterior = $mesesAnterior->get($prevKey);

        $todosLosMeses->push((object) [
            'mes' => $key,
            'total' => $actual?->total ?? 0,
            'total_anterior' => $anterior?->total ?? 0,
        ]);
    }

    return $todosLosMeses;
}

private function buildMesesQuery($year, $estado, $zona, $medicoId, $localidad)
{
    $dateExpr = $this->dateExprMonth();

    return Inspeccion::join('predios', 'inspecciones.predio_id', '=', 'predios.id')
        ->join('productores', 'predios.productor_id', '=', 'productores.id')
        ->select(DB::raw("{$dateExpr} as mes"), DB::raw('COUNT(*) as total'))
        ->whereYear('inspecciones.fecha', $year)
        ->groupBy(DB::raw($dateExpr))
        ->orderBy('mes')
        ->when($estado, fn ($q) => $q->where('inspecciones.estado', $estado))
        ->when($zona, fn ($q) => $q->where('productores.zona', $zona))
        ->when($medicoId, fn ($q) => $q->where('inspecciones.veterinario_id', $medicoId))
        ->when($localidad, fn ($q) => $q->where('predios.localidad', $localidad));
}
```

### 3.2 Vista — `resources/views/reportes/rendimiento.blade.php`

En el tab "Mes", agregar columnas:

```blade
<th class="text-center">Año Anterior</th>
<th class="text-center">Var. YoY</th>
```

En el cuerpo:

```blade
<td class="text-center">
    @if($mes->total_anterior > 0)
        <span class="badge bg-secondary rounded-pill">{{ $mes->total_anterior }}</span>
    @else
        <span class="text-muted">—</span>
    @endif
</td>
<td class="text-center">
    @php
        $yoy = null;
        if ($mes->total_anterior > 0) {
            $yoy = round(($mes->total - $mes->total_anterior) / $mes->total_anterior * 100, 1);
        }
    @endphp
    @if(!is_null($yoy))
        <span class="badge {{ $yoy >= 0 ? 'bg-success' : 'bg-danger' }} rounded-pill">
            <i class="bi {{ $yoy >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
            {{ $yoy >= 0 ? '+' : '' }}{{ $yoy }}%
        </span>
    @else
        <span class="text-muted small">—</span>
    @endif
</td>
```

---

## Iteración 4 — Desglose por tipo_prueba en Detalle Mensual

### 4.1 Controller — `app/Http/Controllers/ReportesRendimientoController.php`

**Método `rendimientoMensual()`** (línea 105): Modificar la query principal para incluir desglose:

```php
$rows = Inspeccion::select(
    'inspecciones.veterinario_id',
    'inspecciones.tipo_prueba',
    DB::raw("{$monthExpr} as mes"),
    DB::raw('COUNT(*) as total_inspecciones'),
    DB::raw('COUNT(DISTINCT inspecciones.predio_id) as predios')
)
    ->join('predios', 'inspecciones.predio_id', '=', 'predios.id')
    ->join('productores', 'predios.productor_id', '=', 'productores.id')
    ->whereYear('inspecciones.fecha', $year)
    ->groupBy('inspecciones.veterinario_id', 'inspecciones.tipo_prueba', DB::raw($monthExpr))
    ...
    ->get();
```

Después de obtener `$rows`, agrupar y pivotear:

```php
$grouped = collect();
foreach ($rows as $row) {
    $key = $row->veterinario_id . '|' . $row->mes;
    if (!$grouped->has($key)) {
        $row->ppc = 0;
        $row->pcc = 0;
        $grouped[$key] = $row;
    }
    $tipo = in_array($row->tipo_prueba, ['P.P.C.', 'PPC']) ? 'ppc' : 'pcc';
    $grouped[$key]->{$tipo} = $row->total_inspecciones;
}
$rows = $grouped->values();
```

En el `DetalleInspeccion` query, agregar también desglose:
```php
DB::raw("COALESCE(SUM(CASE WHEN detalles_inspeccion.resultado_prueba IN ('Positivo','Sospechoso') AND inspecciones.tipo_prueba IN ('P.P.C.','PPC') THEN 1 ELSE 0 END), 0) as reactores_ppc"),
DB::raw("COALESCE(SUM(CASE WHEN detalles_inspeccion.resultado_prueba IN ('Positivo','Sospechoso') AND inspecciones.tipo_prueba = 'PCC' THEN 1 ELSE 0 END), 0) as reactores_pcc"),
```

### 4.2 Vista — `resources/views/reportes/rendimiento_mensual.blade.php`

Agregar columnas en la tabla (líneas 156-165):
```blade
<th class="text-center col-num">PPC</th>
<th class="text-center col-num">PCC</th>
<th class="text-center col-num">React. PPC</th>
<th class="text-center col-num">React. PCC</th>
```

En el cuerpo, después de `<td>total_inspecciones`:
```blade
<td class="text-center">{{ $row->ppc ?? 0 }}</td>
<td class="text-center">{{ $row->pcc ?? 0 }}</td>
<td class="text-center">
    @if(($row->reactores_ppc ?? 0) > 0)
        <span class="badge bg-danger rounded-pill">{{ $row->reactores_ppc }}</span>
    @else
        <span class="text-muted">0</span>
    @endif
</td>
<td class="text-center">
    @if(($row->reactores_pcc ?? 0) > 0)
        <span class="badge bg-danger rounded-pill">{{ $row->reactores_pcc }}</span>
    @else
        <span class="text-muted">0</span>
    @endif
</td>
```

### 4.3 Export — `app/Exports/RendimientoMensualExport.php`

Agregar columnas PPC, PCC, Reactores PPC, Reactores PCC en `collection()`, `headings()`, y `map()`.

---

## Iteración 5 — Cache mejorado + invalidación

### 5.1 Controller — `app/Http/Controllers/ReportesRendimientoController.php`

En `getKpisCached()`, cambiar `300` a `1800`:
```php
return Cache::remember($cacheKey, 1800, function () use (...) { ... });
```

### 5.2 Observer — Crear `app/Observers/InspeccionObserver.php`

```php
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
        Cache::flush(); // o más específico si el driver soporta tags
    }
}
```

### 5.3 Provider — `app/Providers/AppServiceProvider.php`

En `boot()`:
```php
use App\Models\Inspeccion;
use App\Observers\InspeccionObserver;

// ...
public function boot(): void
{
    Inspeccion::observe(InspeccionObserver::class);
}
```

---

## Iteración 6 — PDF Mensual + Tests

### 6.1 Controller — `app/Http/Controllers/ReportesRendimientoController.php`

Agregar método:
```php
public function exportPdfMensual(Request $request)
{
    $year = $request->get('year', (int) now()->year);
    $medicoId = $request->get('medico_id');
    $zona = $request->get('zona');
    $estado = $request->get('estado');

    $medicos = User::role('Medico_Campo')->orderBy('name')->get();
    $data = $this->getMensualData($year, $medicoId, $zona, $estado);

    $pdf = Pdf::loadView('reports.rendimiento_mensual_pdf', compact('data', 'year', 'medicos'));

    return $pdf->download("rendimiento_mensual_{$year}.pdf");
}
```

### 6.2 Vista — Crear `resources/views/reports/rendimiento_mensual_pdf.blade.php`

Template simple con tabla de rendimiento mensual (estilo similar a `rendimiento_reporte_pdf` pero con los datos mensuales).

### 6.3 Route — `routes/web.php`

```php
Route::get('/admin/reportes/rendimiento/mensual/pdf', [ReportesRendimientoController::class, 'exportPdfMensual'])
    ->name('reportes.rendimiento.mensual.pdf');
```

### 6.4 Vista mensual — `resources/views/reportes/rendimiento_mensual.blade.php`

Agregar botón PDF junto al de Excel:
```blade
<a href="{{ route('reportes.rendimiento.mensual.pdf', request()->query()) }}"
   class="btn btn-sm btn-danger rounded-pill px-3">
    <i class="bi bi-file-earmark-pdf me-1"></i> Exportar PDF
</a>
```

### 6.5 Tests — Crear `tests/Feature/ReportesRendimientoMensualPdfTest.php`

```php
class ReportesRendimientoMensualPdfTest extends TestCase
{
    use RefreshDatabase;

    // test_admin_can_download_mensual_pdf
    // test_mensual_pdf_with_data
    // test_mensual_pdf_empty_state
    // test_medico_campo_cannot_download_mensual_pdf
}
```

### 6.6 Tests complementarios

En `tests/Feature/ReportesRendimientoTest.php`, agregar:
- `test_rendimiento_shows_efficiency_columns` — verifica que las columnas de eficiencia aparecen
- `test_rendimiento_ranking_ordered_by_score` — verifica que el ranking está ordenado por score
- `test_rendimiento_filtro_localidad` — verifica que el filtro de localidad funciona
- `test_rendimiento_filtro_year` — verifica que el filtro de año funciona

En `tests/Feature/ReportesRendimientoMensualTest.php`, agregar:
- `test_mensual_shows_ppc_pcc_columns` — verifica que las columnas PPC/PCC aparecen

Crear `tests/Feature/Observers/InspeccionObserverCacheTest.php`:
- `test_creating_inspeccion_clears_rendimiento_cache`
- `test_updating_inspeccion_clears_rendimiento_cache`

---

## Iteración 7 — App móvil dashboard mejorado

### 7.1 API — `app/Http/Controllers/Api/DashboardApiController.php`

En el endpoint `stats()`, agregar al response del admin:

```php
'rendimientoDetalle' => User::role('Medico_Campo')
    ->withCount(['inspecciones as total_inspecciones' => function ($q) {
        // aplicar filtros...
    }])
    ->get()
    ->map(fn ($u) => [
        'name' => $u->name,
        'total_inspecciones' => $u->total_inspecciones,
        'eficiencia_score' => 0, // calcular simplificado
    ]),
'rendimientoKpis' => [
    'total_inspecciones' => $totalInspecciones,
    'total_animales' => $totalAnimales,
    'total_medicos_activos' => $medicosActivos,
    'tasa_reactores' => $tasaReactores,
],
```

### 7.2 Mobile — `mobile_app/src/views/DashboardView.vue`

En la sección admin, después del doughnut chart, agregar:

```vue
<!-- KPIs -->
<div v-if="adminStats.rendimientoKpis" class="row g-2 mb-3">
    <div class="col-3">
        <div class="card p-2 text-center bg-primary text-white">
            <small class="text-white-50">Inspecciones</small>
            <strong>{{ adminStats.rendimientoKpis.total_inspecciones }}</strong>
        </div>
    </div>
    <div class="col-3">
        <div class="card p-2 text-center bg-success text-white">
            <small class="text-white-50">Animales</small>
            <strong>{{ adminStats.rendimientoKpis.total_animales }}</strong>
        </div>
    </div>
    <div class="col-3">
        <div class="card p-2 text-center bg-info text-white">
            <small class="text-white-50">Activos</small>
            <strong>{{ adminStats.rendimientoKpis.total_medicos_activos }}</strong>
        </div>
    </div>
    <div class="col-3">
        <div class="card p-2 text-center bg-danger text-white">
            <small class="text-white-50">% React.</small>
            <strong>{{ adminStats.rendimientoKpis.tasa_reactores }}%</strong>
        </div>
    </div>
</div>

<!-- Tabla médicos -->
<div v-if="adminStats.rendimientoDetalle?.length" class="card border-0 shadow-sm">
    <div class="card-header fw-bold">Médicos</div>
    <div class="list-group list-group-flush">
        <div v-for="m in adminStats.rendimientoDetalle" class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ m.name }}</span>
            <span class="badge bg-primary rounded-pill">{{ m.total_inspecciones }}</span>
        </div>
    </div>
</div>
```

Actualizar el seed de Cypress `dashboard-stats.json` para incluir `rendimientoDetalle` y `rendimientoKpis`.
