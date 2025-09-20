<?php
// scrape_quran_full.php
// Requires: composer require phpoffice/phpspreadsheet:^1.28
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

mb_internal_encoding('UTF-8');
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '1024M');
ignore_user_abort(true);

// -------------------------
// Helper: fetch URL with retries
// -------------------------
function fetchUrl(string $url, int $tries = 3, int $timeout = 60): ?string {
    for ($attempt = 1; $attempt <= $tries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (PHP Quran Scraper)',
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_ENCODING       => '', // accept compressed
        ]);
        $html = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($html !== false && $http >= 200 && $http < 300) {
            return $html;
        }

        // brief backoff before retry (be gentle)
        usleep(200000); // 200ms
    }
    return null;
}

// -------------------------
// Helper: get inner HTML of a node
// -------------------------
function getInnerHTML(\DOMNode $node, \DOMDocument $doc): string {
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $doc->saveHTML($child);
    }
    return $html;
}

// -------------------------
// Convert node children -> clean Unicode text
// (keeps textual content and text of <span> marks such as Quranic symbols)
// -------------------------
function nodeChildrenToUnicodeText(\DOMNode $node): string {
    $pieces = [];
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            $pieces[] = $child->nodeValue;
        } elseif ($child->nodeType === XML_ELEMENT_NODE) {
            // For elements (span, etc) we append their textContent (this preserves any symbol characters)
            $pieces[] = $child->textContent;
        }
    }
    $text = implode('', $pieces);
    // remove leading aya numbers like "1. " if present
    $text = preg_replace('/^\s*\d+\.\s*/u', '', $text);
    // normalize whitespace
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

