<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx';

try {
    $spreadsheet = IOFactory::load($filePath);
    $results = [];

    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $sheetName = $sheet->getTitle();
        $rows = $sheet->toArray(null, true, false, true);
        
        foreach ($rows as $rowIdx => $row) {
            if ($rowIdx > 100) break; // Check only first 100 rows
            foreach ($row as $colKey => $val) {
                if (empty($val)) continue;
                $v = (string)$val;
                if (stripos($v, 'CURP') !== false || stripos($v, 'RPP') !== false || stripos($v, 'REGISTRO') !== false) {
                    $results[] = [
                        'sheet' => $sheetName,
                        'cell' => $colKey . $rowIdx,
                        'value' => $v
                    ];
                }
            }
        }
    }

    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
