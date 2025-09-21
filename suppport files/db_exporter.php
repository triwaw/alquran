<?php
// db_exporter.php

if ($_SERVER["REQUEST_METHOD"] == "GET") {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Exporter</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    label { display: block; margin-top: 10px; }
    input, select { margin-top: 5px; padding: 6px; width: 320px; }
    .btn { background: #007BFF; color: white; border: none; padding: 10px 15px; cursor: pointer; margin-top: 15px; }
    .btn:hover { background: #0056b3; }
    .form-section { margin-bottom: 20px; }
  </style>
</head>
<body>
  <h2>Step 1: Export Database Table</h2>
  <form method="POST">
    <div class="form-section">
      <label>Database Name:</label>
      <input type="text" name="dbname" required>

      <label>Username:</label>
      <input type="text" name="dbuser" value="root" required>

      <label>Password:</label>
      <input type="password" name="dbpass" value="">
    </div>
    <div class="form-section">
      <label>Table Name:</label>
      <input type="text" name="tablename" required>

      <label>Export Format:</label>
      <select name="format" required>
        <option value="csv">CSV (UTF-8, comma delimited)</option>
        <option value="xlsx">Excel (XLSX)</option>
      </select>
    </div>
    <button type="submit" class="btn">Export Table</button>
  </form>
</body>
</html>
<?php
exit;
}

// Step 2: Perform Export
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dbname = $_POST["dbname"];
    $dbuser = $_POST["dbuser"];
    $dbpass = $_POST["dbpass"];
    $table = $_POST["tablename"];
    $format = $_POST["format"];

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET CHARACTER SET utf8mb4");

        $stmt = $pdo->query("SELECT * FROM `$table`");

        $timestamp = date("Y-m-d_His");

        if ($format === "csv") {
            $filename = __DIR__ . "/{$table}_export_$timestamp.csv";
            $fp = fopen($filename, "w");
            fwrite($fp, "\xEF\xBB\xBF"); // UTF-8 BOM

            // header row
            $columns = array_keys($stmt->fetch(PDO::FETCH_ASSOC));
            fputcsv($fp, $columns);

            // reset cursor & fetch again
            $stmt = $pdo->query("SELECT * FROM `$table`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as &$value) {
                    if (!mb_detect_encoding($value, 'UTF-8', true)) {
                        $value = mb_convert_encoding($value, 'UTF-8');
                    }
                }
                fputcsv($fp, $row);
            }
            fclose($fp);

            echo "<h2>✅ Export Complete</h2>";
            echo "<p>Table <b>$table</b> exported to CSV file:</p>";
            echo "<a href='" . basename($filename) . "' download>📥 Download CSV</a>";

        } elseif ($format === "xlsx") {
            // Export to Excel using PhpSpreadsheet
            require __DIR__ . "/vendor/autoload.php"; // requires PhpSpreadsheet installed
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // header row
            $columns = array_keys($stmt->fetch(PDO::FETCH_ASSOC));
            foreach ($columns as $i => $col) {
                $sheet->setCellValueByColumnAndRow($i+1, 1, $col);
            }

            // reset cursor & fetch again
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rowIndex = 2;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $colIndex = 1;
                foreach ($row as $cell) {
                    $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $cell);
                    $colIndex++;
                }
                $rowIndex++;
            }

            $filename = __DIR__ . "/{$table}_export_$timestamp.xlsx";
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filename);

            echo "<h2>✅ Export Complete</h2>";
            echo "<p>Table <b>$table</b> exported to Excel file:</p>";
            echo "<a href='" . basename($filename) . "' download>📥 Download Excel</a>";
        }

    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage();
    }
}
?>