// -------------------------
// Full metadata: 114 surahs
// Format per row: [id, english_name, arabic_name, revelation_type, verses_count, ruku_count]
// -------------------------
$surahMeta = [
    [1, "Al-Fatihah", "الفاتحة", "Meccan", 7, 1],
    [2, "Al-Baqarah", "البقرة", "Medinan", 286, 40],
    [3, "Aal-i-Imran", "آل عمران", "Medinan", 200, 20],
    [4, "An-Nisa", "النساء", "Medinan", 176, 24],
    [5, "Al-Ma'idah", "المائدة", "Medinan", 120, 16],
    [6, "Al-An'am", "الأنعام", "Meccan", 165, 20],
    [7, "Al-A'raf", "الأعراف", "Meccan", 206, 24],
    [8, "Al-Anfal", "الأنفال", "Medinan", 75, 10],
    [9, "At-Tawbah", "التوبة", "Medinan", 129, 16],
    [10, "Yunus", "يونس", "Meccan", 109, 11],
    [11, "Hud", "هود", "Meccan", 123, 10],
    [12, "Yusuf", "يوسف", "Meccan", 111, 12],
    [13, "Ar-Ra'd", "الرعد", "Medinan", 43, 6],
    [14, "Ibrahim", "ابراهيم", "Meccan", 52, 7],
    [15, "Al-Hijr", "الحجر", "Meccan", 99, 6],
    [16, "An-Nahl", "النحل", "Meccan", 128, 16],
    [17, "Al-Isra", "الإسراء", "Meccan", 111, 12],
    [18, "Al-Kahf", "الكهف", "Meccan", 110, 12],
    [19, "Maryam", "مريم", "Meccan", 98, 6],
    [20, "Ta-Ha", "طه", "Meccan", 135, 8],
    [21, "Al-Anbiya", "الأنبياء", "Meccan", 112, 7],
    [22, "Al-Hajj", "الحج", "Medinan", 78, 10],
    [23, "Al-Mu'minun", "المؤمنون", "Meccan", 118, 6],
    [24, "An-Nur", "النور", "Medinan", 64, 9],
    [25, "Al-Furqan", "الفرقان", "Meccan", 77, 6],
    [26, "Ash-Shu'ara", "الشعراء", "Meccan", 227, 11],
    [27, "An-Naml", "النمل", "Meccan", 93, 7],
    [28, "Al-Qasas", "القصص", "Meccan", 88, 9],
    [29, "Al-Ankabut", "العنكبوت", "Meccan", 69, 7],
    [30, "Ar-Rum", "الروم", "Meccan", 60, 6],
    [31, "Luqman", "لقمان", "Meccan", 34, 4],
    [32, "As-Sajda", "السجدة", "Meccan", 30, 3],
    [33, "Al-Ahzab", "الأحزاب", "Medinan", 73, 9],
    [34, "Saba", "سبأ", "Meccan", 54, 6],
    [35, "Fatir", "فاطر", "Meccan", 45, 5],
    [36, "Ya-Sin", "يس", "Meccan", 83, 5],
    [37, "As-Saffat", "الصافات", "Meccan", 182, 5],
    [38, "Sad", "ص", "Meccan", 88, 5],
    [39, "Az-Zumar", "الزمر", "Meccan", 75, 8],
    [40, "Ghafir", "غافر", "Meccan", 85, 9],
    [41, "Fussilat", "فصلت", "Meccan", 54, 6],
    [42, "Ash-Shura", "الشورى", "Meccan", 53, 5],
    [43, "Az-Zukhruf", "الزخرف", "Meccan", 89, 7],
    [44, "Ad-Dukhan", "الدخان", "Meccan", 59, 3],
    [45, "Al-Jathiya", "الجاثية", "Meccan", 37, 4],
    [46, "Al-Ahqaf", "الأحقاف", "Meccan", 35, 4],
    [47, "Muhammad", "محمد", "Medinan", 38, 4],
    [48, "Al-Fath", "الفتح", "Medinan", 29, 4],
    [49, "Al-Hujurat", "الحجرات", "Medinan", 18, 2],
    [50, "Qaf", "ق", "Meccan", 45, 3],
    [51, "Adh-Dhariyat", "الذاريات", "Meccan", 60, 3],
    [52, "At-Tur", "الطور", "Meccan", 49, 2],
    [53, "An-Najm", "النجم", "Meccan", 62, 3],
    [54, "Al-Qamar", "القمر", "Meccan", 55, 3],
    [55, "Ar-Rahman", "الرحمن", "Medinan", 78, 3],
    [56, "Al-Waqia", "الواقعة", "Meccan", 96, 3],
    [57, "Al-Hadid", "الحديد", "Medinan", 29, 4],
    [58, "Al-Mujadila", "المجادلة", "Medinan", 22, 3],
    [59, "Al-Hashr", "الحشر", "Medinan", 24, 3],
    [60, "Al-Mumtahina", "الممتحنة", "Medinan", 13, 2],
    [61, "As-Saff", "الصف", "Medinan", 14, 2],
    [62, "Al-Jumu'a", "الجمعة", "Medinan", 11, 2],
    [63, "Al-Munafiqun", "المنافقون", "Medinan", 11, 2],
    [64, "At-Taghabun", "التغابن", "Medinan", 18, 2],
    [65, "At-Talaq", "الطلاق", "Medinan", 12, 2],
    [66, "At-Tahrim", "التحريم", "Medinan", 12, 2],
    [67, "Al-Mulk", "الملك", "Meccan", 30, 2],
    [68, "Al-Qalam", "القلم", "Meccan", 52, 2],
    [69, "Al-Haqqah", "الحاقة", "Meccan", 52, 2],
    [70, "Al-Maarij", "المعارج", "Meccan", 44, 2],
    [71, "Nuh", "نوح", "Meccan", 28, 2],
    [72, "Al-Jinn", "الجن", "Meccan", 28, 2],
    [73, "Al-Muzzammil", "المزمل", "Meccan", 20, 2],
    [74, "Al-Muddathir", "المدثر", "Meccan", 56, 2],
    [75, "Al-Qiyamah", "القيامة", "Meccan", 40, 2],
    [76, "Al-Insan", "الانسان", "Medinan", 31, 2],
    [77, "Al-Mursalat", "المرسلات", "Meccan", 50, 2],
    [78, "An-Naba", "النبأ", "Meccan", 40, 2],
    [79, "An-Nazi'at", "النازعات", "Meccan", 46, 2],
    [80, "Abasa", "عبس", "Meccan", 42, 1],
    [81, "At-Takwir", "التكوير", "Meccan", 29, 1],
    [82, "Al-Infitar", "الإنفطار", "Meccan", 19, 1],
    [83, "Al-Mutaffifin", "المطففين", "Meccan", 36, 1],
    [84, "Al-Inshiqaq", "الانشقاق", "Meccan", 25, 1],
    [85, "Al-Buruj", "البروج", "Meccan", 22, 1],
    [86, "At-Tariq", "الطارق", "Meccan", 17, 1],
    [87, "Al-A'la", "الأعلى", "Meccan", 19, 1],
    [88, "Al-Ghashiyah", "الغاشية", "Meccan", 26, 1],
    [89, "Al-Fajr", "الفجر", "Meccan", 30, 1],
    [90, "Al-Balad", "البلد", "Meccan", 20, 1],
    [91, "Ash-Shams", "الشمس", "Meccan", 15, 1],
    [92, "Al-Lail", "الليل", "Meccan", 21, 1],
    [93, "Ad-Duha", "الضحى", "Meccan", 11, 1],
    [94, "Ash-Sharh", "الشرح", "Meccan", 8, 1],
    [95, "At-Tin", "التين", "Meccan", 8, 1],
    [96, "Al-Alaq", "العلق", "Meccan", 19, 1],
    [97, "Al-Qadr", "القدر", "Meccan", 5, 1],
    [98, "Al-Bayyina", "البينة", "Medinan", 8, 1],
    [99, "Az-Zalzala", "الزلزلة", "Medinan", 8, 1],
    [100, "Al-Adiyat", "العاديات", "Meccan", 11, 1],
    [101, "Al-Qari'ah", "القارعة", "Meccan", 11, 1],
    [102, "At-Takathur", "التكاثر", "Meccan", 8, 1],
    [103, "Al-Asr", "العصر", "Meccan", 3, 1],
    [104, "Al-Humazah", "الهمزة", "Meccan", 9, 1],
    [105, "Al-Fil", "الفيل", "Meccan", 5, 1],
    [106, "Quraish", "قريش", "Meccan", 4, 1],
    [107, "Al-Ma'un", "الماعون", "Meccan", 7, 1],
    [108, "Al-Kawthar", "الكوثر", "Meccan", 3, 1],
    [109, "Al-Kafirun", "الكافرون", "Meccan", 6, 1],
    [110, "An-Nasr", "النصر", "Medinan", 3, 1],
    [111, "Al-Masad", "المسد", "Meccan", 5, 1],
    [112, "Al-Ikhlas", "الإخلاص", "Meccan", 4, 1],
    [113, "Al-Falaq", "الفلق", "Meccan", 5, 1],
    [114, "An-Nas", "الناس", "Meccan", 6, 1],
];

