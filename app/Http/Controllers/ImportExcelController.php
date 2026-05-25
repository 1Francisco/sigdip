<?php

namespace App\Http\Controllers;

use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\Productor;
use App\Models\AreteCenso;
use App\Models\Predio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ImportExcelController extends Controller
{
    /**
     * Muestra la pantalla de importación
     */
    public function index()
    {
        $medicos = User::role('Medico_Campo')->get();
        $importaciones = AreteCenso::select('archivo_origen')
            ->selectRaw('COUNT(*) as total_aretes')
            ->selectRaw('MAX(created_at) as fecha_importacion')
            ->groupBy('archivo_origen')
            ->orderByDesc('fecha_importacion')
            ->get();

        return view('admin.import-excel', compact('medicos', 'importaciones'));
    }

    /**
     * Previsualiza las pestañas y columnas del Excel
     */
    public function preview(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:xlsx,xls,zip',
            ]);

            // Aumentar límites para archivos grandes
            ini_set('memory_limit', '1G');
            set_time_limit(180);

            $file = $request->file('archivo');
            $extension = $file->getClientOriginalExtension();

            if ($extension === 'zip') {
                $zip = new \ZipArchive();
                $excelFiles = [];
                if ($zip->open($file->getPathname()) === TRUE) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (preg_match('/\.(xlsx|xls)$/i', $filename)) {
                            $excelFiles[] = $filename;
                        }
                    }
                    $zip->close();
                }

                if (empty($excelFiles)) {
                    throw new \Exception("El archivo ZIP no contiene archivos Excel válidos (.xlsx o .xls).");
                }

                // --- Obtener muestra de los primeros 2 archivos ---
                $tempPath = storage_path('app/temp_preview_' . time());
                $previewFiles = array_slice($excelFiles, 0, 2); // Primeros 2 archivos
                $allSamples = [];

                if ($zip->open($file->getPathname()) === TRUE) {
                    if (!File::exists($tempPath)) File::makeDirectory($tempPath, 0777, true);
                    $zip->extractTo($tempPath, $previewFiles);
                    $zip->close();

                    foreach ($previewFiles as $previewFile) {
                        $fullPath = $tempPath . '/' . $previewFile;
                        if (!file_exists($fullPath)) continue;

                        $reader = IOFactory::createReaderForFile($fullPath);
                        $reader->setReadDataOnly(true);
                        $spreadsheet = $reader->load($fullPath);
                        $sampleData = [];

                        foreach ($spreadsheet->getSheetNames() as $sheetName) {
                            $upperName = strtoupper(trim($sheetName));
                            if ($upperName === 'BASE' || $upperName === 'ARETE' || $upperName === 'ARETES') {
                                $sheet = $spreadsheet->getSheetByName($sheetName);
                                $tempData = [];
                                $totalEstimado = $sheet->getHighestDataRow() - 1; // Total estimado (sin header)
                                foreach ($sheet->getRowIterator(1, min(50, $sheet->getHighestDataRow())) as $row) {
                                    $cellIterator = $row->getCellIterator();
                                    $cellIterator->setIterateOnlyExistingCells(false);
                                    $rowData = [];
                                    foreach ($cellIterator as $cell) {
                                        try { $val = $cell->getCalculatedValue(); } catch (\Exception $e) { $val = $cell->getValue(); }
                                        $rowData[] = $val;
                                    }
                                    if (array_filter($rowData)) $tempData[] = $rowData;
                                }
                                if (empty($tempData)) continue;

                                $headersRaw = $tempData[0];

                                if ($upperName === 'BASE') {
                                    $headers = array_map(function($h) { return strtoupper(trim($h ?? '---')); }, $headersRaw);
                                    $headers[] = 'PREDIO_DETECTADO';

                                    // Encontrar columnas clave
                                    $prodColIdx = -1;
                                    $uppColIdx = -1;
                                    foreach ($headers as $idx => $h) {
                                        if ($prodColIdx === -1 && (strpos($h, 'PRODUCTOR') !== false || strpos($h, 'NOMBRE') !== false || strpos($h, 'INTERESADO') !== false)) {
                                            $prodColIdx = $idx;
                                        }
                                        if ($uppColIdx === -1 && (strpos($h, 'U.P.P') !== false || strpos($h, 'UPP') !== false || strpos($h, 'CLAVE DE U') !== false)) {
                                            $uppColIdx = $idx;
                                        }
                                    }

                                    $finalRows = [];
                                    $totalReal = 0;
                                    for ($i = 1; $i < count($tempData); $i++) {
                                        $row = $tempData[$i];
                                        $rowStr = strtoupper(implode(' ', array_map('strval', array_filter($row))));

                                        if (strpos($rowStr, 'PREDIO:') !== false || strpos($rowStr, 'RANCHO:') !== false || strpos($rowStr, 'TOTAL') !== false || strpos($rowStr, 'FIRMA') !== false) continue;

                                        if ($prodColIdx !== -1) {
                                            $prodVal = trim((string)($row[$prodColIdx] ?? ''));
                                            if (empty($prodVal) || strlen($prodVal) < 3 || !preg_match('/[A-Za-z]/', $prodVal)) continue;
                                        }

                                        // Detectar predio
                                        $predio = '';
                                        if (isset($tempData[$i + 1])) {
                                            $nextRow = $tempData[$i + 1];
                                            foreach ($nextRow as $ci => $val) {
                                                $v = strtoupper(trim((string)($val ?? '')));
                                                if (strpos($v, 'PREDIO:') !== false || strpos($v, 'RANCHO:') !== false) {
                                                    $nombre = trim(str_ireplace(['PREDIO:', 'RANCHO:'], '', $v));
                                                    if (empty($nombre) && isset($nextRow[$ci + 1])) {
                                                        $nombre = trim((string)($nextRow[$ci + 1] ?? ''));
                                                    }
                                                    $predio = $nombre;
                                                    break;
                                                }
                                            }
                                        }

                                        $rowValues = array_map(function($v) { return $v ?? ''; }, array_values($row));
                                        while (count($rowValues) < count($headers) - 1) { $rowValues[] = ''; }
                                        $rowValues[] = $predio;

                                        // Dividir productores separados por "/"
                                        $prodName = $prodColIdx !== -1 ? trim((string)($row[$prodColIdx] ?? '')) : '';
                                        if (strpos($prodName, '/') !== false && $prodColIdx !== -1) {
                                            $names = array_map('trim', explode('/', $prodName));
                                            $uppVal = $uppColIdx !== -1 ? trim((string)($row[$uppColIdx] ?? '')) : '';
                                            $upps = strpos($uppVal, '/') !== false ? array_map('trim', explode('/', $uppVal)) : (strpos($uppVal, "\n") !== false ? array_map('trim', explode("\n", $uppVal)) : [$uppVal]);

                                            foreach ($names as $k => $name) {
                                                if (empty($name) || strlen($name) < 3) continue;
                                                $subRow = $rowValues;
                                                $subRow[$prodColIdx] = $name;
                                                if ($uppColIdx !== -1) {
                                                    $subRow[$uppColIdx] = trim($upps[$k] ?? $upps[0] ?? '');
                                                }
                                                $totalReal++;
                                                if (count($finalRows) < 15) {
                                                    $finalRows[] = $subRow;
                                                }
                                            }
                                        } else {
                                            $totalReal++;
                                            if (count($finalRows) < 15) {
                                                $finalRows[] = $rowValues;
                                            }
                                        }
                                    }
                                } else {
                                    $headersLower = array_map(function($h) { return strtolower(trim($h ?? '')); }, $headersRaw);
                                    $areteMapping = [
                                        'ARETE' => ['arete', 'siniiga', 'numero'],
                                        'RAZA' => ['raza'],
                                        'SEXO' => ['sexo'],
                                        'NAC' => ['nac', 'nacimiento', 'f. nac', 'fecha nac', 'fecha_nac'],
                                        'SAC?' => ['sac?', 'sac', 'sacrificio', 'f_sac', 'destino'],
                                    ];
                                    $areteIndices = [];
                                    foreach ($areteMapping as $label => $patterns) {
                                        foreach ($headersLower as $idx => $h) {
                                            foreach ($patterns as $p) {
                                                if (strpos($h, $p) !== false) { $areteIndices[$label] = $idx; break 2; }
                                            }
                                        }
                                    }

                                    $headers = ['ARETE', 'EDAD', 'RAZA', 'SEXO', 'NAC', 'SAC?'];
                                    $finalRows = [];
                                    $totalReal = 0;
                                    for ($i = 1; $i < count($tempData); $i++) {
                                        $row = $tempData[$i];
                                        $arete = isset($areteIndices['ARETE']) ? trim((string)($row[$areteIndices['ARETE']] ?? '')) : '';
                                        if (empty($arete)) continue;

                                        $totalReal++;
                                        if (count($finalRows) >= 10) continue;

                                        $nacRaw = isset($areteIndices['NAC']) ? ($row[$areteIndices['NAC']] ?? '') : '';
                                        $nacFormatted = $nacRaw;
                                        $edad = '---';
                                        try {
                                            if ($nacRaw) {
                                                if (is_numeric($nacRaw)) {
                                                    $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($nacRaw));
                                                } else {
                                                    $date = Carbon::parse($nacRaw);
                                                }
                                                $edad = $date->diffInMonths(Carbon::now()) . ' m';
                                                $nacFormatted = $date->format('d/m/Y');
                                            }
                                        } catch (\Exception $e) {}

                                        $finalRows[] = [
                                            $arete,
                                            $edad,
                                            isset($areteIndices['RAZA']) ? trim((string)($row[$areteIndices['RAZA']] ?? '')) : '',
                                            isset($areteIndices['SEXO']) ? trim((string)($row[$areteIndices['SEXO']] ?? '')) : '',
                                            $nacFormatted,
                                            isset($areteIndices['SAC?']) ? trim((string)($row[$areteIndices['SAC?']] ?? '')) : '',
                                        ];
                                    }
                                }

                                if (!empty($finalRows)) {
                                    // --- NUEVO: Eliminar columnas completamente vacías en la muestra ---
                                    $nonEmptyIndices = [];
                                    foreach ($headers as $idx => $h) {
                                        $hasData = false;
                                        // Siempre mantener columnas críticas o con algún dato
                                        $hUpper = strtoupper($h);
                                        if (strpos($hUpper, 'PRODUCTOR') !== false || strpos($hUpper, 'ARETE') !== false || strpos($hUpper, 'UPP') !== false || strpos($hUpper, 'PREDIO') !== false) {
                                            $hasData = true;
                                        } else {
                                            foreach ($finalRows as $row) {
                                                $val = trim((string)($row[$idx] ?? ''));
                                                if (!empty($val) && $val !== '---' && $val !== '0') {
                                                    $hasData = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if ($hasData) $nonEmptyIndices[] = $idx;
                                    }

                                    // Filtrar headers y filas
                                    $headers = array_values(array_intersect_key($headers, array_flip($nonEmptyIndices)));
                                    $finalRows = array_map(function($row) use ($nonEmptyIndices) {
                                        return array_values(array_intersect_key($row, array_flip($nonEmptyIndices)));
                                    }, $finalRows);

                                    $sampleData[$sheetName] = [
                                        'headers' => $headers,
                                        'rows' => $finalRows,
                                        'total' => $totalReal
                                    ];
                                }
                            }
                        }

                        $allSamples[basename($previewFile)] = $sampleData;
                        $spreadsheet->disconnectWorksheets();
                        unset($spreadsheet, $sampleData, $tempData);
                    }
                    File::deleteDirectory($tempPath);
                }

                return response()->json([
                    'status' => 'success',
                    'is_zip' => true,
                    'archivo' => $file->getClientOriginalName(),
                    'file_count' => count($excelFiles),
                    'files' => $excelFiles,
                    'samples' => $allSamples,
                    'message' => 'Se detectó un archivo ZIP con ' . count($excelFiles) . ' archivos. Mostrando muestra de los primeros ' . count($previewFiles) . '.'
                ]);
            }
            
            // Cargar rápido (Solo datos) para archivos Excel individuales
            $reader = IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());
            
            $results = [];
            $allSheetNames = $spreadsheet->getSheetNames();
            
            foreach ($allSheetNames as $sheetName) {
                $upperName = strtoupper(trim($sheetName));
                if ($upperName === 'BASE' || $upperName === 'ARETE' || $upperName === 'ARETES') {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    $tempData = [];
                    $totalEstimado = $sheet->getHighestDataRow() - 1; // Total estimado (sin header)
                    foreach ($sheet->getRowIterator(1, min(100, $sheet->getHighestDataRow())) as $rowIndex => $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);
                        $rowData = [];
                        foreach ($cellIterator as $cell) {
                            try { $val = $cell->getCalculatedValue(); } catch (\Exception $e) { $val = $cell->getValue(); }
                            $rowData[] = $val;
                        }
                        if (array_filter($rowData)) $tempData[] = $rowData;
                    }

                    if (empty($tempData)) continue;

                    $headersRaw = $tempData[0];

                    if ($upperName === 'BASE') {
                        // BASE: Mostrar TODAS las columnas originales + PREDIO_DETECTADO
                        $headers = array_map(function($h) { return strtoupper(trim($h ?? '---')); }, $headersRaw);
                        $headers[] = 'PREDIO_DETECTADO';

                        // Encontrar columnas clave
                        $prodColIdx = -1;
                        $uppColIdx = -1;
                        foreach ($headers as $idx => $h) {
                            if ($prodColIdx === -1 && (strpos($h, 'PRODUCTOR') !== false || strpos($h, 'NOMBRE') !== false || strpos($h, 'INTERESADO') !== false)) {
                                $prodColIdx = $idx;
                            }
                            if ($uppColIdx === -1 && (strpos($h, 'U.P.P') !== false || strpos($h, 'UPP') !== false || strpos($h, 'CLAVE DE U') !== false)) {
                                $uppColIdx = $idx;
                            }
                        }

                        $finalRows = [];
                        $totalReal = 0;
                        for ($i = 1; $i < count($tempData); $i++) {
                            $row = $tempData[$i];
                            $rowStr = strtoupper(implode(' ', array_map('strval', array_filter($row))));

                            // Saltar filas de info/resumen
                            if (strpos($rowStr, 'PREDIO:') !== false || strpos($rowStr, 'RANCHO:') !== false || strpos($rowStr, 'TOTAL') !== false || strpos($rowStr, 'FIRMA') !== false) continue;

                            // Solo mostrar filas con un nombre de productor válido (con letras)
                            if ($prodColIdx !== -1) {
                                $prodVal = trim((string)($row[$prodColIdx] ?? ''));
                                if (empty($prodVal) || strlen($prodVal) < 3 || !preg_match('/[A-Za-z]/', $prodVal)) continue;
                            }

                            // Detectar predio
                            $predio = '';
                            if (isset($tempData[$i + 1])) {
                                $nextRow = $tempData[$i + 1];
                                foreach ($nextRow as $ci => $val) {
                                    $v = strtoupper(trim((string)($val ?? '')));
                                    if (strpos($v, 'PREDIO:') !== false || strpos($v, 'RANCHO:') !== false) {
                                        // Extraer nombre: puede estar en la misma celda o en la siguiente
                                        $nombre = trim(str_ireplace(['PREDIO:', 'RANCHO:'], '', $v));
                                        if (empty($nombre) && isset($nextRow[$ci + 1])) {
                                            $nombre = trim((string)($nextRow[$ci + 1] ?? ''));
                                        }
                                        $predio = $nombre;
                                        break;
                                    }
                                }
                            }

                            $rowValues = array_map(function($v) { return $v ?? ''; }, array_values($row));
                            while (count($rowValues) < count($headers) - 1) { $rowValues[] = ''; }
                            $rowValues[] = $predio;

                            // Dividir productores separados por "/"
                            $prodName = $prodColIdx !== -1 ? trim((string)($row[$prodColIdx] ?? '')) : '';
                            if (strpos($prodName, '/') !== false && $prodColIdx !== -1) {
                                $names = array_map('trim', explode('/', $prodName));
                                $uppVal = $uppColIdx !== -1 ? trim((string)($row[$uppColIdx] ?? '')) : '';
                                $upps = strpos($uppVal, '/') !== false ? array_map('trim', explode('/', $uppVal)) : (strpos($uppVal, "\n") !== false ? array_map('trim', explode("\n", $uppVal)) : [$uppVal]);

                                foreach ($names as $k => $name) {
                                    if (empty($name) || strlen($name) < 3) continue;
                                    $subRow = $rowValues;
                                    $subRow[$prodColIdx] = $name;
                                    if ($uppColIdx !== -1) {
                                        $subRow[$uppColIdx] = trim($upps[$k] ?? $upps[0] ?? '');
                                    }
                                    $totalReal++;
                                    if (count($finalRows) < 25) {
                                        $finalRows[] = $subRow;
                                    }
                                }
                            } else {
                                $totalReal++;
                                if (count($finalRows) < 25) {
                                    $finalRows[] = $rowValues;
                                }
                            }
                        }
                    } else {
                        // ARETES: Mapear las 6 columnas exactas
                        $headersLower = array_map(function($h) { return strtolower(trim($h ?? '')); }, $headersRaw);
                        $areteMapping = [
                            'ARETE' => ['arete', 'siniiga', 'numero'],
                            'RAZA' => ['raza'],
                            'SEXO' => ['sexo'],
                            'NAC' => ['nac', 'nacimiento', 'f. nac', 'fecha nac', 'fecha_nac'],
                            'SAC?' => ['sac?', 'sac', 'sacrificio', 'f_sac', 'destino'],
                        ];
                        $areteIndices = [];
                        foreach ($areteMapping as $label => $patterns) {
                            foreach ($headersLower as $idx => $h) {
                                foreach ($patterns as $p) {
                                    if (strpos($h, $p) !== false) { $areteIndices[$label] = $idx; break 2; }
                                }
                            }
                        }

                        $headers = ['ARETE', 'EDAD', 'RAZA', 'SEXO', 'NAC', 'SAC?'];
                        $finalRows = [];
                        $totalReal = 0;
                        for ($i = 1; $i < count($tempData); $i++) {
                            $row = $tempData[$i];
                            $arete = isset($areteIndices['ARETE']) ? trim((string)($row[$areteIndices['ARETE']] ?? '')) : '';
                            if (empty($arete)) continue;

                            $totalReal++;
                            if (count($finalRows) >= 25) continue;

                            $nacRaw = isset($areteIndices['NAC']) ? ($row[$areteIndices['NAC']] ?? '') : '';
                            $nacFormatted = $nacRaw;
                            $edad = '---';
                            try {
                                if ($nacRaw) {
                                    if (is_numeric($nacRaw)) {
                                        $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($nacRaw));
                                    } else {
                                        $date = Carbon::parse($nacRaw);
                                    }
                                    $edad = $date->diffInMonths(Carbon::now()) . ' m';
                                    $nacFormatted = $date->format('d/m/Y');
                                }
                            } catch (\Exception $e) {}

                            $finalRows[] = [
                                $arete,
                                $edad,
                                isset($areteIndices['RAZA']) ? trim((string)($row[$areteIndices['RAZA']] ?? '')) : '',
                                isset($areteIndices['SEXO']) ? trim((string)($row[$areteIndices['SEXO']] ?? '')) : '',
                                $nacFormatted,
                                isset($areteIndices['SAC?']) ? trim((string)($row[$areteIndices['SAC?']] ?? '')) : '',
                            ];
                        }
                    }

                    if (!empty($finalRows)) {
                        // --- NUEVO: Eliminar columnas completamente vacías en la muestra ---
                        $nonEmptyIndices = [];
                        foreach ($headers as $idx => $h) {
                            $hasData = false;
                            $hUpper = strtoupper($h);
                            if (strpos($hUpper, 'PRODUCTOR') !== false || strpos($hUpper, 'ARETE') !== false || strpos($hUpper, 'UPP') !== false || strpos($hUpper, 'PREDIO') !== false) {
                                $hasData = true;
                            } else {
                                foreach ($finalRows as $row) {
                                    $val = trim((string)($row[$idx] ?? ''));
                                    if (!empty($val) && $val !== '---' && $val !== '0') {
                                        $hasData = true;
                                        break;
                                    }
                                }
                            }
                            if ($hasData) $nonEmptyIndices[] = $idx;
                        }

                        // Filtrar headers y filas
                        $headers = array_values(array_intersect_key($headers, array_flip($nonEmptyIndices)));
                        $finalRows = array_map(function($row) use ($nonEmptyIndices) {
                            return array_values(array_intersect_key($row, array_flip($nonEmptyIndices)));
                        }, $finalRows);

                        $results[$sheetName] = [
                            'headers' => $headers,
                            'rows' => $finalRows,
                            'total' => $totalReal
                        ];
                    }
                }
            }
            return response()->json([
                'status' => 'success',
                'archivo' => $file->getClientOriginalName(),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Procesa e importa los datos del Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,zip',
            'medico_id' => 'nullable|exists:users,id',
            'zona' => 'nullable|in:A,B',
        ]);

        $file = $request->file('archivo');
        $medicoId = $request->medico_id;
        $zona = $request->zona;

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        $totalProductores = 0;
        $totalAretes = 0;
        $archivosProcesados = 0;
        $errores = [];

        try {
            if ($file->getClientOriginalExtension() === 'zip') {
                $zip = new ZipArchive;
                $tempPath = storage_path('app/temp_import_' . time());
                
                if ($zip->open($file->getPathname()) === TRUE) {
                    $zip->extractTo($tempPath);
                    $zip->close();

                    $files = File::allFiles($tempPath);
                    foreach ($files as $f) {
                        if (in_array(strtolower($f->getExtension()), ['xlsx', 'xls'])) {
                            try {
                                $res = $this->processExcelFile($f->getPathname(), $f->getFilename(), $medicoId, $zona);
                                $totalProductores += $res['productores'];
                                $totalAretes += $res['aretes'];
                                $archivosProcesados++;
                                
                                // Liberar memoria después de cada archivo
                                if (isset($res['spreadsheet'])) {
                                    $res['spreadsheet']->disconnectWorksheets();
                                    unset($res['spreadsheet']);
                                }
                                gc_collect_cycles();
                            } catch (\Exception $e) {
                                $errores[] = "Error en {$f->getFilename()}: " . $e->getMessage();
                            }
                        }
                    }
                    File::deleteDirectory($tempPath);
                } else {
                    return back()->with('error', 'No se pudo abrir el archivo ZIP.');
                }
            } else {
                $res = $this->processExcelFile($file->getPathname(), $file->getClientOriginalName(), $medicoId, $zona);
                $totalProductores = $res['productores'];
                $totalAretes = $res['aretes'];
                $archivosProcesados = 1;
            }

            $msg = "Importación completada: {$archivosProcesados} archivos procesados. Total: {$totalProductores} productores y {$totalAretes} aretes.";
            if (count($errores) > 0) {
                $msg .= " (Se encontraron algunos errores: " . implode(', ', $errores) . ")";
            }

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    /**
     * Procesa un único archivo Excel
     */
    private function processExcelFile(string $filePath, string $nombreArchivo, $medicoId = null, $zona = null)
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

            // ===== DETECTAR PESTAÑAS =====
            $allSheetNames = $spreadsheet->getSheetNames();
            $sheetBase = null;
            $sheetAretes = null;

            foreach ($allSheetNames as $name) {
                $upper = strtoupper(trim($name));
                if ($upper === 'BASE') $sheetBase = $spreadsheet->getSheetByName($name);
                if ($upper === 'ARETE' || $upper === 'ARETES') $sheetAretes = $spreadsheet->getSheetByName($name);
            }

            if (!$sheetBase) throw new \Exception("No se encontró la pestaña 'BASE'");
            if (!$sheetAretes) throw new \Exception("No se encontró la pestaña de aretes ('ARETE' o 'ARETES')");

            // ===== PROCESAR PESTAÑA BASE =====
            $dataBase = $sheetBase->toArray(null, true, false, true);
            $rowsBase = array_values($dataBase);
            $mappedRaw = $this->aiMapColumns($rowsBase, [
                'nombre' => ['nombre', 'productor', 'interesado'],
                'curp' => ['curp'],
                'upp' => ['upp', 'u.p.p.', 'clave_upp', 'rpp', 'registro patronal', 'registro pecuario'],
                'telefono' => ['telefono', 'tel', 'celular'],
                'domicilio' => ['domicilio', 'direccion', 'calle'],
                'municipio' => ['municipio'],
                'localidad' => ['localidad'],
                'cuarentena' => ['cuarentena', 'clave_cuarentena'],
                'predio_nombre' => ['predio', 'rancho', 'nombre_predio'],
            ]) ?? $this->smartMapColumns($rowsBase, [
                'nombre' => ['nombre', 'productor', 'interesado'],
                'curp' => ['curp'],
                'upp' => ['upp', 'u.p.p.', 'clave_upp', 'rpp', 'registro patronal', 'registro pecuario'],
                'telefono' => ['telefono', 'tel', 'celular'],
                'domicilio' => ['domicilio', 'direccion', 'calle'],
                'municipio' => ['municipio'],
                'localidad' => ['localidad'],
                'cuarentena' => ['cuarentena', 'clave_cuarentena'],
                'predio_nombre' => ['predio', 'rancho', 'nombre_predio'],
            ]);

            // Garantizar unicidad de columnas
            $colMap = [];
            $usedIdxBase = [];
            foreach ($mappedRaw as $mKey => $mIdx) {
                if ($mIdx !== null && !in_array($mIdx, $usedIdxBase)) {
                    $colMap[$mKey] = $mIdx;
                    $usedIdxBase[] = $mIdx;
                } else {
                    $colMap[$mKey] = null;
                }
            }

            DB::beginTransaction();

            $productoresCreados = 0;
            $aretesCreados = 0;
            $claveCuarentenaGlobal = null;

            // Extraer datos usando un índice numérico para mirar adelante
            $productorIds = [];

            for ($i = 1; $i < count($rowsBase); $i++) {
                $originalRow = $rowsBase[$i];
                $rowStr = strtoupper(implode(' ', array_map('strval', array_filter($originalRow))));

                // Saltar filas de basura (mismo filtro que la vista previa)
                if (strpos($rowStr, 'PREDIO:') !== false || strpos($rowStr, 'RANCHO:') !== false || strpos($rowStr, 'TOTAL') !== false || strpos($rowStr, 'FIRMA') !== false) {
                    if (strpos($rowStr, 'TOTAL') !== false) break;
                    continue;
                }

                if ($colMap['nombre']) {
                    $prodVal = trim((string)($originalRow[$colMap['nombre']] ?? ''));
                    if (empty($prodVal) || strlen($prodVal) < 3 || !preg_match('/[A-Z]/', strtoupper($prodVal))) continue;
                }

                // Separar productores y UPPs
                $subRows = $this->explodeRow($originalRow, [
                    'productor' => $colMap['nombre'],
                    'upp' => $colMap['upp']
                ]);

                 foreach ($subRows as $subIdx => $row) {
                    $nombreVal = trim((string)($row[$colMap['nombre']] ?? ''));
                    $uppIdxImport = $colMap['upp'] ?? null;
                    $uppVal = ($uppIdxImport !== null) ? trim((string)($row[$uppIdxImport] ?? '')) : '';
                    $nombreUpper = strtoupper($nombreVal);
                     if (strpos($nombreUpper, 'TOTAL') !== false || strpos($nombreUpper, 'FIRMA') !== false) break 2;

                    // Solo procesar si tiene nombre (con letras) y UPP
                    if (empty($nombreVal) || empty($uppVal) || strlen($nombreVal) < 3 || !preg_match('/[A-Z]/', $nombreUpper)) continue;

                    // Detectar Predio (Estructura vertical o columna dedicada)
                    $predioNombre = '';
                    $predioIdx = $colMap['predio_nombre'] ?? -1;

                    // 1. Intentar obtener de columna mapeada de predio en la misma fila
                    if ($predioIdx !== -1 && !empty($row[$predioIdx])) {
                        $predioNombre = trim($row[$predioIdx]);
                    }

                    // 2. Si no hay, buscar en la SIGUIENTE fila (estructura vertical) o en TODAS las columnas
                    if (empty($predioNombre) && isset($rowsBase[$i+1])) {
                        $nextRow = $rowsBase[$i+1];
                        $cols = array_keys($nextRow);
                        foreach ($cols as $idx => $colKey) {
                            $cVal = strtoupper(trim($nextRow[$colKey] ?? ''));
                            if (strpos($cVal, 'PREDIO:') !== false || strpos($cVal, 'RANCHO:') !== false) {
                                if (strlen($cVal) > 8) {
                                    $predioNombre = trim(str_ireplace(['PREDIO:', 'RANCHO:'], '', $cVal));
                                } else {
                                    $nextColKey = $cols[$idx + 1] ?? null;
                                    if ($nextColKey) {
                                        $predioNombre = trim($nextRow[$nextColKey] ?? '');
                                    }
                                }
                                break;
                            }
                        }
                    }

                    if ($colMap['cuarentena'] && !empty($row[$colMap['cuarentena']])) {
                        $claveCuarentenaGlobal = trim($row[$colMap['cuarentena']]);
                    }

                    $curpVal = $colMap['curp'] ? trim($row[$colMap['curp']] ?? '') : null;

                    $split = $this->splitName($nombreVal);

                    // Lógica de búsqueda inteligente para evitar duplicados y "limpiar" nombres viejos
                    $productor = null;
                    
                    // 1. Intentar por CURP (si existe)
                    if (!empty($curpVal)) {
                        $productor = Productor::where('curp', $curpVal)->first();
                    }

                    // 2. Si no hay CURP o no se encontró, intentar por Nombre + Apellidos ya separados
                    if (!$productor) {
                        $productor = Productor::where('nombre', $split['nombre'])
                            ->where('apellido_paterno', $split['apellido_paterno'])
                            ->where('apellido_materno', $split['apellido_materno'])
                            ->first();
                    }

                    // 3. Si sigue sin aparecer, buscar por el nombre "amontonado" (limpieza de importaciones previas)
                    if (!$productor) {
                        $fullNameRaw = trim($nombreVal);
                        $productor = Productor::where('nombre', $fullNameRaw)
                            ->whereNull('apellido_paterno')
                            ->first();
                    }

                    // Crear o actualizar con los datos limpios
                        if ($productor) {
                            $productor->update([
                                'nombre' => $split['nombre'],
                                'apellido_paterno' => $split['apellido_paterno'],
                                'apellido_materno' => $split['apellido_materno'],
                                'curp' => $curpVal,
                                'upp' => $uppVal,
                                'telefono' => $colMap['telefono'] ? trim($row[$colMap['telefono']] ?? '') : $productor->telefono,
                                'domicilio' => $colMap['domicilio'] ? trim($row[$colMap['domicilio']] ?? '') : $productor->domicilio,
                                'municipio' => $colMap['municipio'] ? trim($row[$colMap['municipio']] ?? '') : $productor->municipio,
                                'localidad' => $colMap['localidad'] ? trim($row[$colMap['localidad']] ?? '') : $productor->localidad,
                                'clave_cuarentena' => $claveCuarentenaGlobal,
                            ]);
                        } else {
                            $productor = Productor::create([
                                'nombre' => $split['nombre'],
                                'apellido_paterno' => $split['apellido_paterno'],
                                'apellido_materno' => $split['apellido_materno'],
                                'curp' => $curpVal,
                                'upp' => $uppVal,
                                'telefono' => $colMap['telefono'] ? trim($row[$colMap['telefono']] ?? '') : '',
                                'domicilio' => $colMap['domicilio'] ? trim($row[$colMap['domicilio']] ?? '') : '',
                                'municipio' => $colMap['municipio'] ? trim($row[$colMap['municipio']] ?? '') : '',
                                'localidad' => $colMap['localidad'] ? trim($row[$colMap['localidad']] ?? '') : '',
                                'medico_id' => $medicoId,
                                'zona' => $zona,
                                'clave_cuarentena' => $claveCuarentenaGlobal,
                            ]);
                        }

                    $claveUpp = $colMap['upp'] ? trim($row[$colMap['upp']] ?? '') : '';
                    $predio = Predio::updateOrCreate(
                        ['clave_unidad_produccion' => $claveUpp ?: 'UPP-' . $productor->id],
                        [
                            'nombre_rancho' => $predioNombre ?: 'Rancho de ' . $nombreVal,
                            'productor_id' => $productor->id,
                            'localidad' => $colMap['localidad'] ? trim($row[$colMap['localidad']] ?? '') : 'CONOCIDO',
                            'municipio' => $colMap['municipio'] ? trim($row[$colMap['municipio']] ?? '') : '',
                        ]
                    );

                    $productorIds[] = [
                        'productor_id' => $productor->id,
                        'predio_id' => $predio->id,
                    ];
                    $productoresCreados++;
                }
            }

            // ===== PROCESAR PESTAÑA ARETES =====
            $dataAretes = $sheetAretes->toArray(null, true, false, true);
            $headersAretes = array_map('strtolower', array_map('trim', $dataAretes[1] ?? []));

            $colAretesRaw = $this->aiMapColumns(array_values($dataAretes), [
                'arete' => ['arete', 'siniiga', 'numero', 'identificador'],
                'raza' => ['raza'],
                'sexo' => ['sexo'],
                'nacimiento' => ['fecha nac', 'f. nac', 'nacimiento', 'fecha_nac'],
                'sacrificio' => ['sac?', 'f_sac', 'sacrificado', 'destino', 'sacrificio'],
            ]) ?? $this->smartMapColumns(array_values($dataAretes), [
                'arete' => ['arete', 'siniiga', 'numero', 'identificador'],
                'raza' => ['raza'],
                'sexo' => ['sexo'],
                'nacimiento' => ['fecha nac', 'f. nac', 'nacimiento', 'fecha_nac'],
                'sacrificio' => ['sac?', 'f_sac', 'sacrificado', 'destino', 'sacrificio'],
            ]);

            // Garantizar unicidad de columnas para aretes
            $colAretes = [];
            $usedIdxAretes = [];
            foreach ($colAretesRaw as $mKey => $mIdx) {
                if ($mIdx !== null && !in_array($mIdx, $usedIdxAretes)) {
                    $colAretes[$mKey] = $mIdx;
                    $usedIdxAretes[] = $mIdx;
                } else {
                    $colAretes[$mKey] = null;
                }
            }

            // Usar el primer productor/predio como referencia si solo hay uno
            $defaultProductor = $productorIds[0] ?? ['productor_id' => null, 'predio_id' => null];

            for ($i = 2; $i <= count($dataAretes); $i++) {
                $row = $dataAretes[$i] ?? [];

                $arete = $colAretes['arete'] ? trim($row[$colAretes['arete']] ?? '') : '';
                if (empty($arete)) continue;

                $nacimiento = null;
                $edadMeses = null;
                if ($colAretes['nacimiento'] && !empty($row[$colAretes['nacimiento']])) {
                    $nacRaw = $row[$colAretes['nacimiento']];
                    try {
                        if (is_numeric($nacRaw)) {
                            $nacimiento = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($nacRaw));
                        } else {
                            $nacimiento = Carbon::parse($nacRaw);
                        }
                        $edadMeses = $nacimiento->diffInMonths(Carbon::now());
                    } catch (\Exception $e) {
                        $nacimiento = null;
                    }
                }

                AreteCenso::updateOrCreate(
                    ['numero_arete' => $arete],
                    [
                        'productor_id' => $defaultProductor['productor_id'],
                        'predio_id' => $defaultProductor['predio_id'],
                        'raza' => $colAretes['raza'] ? trim($row[$colAretes['raza']] ?? '') : null,
                        'sexo' => $colAretes['sexo'] ? trim($row[$colAretes['sexo']] ?? '') : null,
                        'fecha_nacimiento' => $nacimiento,
                        'edad_meses' => $edadMeses,
                        'sacrificio' => (function() use ($colAretes, $row) {
                            if (!$colAretes['sacrificio']) return null;
                            $val = trim((string)($row[$colAretes['sacrificio']] ?? ''));
                            $vUpper = strtoupper($val);
                            if (!empty($val) && !in_array($vUpper, ['0', 'NO', '-', '.', 'FALSE'])) {
                                return 'SI';
                            }
                            return null;
                        })(),
                        'archivo_origen' => $nombreArchivo,
                    ]
                );
                $aretesCreados++;
            }

            DB::commit();

            return [
                'productores' => $productoresCreados,
                'aretes' => $aretesCreados,
                'spreadsheet' => $spreadsheet
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mapeo de columnas usando Inteligencia Artificial (Gemini API)
     */
    private function aiMapColumns(array $rows, array $mappings): ?array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) return null;

        $sample = array_slice($rows, 0, 6);
        $sampleJson = json_encode($sample);

        $prompt = "Analiza este fragmento de Excel. Identifica los índices de columna (0, 1, 2...) para: " . implode(', ', array_keys($mappings)) . ". 
        Estructura esperada: {\"mapping\": {\"campo\": indice, ...}, \"structure\": \"standard\"|\"vertical_predio\"}";

        try {
            $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt . "\n\nDatos: " . $sampleJson]]]]
            ]);

            if ($response->successful()) {
                $text = preg_replace('/```json\s*|\s*```/', '', $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '');
                $result = json_decode(trim($text), true);
                if (isset($result['mapping'])) {
                    session(['excel_structure' => $result['structure'] ?? 'standard']);
                    return $result['mapping'];
                }
            }
        } catch (\Exception $e) { \Log::error("Gemini Error: " . $e->getMessage()); }
        return null;
    }

    /**
     * Mapeo inteligente de columnas (Heurístico / Respaldo)
     */
    private function smartMapColumns(array $rows, array $mappings): array
    {
        $headers = array_map(function($h) { return strtolower(trim($h ?? '')); }, $rows[0] ?? []);
        $sampleData = array_slice($rows, 1, 10);
        $result = [];

        foreach ($mappings as $key => $patterns) {
            $scores = [];
            foreach ($headers as $colIdx => $headerName) {
                $score = 0;
                foreach ($patterns as $pattern) {
                    if (stripos($headerName, $pattern) !== false) $score += 30; // Peso alto a cabeceras
                }
                if (empty($headerName)) $score -= 20; // Penalizar fuertemente columnas sin nombre
                
                foreach ($sampleData as $rowData) {
                    $val = trim((string)($rowData[$colIdx] ?? ''));
                    if (empty($val)) continue;
                    $valUpper = strtoupper($val);
                    switch ($key) {
                        case 'curp': if (preg_match('/^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}$/i', $val)) $score += 5; break;
                        case 'upp': if (strpos($val, '18') === 0) $score += 8; break;
                        case 'arete': if (is_numeric($val) && strlen($val) >= 9) $score += 5; break;
                        case 'sexo': if (in_array($valUpper, ['H', 'M', 'HEMBRA', 'MACHO'])) $score += 5; break;
                        case 'predio': if (strpos($valUpper, 'PREDIO:') !== false || strpos($valUpper, 'RANCHO:') !== false) $score += 7; break;
                        case 'nacimiento': 
                            if (is_numeric($val) && $val > 30000 && $val < 60000) $score += 4;
                            break;
                        case 'sacrificio':
                            if (in_array($valUpper, ['X', 'SI', 'S', '1', 'YES'])) $score += 2;
                            break;
                    }
                }
                $scores[$colIdx] = $score;
            }
            arsort($scores);
            $bestIdx = key($scores);
            $result[$key] = ($scores[$bestIdx] ?? 0) > 0 ? $bestIdx : null;
        }
        return $result;
    }

    /**
     * Divide una fila en múltiples filas si contiene varios productores o UPPs
     */
    private function explodeRow(array $row, array $colMap)
    {
        $prodIdx = $colMap['productor'] ?? $colMap['nombre'] ?? -1;
        $uppIdx = $colMap['upp'] ?? -1;

        if ($prodIdx === -1) return [$row];

        $prodVal = (string)($row[$prodIdx] ?? '');
        $uppVal = $uppIdx !== -1 ? (string)($row[$uppIdx] ?? '') : '';

        // Si no hay saltos de línea ni separadores, devolver la fila original
        if (strpos($prodVal, "\n") === false && strpos($prodVal, "/") === false && strpos($uppVal, "\n") === false) {
            return [$row];
        }

        // Dividir productores (pueden venir con \n o /)
        $productores = preg_split('/[\n\r\/]+/', $prodVal);
        $productores = array_filter(array_map('trim', $productores));

        // Dividir UPPs (pueden venir con \n, / o espacios)
        $upps = preg_split('/[\n\r\/]+/', $uppVal);
        $upps = array_filter(array_map('trim', $upps));

        if (count($productores) <= 1 && count($upps) <= 1) {
            return [$row];
        }

        $count = max(count($productores), count($upps));
        $exploded = [];

         for ($i = 0; $i < $count; $i++) {
            $newRow = $row;
            $newRow[$prodIdx] = $productores[$i] ?? '';
            
            if ($uppIdx !== -1) {
                $newRow[$uppIdx] = $upps[$i] ?? '';
            }
            $exploded[] = $newRow;
        }

        return $exploded;
    }

    /**
     * Intenta separar un nombre completo en nombre, apellido paterno y apellido materno
     */
    private function splitName(string $fullName)
    {
        $parts = array_filter(explode(' ', trim($fullName)));
        $parts = array_values($parts);
        $count = count($parts);

        $result = [
            'nombre' => $fullName,
            'apellido_paterno' => '',
            'apellido_materno' => ''
        ];

        if ($count === 0) return $result;

        if ($count === 2) {
            $result['nombre'] = $parts[0];
            $result['apellido_paterno'] = $parts[1];
        } elseif ($count === 3) {
            $result['nombre'] = $parts[0];
            $result['apellido_paterno'] = $parts[1];
            $result['apellido_materno'] = $parts[2];
        } elseif ($count >= 4) {
            $result['apellido_materno'] = array_pop($parts);
            $result['apellido_paterno'] = array_pop($parts);
            $result['nombre'] = implode(' ', $parts);
        }

        return $result;
    }
}
