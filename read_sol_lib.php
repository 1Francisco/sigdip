<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx';

try {
    $reader = IOFactory::createReaderForFile($filePath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getSheetByName('10-SOL LIB');
    
    $rows = $sheet->rangeToArray("A1:M50", null, true, false, true);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }
