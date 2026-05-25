<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx';

try {
    $reader = IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    
    echo "Sheets: " . implode(', ', $spreadsheet->getSheetNames()) . "\n\n";

    foreach ($spreadsheet->getAllSheets() as $sheet) {
        echo "--- Sheet: " . $sheet->getTitle() . " ---\n";
        $rows = $sheet->rangeToArray("A1:AZ20", null, true, false, true);
        foreach ($rows as $rIdx => $row) {
            foreach ($row as $cKey => $val) {
                if (empty($val)) continue;
                $v = (string)$val;
                if (stripos($v, 'CURP') !== false || stripos($v, 'RPP') !== false || stripos($v, 'REGISTRO') !== false) {
                    echo "Found '$v' at $cKey$rIdx\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
