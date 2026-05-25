<?php
$zip = new ZipArchive;
if ($zip->open('C:\Users\franc\Downloads\AD-10-09 ELISEO ECHEVARRIA FLORES (1).xlsx') === TRUE) {
    $strings = $zip->getFromName('xl/sharedStrings.xml');
    $keywords = ['CURP', 'RPP', 'REGISTRO', 'PATRONAL', 'PECUARIO', 'IDENTIFICACION'];
    foreach ($keywords as $kw) {
        echo "$kw: " . (stripos($strings, $kw) !== false ? 'YES' : 'NO') . "\n";
    }
}
