<?php
// scrape_quran_excel_meta.php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');
ignore_user_abort(true);

// --- MAIN SHEET: Quran Text ---
$spreadsheet = new Spreadsheet();
$quranSheet = $spreadsheet->getActiveSheet();
$quranSheet->setTitle('QuranText');

// Headers
$quranSheet->setCellValue('A1', 'Sura ID');
$quranSheet->setCellValue('B1', 'Sura Name');
$quranSheet->setCellValue('C1', 'Aya Number');
$quranSheet->setCellValue('D1', 'Aya Text');

// Styling
$quranSheet->getStyle('B:B')->getFont()->setName('KFGQPC Uthman Taha')->setSize(18);
$quranSheet->getStyle('D:D')->getFont()->setName('KFGQPC Uthman Taha')->setSize(18);

$quranSheet->getStyle('B:D')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
    ->setVertical(Alignment::VERTICAL_TOP);

$quranSheet->getStyle('D:D')->getAlignment()->setWrapText(true);
$quranSheet->setRightToLeft(true);

$quranSheet->getColumnDimension('A')->setWidth(8);
$quranSheet->getColumnDimension('B')->setWidth(28);
$quranSheet->getColumnDimension('C')->setWidth(10);
$quranSheet->getColumnDimension('D')->setWidth(80);

$row = 2;

// --- SECOND SHEET: Sura Metadata ---
$metaSheet = $spreadsheet->createSheet();
$metaSheet->setTitle('SuraMeta');
$metaSheet->setCellValue('A1', 'Sura ID');
$metaSheet->setCellValue('B1', 'Arabic Name');
$metaSheet->setCellValue('C1', 'English Name');
$metaSheet->setCellValue('D1', 'Revelation Type');
$metaSheet->setCellValue('E1', 'Total Ayas');

$metaRow = 2;

// --- Helper function: fetch URL ---
function fetchUrl(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (PHP Quran Scraper)',
        CURLOPT_TIMEOUT        => 60,
    ]);
    $html = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http >= 200 && $http < 300 && $html !== false) return $html;
    return null;
}

// --- Scrape loop ---
for ($sura = 1; $sura <= 114; $sura++) {
    echo "Fetching Sura $sura...\n";
    $url = "https://tanzil.net/pub/sample/show-sura.php?sura=" . $sura;
    $html = fetchUrl($url);
    if ($html === null) {
        echo "  -> Failed (HTTP error)\n";
        continue;
    }

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    // ---- Sura metadata ----
    $suraNameNode = $xpath->query("//div[contains(@class,'suraName')]")->item(0);
    $suraName = $suraNameNode ? trim($suraNameNode->textContent) : "";

    $engNameNode = $xpath->query("//div[contains(@class,'suraEngName')]")->item(0);
    $engName = $engNameNode ? trim($engNameNode->textContent) : "";

    $revelationNode = $xpath->query("//div[contains(@class,'suraType')]")->item(0);
    $revelation = $revelationNode ? trim($revelationNode->textContent) : "";

    $ayaNodes = $xpath->query("//div[contains(@class,'aya')]");
    $totalAyas = $ayaNodes->length;

    // Write metadata row
    $metaSheet->setCellValue("A{$metaRow}", $sura);
    $metaSheet->setCellValue("B{$metaRow}", $suraName);
    $metaSheet->setCellValue("C{$metaRow}", $engName);
    $metaSheet->setCellValue("D{$metaRow}", $revelation);
    $metaSheet->setCellValue("E{$metaRow}", $totalAyas);
    $metaRow++;

    // ---- Aya text ----
    foreach ($ayaNodes as $ayaNode) {
        $numNode = $xpath->query(".//span[contains(@class,'ayaNum')]", $ayaNode)->item(0);
        $ayaNum = $numNode ? preg_replace('/\D+/', '', $numNode->textContent) : '';

        // Keep full aya HTML (with <span> symbols intact)
        $innerHtml = '';
        foreach ($ayaNode->childNodes as $child) {
            $innerHtml .= $doc->saveHTML($child);
        }
        $ayaHtml = trim($innerHtml);

        // Write to QuranText sheet
        $quranSheet->setCellValue("A{$row}", $sura);
        $quranSheet->setCellValue("B{$row}", $suraName);
        $quranSheet->setCellValue("C{$row}", $ayaNum);
        $quranSheet->setCellValueExplicit("D{$row}", $ayaHtml, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $row++;
    }

    // Delay to be gentle
    usleep(500000); // 0.5 sec
}

// --- Save file ---
$timestamp = date('Ymd_His');
$filename = "quran_with_meta_{$timestamp}.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "✅ Saved as {$filename}\n";
