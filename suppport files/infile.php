<?php
$pdo = new PDO("mysql:host=localhost;dbname=pitppk_burj192;charset=utf8mb4", "root", "");

// open CSV
if (($handle = fopen("C:/xampp/mysql/data/pitppk_burj192/verses_signs1.csv", "r")) !== FALSE) {
    $row = 0;
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        if ($row++ == 0) continue; // skip header

        [$id, $sura_number, $verse_number, $verse_text_only, $verse_with_signs] = $data;

        $stmt = $pdo->prepare("INSERT INTO verses_signs 
            (id, sura_number, verse_number, verse_text_only, verse_with_signs)
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $sura_number, $verse_number, $verse_text_only, $verse_with_signs]);
    }
    fclose($handle);
}
?>
