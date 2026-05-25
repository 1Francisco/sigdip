<?php
$zip = new ZipArchive;
if ($zip->open('C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx') === TRUE) {
    $strings = $zip->getFromName('xl/sharedStrings.xml');
    if ($strings) {
        // Buscar CURP con Regex
        if (preg_match_all('/[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}/i', $strings, $matches)) {
            echo "FOUND CURPS: " . implode(', ', array_slice(array_unique($matches[0]), 0, 10)) . "...\n";
        } else {
            echo "NO CURPS FOUND IN STRINGS\n";
        }
        
        // Buscar RPP (intentar patrones comunes)
        if (stripos($strings, 'RPP') !== false) echo "STRING 'RPP' FOUND\n";
        if (stripos($strings, 'REGISTRO') !== false) echo "STRING 'REGISTRO' FOUND\n";
    }
    $zip->close();
} else {
    echo "Could not open Zip\n";
}
