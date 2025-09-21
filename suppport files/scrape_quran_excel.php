<?php
// scrape_quran_excel.php
require 'vendor/autoload.php'; // composer require phpoffice/phpspreadsheet:^1.28

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

set_time_limit(0);                 // unlimited execution time
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');
ignore_user_abort(true);


// Get user inputs
$urlBase = $_POST['url'] ?? "https://tanzil.net/pub/sample/show-sura.php?sura=";
$delay   = (int) ($_POST['delay'] ?? 5000); // default 5 sec
$start   = (int) ($_POST['start'] ?? 1);
$end     = (int) ($_POST['end'] ?? 114);

// ✅ Clamp values
$delay = max(5, min(10000, $delay));
$start = max(1, min(114, $start));
$end   = max(1, min(114, $end));
if ($end < $start) $end = $start;




// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Sura ID');
$sheet->setCellValue('B1', 'Sura Name');
$sheet->setCellValue('C1', 'Aya Number');
$sheet->setCellValue('D1', 'Aya Text');

// Styling
// Font for Arabic columns (B and D)
$sheet->getStyle('B:B')->getFont()->setName('Traditional Arabic')->setSize(16);
$sheet->getStyle('D:D')->getFont()->setName('Traditional Arabic')->setSize(16);

// Align right and top for Arabic text
$sheet->getStyle('B:D')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
    ->setVertical(Alignment::VERTICAL_TOP);

// Wrap text in aya column
$sheet->getStyle('D:D')->getAlignment()->setWrapText(true);

// Set sheet direction to RTL (this sets overall reading direction for the sheet)
// $sheet->setRightToLeft(true);

// Optional: adjust column widths (small example)
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(28);
$sheet->getColumnDimension('C')->setWidth(10);
$sheet->getColumnDimension('D')->setWidth(160);

$row = 2;

// Function to fetch HTML via cURL
function fetchUrl(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (PHP) quran-scraper/1.0',
        CURLOPT_TIMEOUT        => 30,
    ]);
    $html = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http >= 200 && $http < 300 && $html !== false) return $html;
    return null;
}

// Loop over suras 1..114
// for ($sura = 112; $sura <= 114; $sura++) {
	
for ($sura = $start; $sura <= $end; $sura++) {
    echo "Fetching Sura $sura...\n";
	 $url = $urlBase . $sura;
    // $url = "https://tanzil.net/pub/sample/show-sura.php?sura=" . $sura;
    $html = fetchUrl($url);
    if ($html === null) {
        echo "  -> Failed to download Sura $sura (HTTP error)\n";
        continue;
    }

    // Parse DOM (robust UTF-8 handling)
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // Convert to HTML-ENTITIES to preserve Arabic chars
    $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    // Sura name
    $suraNameNode = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' suraName ')]")->item(0);
    $suraName = $suraNameNode ? trim($suraNameNode->textContent) : "";

    // All ayas
    $ayaNodes = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' aya ')]");
    foreach ($ayaNodes as $ayaNode) {
        // Get aya number (from span.ayaNum)
        $numNode = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' ayaNum ')]", $ayaNode)->item(0);
        $ayaNum = $numNode ? preg_replace('/\D+/', '', $numNode->textContent) : '';

        // Remove the ayaNum and any sign spans so we get clean aya text
        $removeNodes = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' ayaNum ') or contains(concat(' ', normalize-space(@class), ' '), ' sign ')]", $ayaNode);
        // iterate backwards to safely remove nodes
        for ($i = $removeNodes->length - 1; $i >= 0; $i--) {
            $n = $removeNodes->item($i);
            if ($n && $n->parentNode) {
                $n->parentNode->removeChild($n);
            }
        }

        // Remaining text content of the aya div
        $ayaText = trim($ayaNode->textContent);
        // Remove stray leading numbers or dots if any remain
        $ayaText = preg_replace('/^\s*\d+\.\s*/u', '', $ayaText);

        // Write to sheet
        $sheet->setCellValue("A{$row}", $sura);
        $sheet->setCellValue("B{$row}", $suraName);
        $sheet->setCellValue("C{$row}", $ayaNum);
        $sheet->setCellValue("D{$row}", $ayaText);
        $row++;
    }

    // small pause to be polite (optional)
 //    usleep(500000); // 150ms
 
  usleep($delay * 1000); // convert ms to µs
}

// Filename with timestamp and increment if exists
$timestamp = date('Ymd_His');
$baseName = "quran_ayas_{$timestamp}.xlsx";
$filename = $baseName;
$counter = 1;
while (file_exists($filename)) {
    $filename = "quran_ayas_{$timestamp}_{$counter}.xlsx";
    $counter++;
}

// Save file
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "✅ Done. Saved to: {$filename}\n";
