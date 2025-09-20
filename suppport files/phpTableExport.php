<?php
/**
 * Quran Verses Exporter
 * ---------------------
 * This script exports verses from the `verses_signs` table in MySQL
 * into a UTF-8 encoded CSV file.
 *
 * Key Features:
 *  - Connects to the MySQL database using PDO with utf8mb4 (full Unicode support).
 *  - Ensures database communication is UTF-8 (via SET NAMES).
 *  - Writes the CSV file in UTF-8 with BOM (so Excel shows Arabic correctly).
 *  - Automatically adds a timestamp to the output filename.
 *  - Converts any non-UTF-8 strings into UTF-8 to avoid mojibake (garbled text).
 *
 * Output example:
 *    verses_signs_export_2025-09-20_184530.csv
 */

// Database connection with UTF-8
$pdo = new PDO("mysql:host=localhost;dbname=pitppk_burj192;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure UTF-8 connection
$pdo->exec("SET NAMES utf8mb4");
$pdo->exec("SET CHARACTER SET utf8mb4");

// Query verses
$stmt = $pdo->query("
    SELECT id, sura_number, verse_number, verse_text_only, verse_with_signs
    FROM verses_signs
    ORDER BY sura_number, verse_number
");

// Build filename with timestamp
$timestamp = date("Y-m-d_His");  // e.g. 2025-09-20_184530
$filename = __DIR__ . "/verses_signs_export_$timestamp.csv";

// Open file
$fp = fopen($filename, "w");

// Write UTF-8 BOM (Excel compatibility) — remove if not needed
fwrite($fp, "\xEF\xBB\xBF");

// Write header row
fputcsv($fp, ["id", "sura_number", "verse_number", "verse_text_only", "verse_with_signs"]);

// Write data rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Ensure values are in UTF-8
    foreach ($row as &$value) {
        if (!mb_detect_encoding($value, 'UTF-8', true)) {
            $value = mb_convert_encoding($value, 'UTF-8');
        }
    }
    fputcsv($fp, $row);
}

fclose($fp);

echo "✅ Export complete: $filename\n";
?>
