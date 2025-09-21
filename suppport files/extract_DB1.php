<?php
// ----------------------
// Enable error reporting (dev mode)
// ----------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ----------------------
// Helper: connect to DB
// ----------------------
function connect($dbname, $dbuser, $dbpass) {
    $dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    return $pdo;
}

// ----------------------
// Helper: export data to CSV
// ----------------------
function export_table($pdo, $tablename, $cols) {
    $stmt = $pdo->query("SELECT $cols FROM `$tablename`");

    $timestamp = date("Y-m-d_His");
    $filename = $tablename . "_export_$timestamp.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $fp = fopen('php://output', 'w');
    fwrite($fp, "\xEF\xBB\xBF"); // Excel UTF-8 BOM

    $first = true;
    while ($row = $stmt->fetch()) {
        if ($first) {
            fputcsv($fp, array_keys($row));
            $first = false;
        }
        fputcsv($fp, $row);
    }
    fclose($fp);
    exit;
}

// ----------------------
// Handle form submissions
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbname = $_POST['dbname'];
    $dbuser = $_POST['dbuser'] ?: "root";
    $dbpass = $_POST['dbpass'] ?? "";
    $table  = $_POST['table'];

    $pdo = connect($dbname, $dbuser, $dbpass);

    // Step 1: full export directly
    if (isset($_POST['full_export'])) {
        export_table($pdo, $table, "*");
    }

    // Step 2: user picked specific columns
    if (isset($_POST['export_selected']) && !empty($_POST['cols'])) {
        $cols = implode(", ", array_map(fn($c) => "`$c`", $_POST['cols']));
        export_table($pdo, $table, $cols);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Database Table Exporter</title>
<style>
    body { font-family: Arial, sans-serif; background:#f9fafb; padding:30px; }
    .card { background:#fff; padding:20px; margin:20px auto; max-width:800px;
            border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    h2 { margin-top:0; color:#333; }
    label { font-weight:bold; display:block; margin-top:10px; }
    input[type=text], input[type=password] {
        width:100%; padding:8px; margin-top:5px;
        border:1px solid #ccc; border-radius:8px;
    }
    button {
        margin-top:15px; padding:10px 18px; border:none;
        border-radius:8px; background:#4CAF50; color:white;
        cursor:pointer; font-size:15px;
    }
    button:hover { background:#43a047; }
    table { border-collapse:collapse; width:100%; margin-top:15px; }
    table th, table td { border:1px solid #ccc; padding:8px; text-align:left; }
    table th { background:#f1f1f1; }
</style>
</head>
<body>

<div class="card">
    <h2>Step 1: Database Connection</h2>
    <form method="post">
        <label>Database Name:</label>
        <input type="text" name="dbname" required>

        <label>User:</label>
        <input type="text" name="dbuser" value="root">

        <label>Password:</label>
        <input type="password" name="dbpass" value="">

        <label>Table Name:</label>
        <input type="text" name="table" required>

        <button type="submit" name="preview">Preview / Choose Columns</button>
        <button type="submit" name="full_export">Export Full Table</button>
    </form>
</div>

<?php
// ----------------------
// Step 2: Show preview if requested
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])) {
    try {
        $dbname = $_POST['dbname'];
        $dbuser = $_POST['dbuser'] ?: "root";
        $dbpass = $_POST['dbpass'] ?? "";
        $table  = $_POST['table'];

        $pdo = connect($dbname, $dbuser, $dbpass);

        $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 3");
        $rows = $stmt->fetchAll();

        if ($rows) {
            echo "<div class='card'><h2>Step 2: Select Columns to Export</h2>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='dbname' value='$dbname'>";
            echo "<input type='hidden' name='dbuser' value='$dbuser'>";
            echo "<input type='hidden' name='dbpass' value='$dbpass'>";
            echo "<input type='hidden' name='table' value='$table'>";

            echo "<table><tr>";
            foreach (array_keys($rows[0]) as $col) {
                echo "<th><label><input type='checkbox' name='cols[]' value='$col' checked> $col</label></th>";
            }
            echo "</tr>";

            foreach ($rows as $r) {
                echo "<tr>";
                foreach ($r as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";

            echo "<button type='submit' name='export_selected'>Export Selected Columns</button>";
            echo "</form></div>";
        } else {
            echo "<div class='card'>⚠️ Table is empty.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='card' style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

</body>
</html>
