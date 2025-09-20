<?php
/**
 * Generic CSV → MySQL Importer (with Connection Form + Preview)
 * ------------------------------------------------------------
 * 1. User enters DB connection details (DB name, username, password)
 * 2. User uploads a CSV file + specifies table name
 * 3. Data imported with UTF-8 support
 * 4. Confirmation + preview of first 3 rows
 */

$importSummary = null;
$previewRows = [];
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbName   = trim($_POST['db_name'] ?? '');
    $dbUser   = trim($_POST['db_user'] ?? '');
    $dbPass   = trim($_POST['db_pass'] ?? '');
    $tableName = trim($_POST['table_name'] ?? '');
    $csvFileName = $_FILES['csv_file']['name'] ?? '';
    $csvFileTmp = $_FILES['csv_file']['tmp_name'] ?? '';

    if (!$dbName || !$dbUser || !$tableName || !$csvFileTmp) {
        $errorMsg = "❌ Please fill all fields and upload a CSV file.";
    } else {
        try {
            // --- Connect dynamically ---
            $pdo = new PDO("mysql:host=localhost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET NAMES utf8mb4");
            $pdo->exec("SET CHARACTER SET utf8mb4");

            // --- Create table if not exists ---
            $createTableSQL = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id BIGINT UNSIGNED PRIMARY KEY,
                sura_number BIGINT UNSIGNED NOT NULL,
                verse_number INT NOT NULL,
                verse_text_only TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                verse_with_signs TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                INDEX(sura_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($createTableSQL);

            // --- Import CSV ---
            $fp = fopen($csvFileTmp, "r");
            fgetcsv($fp); // skip header

            $stmt = $pdo->prepare("
                INSERT INTO `$tableName` (id, sura_number, verse_number, verse_text_only, verse_with_signs)
                VALUES (:id, :sura_number, :verse_number, :verse_text_only, :verse_with_signs)
                ON DUPLICATE KEY UPDATE
                    sura_number = VALUES(sura_number),
                    verse_number = VALUES(verse_number),
                    verse_text_only = VALUES(verse_text_only),
                    verse_with_signs = VALUES(verse_with_signs)
            ");

            $imported = 0;
            while (($row = fgetcsv($fp)) !== false) {
                [$id, $sura_number, $verse_number, $verse_text_only, $verse_with_signs] = $row;

                foreach ($row as &$value) {
                    if (!mb_detect_encoding($value, 'UTF-8', true)) {
                        $value = mb_convert_encoding($value, 'UTF-8');
                    }
                }

                $stmt->execute([
                    ':id' => $id,
                    ':sura_number' => $sura_number,
                    ':verse_number' => $verse_number,
                    ':verse_text_only' => $verse_text_only,
                    ':verse_with_signs' => $verse_with_signs
                ]);

                $imported++;
            }
            fclose($fp);

            // --- Fetch preview ---
            $previewStmt = $pdo->query("SELECT * FROM `$tableName` LIMIT 3");
            $previewRows = $previewStmt->fetchAll(PDO::FETCH_ASSOC);

            $importSummary = [
                'file' => $csvFileName,
                'table' => $tableName,
                'rows' => $imported,
                'db'   => $dbName
            ];

        } catch (Exception $e) {
            $errorMsg = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generic CSV Importer</title>
<style>
    body { font-family: Arial, sans-serif; background: #f3f6fa; margin: 0; padding: 20px; }
    .container {
        max-width: 750px; margin: auto; background: #fff; padding: 25px;
        border-radius: 12px; box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }
    h2 { text-align: center; color: #333; }
    label { font-weight: bold; display: block; margin-top: 15px; color: #444; }
    input[type="text"], input[type="password"], input[type="file"] {
        width: 100%; padding: 10px; border: 1px solid #ccc;
        border-radius: 8px; margin-top: 5px; font-size: 14px;
    }
    input[type="submit"] {
        margin-top: 20px; width: 100%; padding: 12px;
        background: #0073e6; border: none; border-radius: 8px;
        color: white; font-size: 16px; cursor: pointer; transition: 0.3s;
    }
    input[type="submit"]:hover { background: #005bb5; }
    .summary { margin-top: 20px; background: #e9f7ef; padding: 15px; border-radius: 8px; }
    .summary p { margin: 5px 0; color: #2d662d; }
    .error { margin-top: 20px; background: #fdecea; padding: 15px; border-radius: 8px; color: #b30000; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
</style>
</head>
<body>
<div class="container">
    <h2>📥 Generic CSV → MySQL Importer</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Database Name:</label>
        <input type="text" name="db_name" placeholder="Enter database name..." required>

        <label>Username:</label>
        <input type="text" name="db_user" placeholder="Enter MySQL username..." required>

        <label>Password:</label>
        <input type="password" name="db_pass" placeholder="Enter MySQL password...">

        <label>Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required>

        <label>Table Name:</label>
        <input type="text" name="table_name" placeholder="Enter table name..." required>

        <input type="submit" value="Import CSV">
    </form>

    <?php if ($errorMsg): ?>
        <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($importSummary): ?>
        <div class="summary">
            <p>✅ File <b><?= htmlspecialchars($importSummary['file']) ?></b> has been imported into 
               Table <b><?= htmlspecialchars($importSummary['table']) ?></b> 
               in Database <b><?= htmlspecialchars($importSummary['db']) ?></b></p>
            <p>Total Rows Added: <b><?= $importSummary['rows'] ?></b></p>
            <p>First 3 Rows (for verification):</p>
            <table>
                <tr>
                    <?php foreach (array_keys($previewRows[0]) as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ($previewRows as $row): ?>
                <tr>
                    <?php foreach ($row as $val): ?>
                        <td><?= htmlspecialchars($val) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