// -------------------------
// Create Spreadsheet & Sheets
// -------------------------
$spreadsheet = new Spreadsheet();

// Sheet 1: Quran Text
$quranSheet = $spreadsheet->getActiveSheet();
$quranSheet->setTitle('QuranText');

// Header row
$quranSheet->setCellValue('A1', 'Sura ID');
$quranSheet->setCellValue('B1', 'Sura Name (Arabic)');
$quranSheet->setCellValue('C1', 'Aya Number');
$quranSheet->setCellValue('D1', 'Aya Unicode');
$quranSheet->setCellValue('E1', 'Aya HTML');

// Styling (Arabic columns)
$quranSheet->getStyle('B:D')->getFont()->setName('KFGQPC Uthman Taha')->setSize(16);
$quranSheet->getStyle('B:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$quranSheet->getStyle('D:D')->getAlignment()->setWrapText(true);
$quranSheet->setRightToLeft(true);

// Column widths
$quranSheet->getColumnDimension('A')->setWidth(8);
$quranSheet->getColumnDimension('B')->setWidth(32);
$quranSheet->getColumnDimension('C')->setWidth(10);
$quranSheet->getColumnDimension('D')->setWidth(80);
$quranSheet->getColumnDimension('E')->setWidth(80);

// Sheet 2: Surah Metadata
$metaSheet = $spreadsheet->createSheet();
$metaSheet->setTitle('SurahMeta');

$metaSheet->setCellValue('A1', 'ID');
$metaSheet->setCellValue('B1', 'English Name');
$metaSheet->setCellValue('C1', 'Arabic Name');
$metaSheet->setCellValue('D1', 'Revelation Type');
$metaSheet->setCellValue('E1', 'Verses Count');
$metaSheet->setCellValue('F1', 'Ruku Count');

// Fill meta sheet from $surahMeta
$metaRow = 2;
foreach ($surahMeta as $m) {
    $metaSheet->setCellValueExplicit("A{$metaRow}", $m[0], DataType::TYPE_STRING);
    $metaSheet->setCellValue("B{$metaRow}", $m[1]);
    $metaSheet->setCellValue("C{$metaRow}", $m[2]);
    $metaSheet->setCellValue("D{$metaRow}", $m[3]);
    $metaSheet->setCellValueExplicit("E{$metaRow}", $m[4], DataType::TYPE_STRING);
    $metaSheet->setCellValueExplicit("F{$metaRow}", $m[5], DataType::TYPE_STRING);
    $metaRow++;
}

// -------------------------
// Scrape loop (fill QuranText sheet)
// -------------------------
$row = 2;
for ($sura = 1; $sura <= 114; $sura++) {
    echo "Fetching Sura {$sura}...\n";
    $url = "https://tanzil.net/pub/sample/show-sura.php?sura={$sura}";
    $html = fetchUrl($url, 3, 60);
    if ($html === null) {
        echo "  -> Failed to fetch Sura {$sura}\n";
        // still write sura meta row or continue
        usleep(500000);
        continue;
    }

    // Parse DOM (UTF-8 safe)
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // Convert encoding to HTML-ENTITIES so that Arabic remains intact
    $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    // Sura name: robust match for class
    $suraNameNode = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' suraName ')]")->item(0);
    $suraName = $suraNameNode ? trim($suraNameNode->textContent) : "";

    // Aya nodes
    $ayaNodes = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' aya ')]");

    foreach ($ayaNodes as $ayaNode) {
        // Aya number from span.ayaNum (digits)
        $numNode = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' ayaNum ')]", $ayaNode)->item(0);
        $ayaNumRaw = $numNode ? trim($numNode->textContent) : '';
        $ayaNum = preg_replace('/\D+/', '', $ayaNumRaw); // keep digits only

        // Aya HTML: full innerHTML of this div (keeps spans intact)
        $ayaHtmlFull = getInnerHTML($ayaNode, $doc);
        $ayaHtmlFull = trim($ayaHtmlFull);

        // Aya Unicode: merge textContent of children (keeps symbol characters)
        $ayaUnicode = nodeChildrenToUnicodeText($ayaNode);

        // Clean hebi: remove leading number from Unicode text if still present
        $ayaUnicode = preg_replace('/^\s*\d+\.\s*/u', '', $ayaUnicode);
        $ayaUnicode = trim($ayaUnicode);

        // Write to sheet, use explicit string for large/HTML cells
        $quranSheet->setCellValueExplicit("A{$row}", (string)$sura, DataType::TYPE_STRING);
        $quranSheet->setCellValue("B{$row}", $suraName);
        $quranSheet->setCellValueExplicit("C{$row}", $ayaNum, DataType::TYPE_STRING);
        $quranSheet->setCellValueExplicit("D{$row}", $ayaUnicode, DataType::TYPE_STRING);
        $quranSheet->setCellValueExplicit("E{$row}", $ayaHtmlFull, DataType::TYPE_STRING);

        $row++;
    }

    // polite delay
    usleep(500000); // 500 ms
}

// -------------------------
// Save file (timestamped, increment if exists)
// -------------------------
$timestamp = date('Ymd_His');
$base = "quran_full_{$timestamp}.xlsx";
$filename = $base;
$i = 1;
while (file_exists($filename)) {
    $filename = "quran_full_{$timestamp}_{$i}.xlsx";
    $i++;
}

$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "✅ Done. Saved to: {$filename}\n";
