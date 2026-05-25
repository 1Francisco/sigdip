<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx';

try {
    $reader = IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $results = [];

    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $sheetName = $sheet->getTitle();
        // Read only first 100 rows
        $highestCol = $sheet->getHighestColumn();
        $rows = $sheet->rangeToArray("A1:" . $highestCol . "100", null, true, false, true);
        
        foreach ($rows as $rowIdx => $row) {
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
