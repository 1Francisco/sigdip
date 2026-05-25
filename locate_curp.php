<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx';

try {
    $reader = IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $sheetName = $sheet->getTitle();
        $rows = $sheet->rangeToArray("A1:AZ100", null, true, false, true);
        foreach ($rows as $rIdx => $row) {
            foreach ($row as $cKey => $val) {
                if (empty($val)) continue;
                if (stripos((string)$val, 'CURP') !== false) {
                    echo "Found '" . $val . "' at " . $sheetName . "!" . $cKey . $rIdx . "\n";
                }
            }
        }
    }
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }
