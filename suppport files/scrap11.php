<?php
require 'vendor/autoload.php'; // run: composer require phpoffice/phpspreadsheet:^1.28

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');
ignore_user_abort(true);


// -------------------------
// Mapping <span> → Unicode
// -------------------------
function convertSpansToUnicode($html) {
    $map = [
        '&nbsp;۞' => '۞', // Ruku
        '&nbsp;۩' => '۩', // Sajdah
        '&nbsp;۝' => '۝', // Verse ending
        '&nbsp;ۗ' => 'ۗ',
        '&nbsp;ۚ' => 'ۚ',
        '&nbsp;ۛ' => 'ۛ',
        '&nbsp;ۖ' => 'ۖ',
        '&nbsp;ۙ' => 'ۙ',
        '&nbsp;ۘ' => 'ۘ',
        '&nbsp;ۜ' => 'ۜ',
    ];
    $unicode = $html;
    $unicode = str_replace(array_keys($map), array_values($map), $unicode);
    $unicode = strip_tags($unicode); // remove <span> but keep mapped chars
    return trim($unicode);
}

// -------------------------
// Scraper Functions
// -------------------------
function getSura($id) {
    $url = "https://tanzil.net/pub/sample/show-sura.php?sura=" . $id;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function extractAyaData($html) {
    $data = [];

    // Extract sura name
    if (preg_match('/<div class=suraName>(.*?)<\/div>/', $html, $m)) {
        $suraName = trim($m[1]);
    } else {
        $suraName = "";
    }

    // Extract aya blocks
    preg_match_all('/<div class=aya>(.*?)<\/div>/', $html, $matches);
    foreach ($matches[1] as $ayaHtml) {
        preg_match('/<span class=ayaNum>(.*?)<\/span>/', $ayaHtml, $numMatch);
        $ayaNum = isset($numMatch[1]) ? trim(strip_tags($numMatch[1])) : "";

        $ayaHtmlFull = $ayaHtml; // keep original
        $ayaUnicode  = convertSpansToUnicode($ayaHtml);

        $data[] = [
            "sura_name"   => $suraName,
            "aya_num"     => $ayaNum,
            "aya_html"    => $ayaHtmlFull,
            "aya_unicode" => $ayaUnicode
        ];
    }

    return $data;
}

// -------------------------
// Spreadsheet Setup
// -------------------------
$spreadsheet = new Spreadsheet();

// --- Sheet 1: Quran Text ---
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle("Quran Text");

// Headers
$sheet1->setCellValue('A1', 'Sura ID');
$sheet1->setCellValue('B1', 'Sura Name');
$sheet1->setCellValue('C1', 'Aya Number');
$sheet1->setCellValue('D1', 'Aya Unicode');
$sheet1->setCellValue('E1', 'Aya HTML');

// Arabic-friendly style
$sheet1->getStyle('B:D')->getFont()->setName('KFGQPC Uthman Taha')->setSize(16);
$sheet1->getStyle('B:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$row = 2;

// -------------------------
// Main Loop
// -------------------------
for ($suraId = 112; $suraId <= 114; $suraId++) {
    echo "Fetching sura $suraId...\n";
    $html = getSura($suraId);
    $ayaData = extractAyaData($html);

    foreach ($ayaData as $aya) {
        $sheet1->setCellValue("A$row", $suraId);
        $sheet1->setCellValue("B$row", $aya['sura_name']);
        $sheet1->setCellValue("C$row", $aya['aya_num']);
        $sheet1->setCellValue("D$row", $aya['aya_unicode']);
        $sheet1->setCellValue("E$row", $aya['aya_html']);
        $row++;
    }

    // Respect Tanzil.net server
    usleep(500000); // 500ms pause
}

// -------------------------
// Sheet 2: Surah Metadata
// -------------------------
$metaSheet = $spreadsheet->createSheet();
$metaSheet->setTitle("Surah Metadata");

$metaSheet->setCellValue('A1', 'ID');
$metaSheet->setCellValue('B1', 'English Name');
$metaSheet->setCellValue('C1', 'Arabic Name');
$metaSheet->setCellValue('D1', 'Revelation Type');
$metaSheet->setCellValue('E1', 'Verses Count');
$metaSheet->setCellValue('F1', 'Ruku Count');

// Example metadata (you can load real data from JSON later)
$surahMeta = [
    ["1", "Al-Fatihah", "الفاتحة", "Meccan", 7, 1],
    ["2", "Al-Baqarah", "البقرة", "Medinan", 286, 40],
    ["3", "Aal-E-Imran", "آل عمران", "Medinan", 200, 20],
    // ... Add all 114 here or load dynamically
];

$row = 2;
foreach ($surahMeta as $m) {
    $metaSheet->fromArray($m, null, "A$row");
    $row++;
}

// -------------------------
// Save with timestamp
// -------------------------
$timestamp = date("Ymd_His");
$filename = "quran_surahs_$timestamp.xlsx";

$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "✅ Done! Saved to $filename\n";
