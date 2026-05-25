<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx';

try {
    $reader = IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    
    $sheetsToCheck = ['SINIIGA', 'ARETES', 'INI', 'SUP', '10-SOL LIB'];
    
    foreach ($sheetsToCheck as $sName) {
        $sheet = $spreadsheet->getSheetByName($sName);
        if (!$sheet) continue;
        echo "Checking $sName...\n";
        $rows = $sheet->rangeToArray("A1:AZ50", null, true, false, true);
        foreach ($rows as $rIdx => $row) {
            foreach ($row as $cKey => $val) {
                if (empty($val)) continue;
                $v = (string)$val;
                if (stripos($v, 'CURP') !== false || stripos($v, 'RPP') !== false) {
                    echo "Found '$v' at $sName!$cKey$rIdx\n";
                }
            }
        }
    }
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }
