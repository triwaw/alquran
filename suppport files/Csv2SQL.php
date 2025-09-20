<?php
// csv_importer.php

// Step 1: Form for Database + File Upload
if ($_SERVER["REQUEST_METHOD"] == "GET") {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CSV Importer</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    label { display: block; margin-top: 10px; }
    input, select { margin-top: 5px; padding: 5px; width: 300px; }
    .form-section { margin-bottom: 20px; }
    .btn { background: #4CAF50; color: white; border: none; padding: 10px 15px; cursor: pointer; }
    .btn:hover { background: #45a049; }
  </style>
</head>
<body>
  <h2>Step 1: Database & CSV File Selection</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="form-section">
      <label>Database Name:</label>
      <input type="text" name="dbname" required>
      
      <label>Username:</label>
      <input type="text" name="dbuser" value="root" required>
      
      <label>Password:</label>
      <input type="password" name="dbpass" value="">
    </div>
    <div class="form-section">
      <label>Select CSV File:</label>
      <input type="file" name="csvfile" accept=".csv" required>
      
      <label>Table Name:</label>
      <input type="text" name="tablename" required>
    </div>
    <button type="submit" class="btn">Upload CSV</button>
  </form>
</body>
</html>
<?php
exit;
}

// Step 2: Handle CSV Upload & Field Mapping
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csvfile"])) {
    $dbname = $_POST["dbname"];
    $dbuser = $_POST["dbuser"];
    $dbpass = $_POST["dbpass"];
    $table = $_POST["tablename"];

    $csvFile = $_FILES["csvfile"]["tmp_name"];
    $rows = array_map("str_getcsv", file($csvFile));
    $header = array_shift($rows);

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Step 2: Define Columns</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        select, input[type="text"] { padding: 5px; }
      </style>
      <script>
        function toggleOptions(rowIndex) {
          let type = document.getElementById("type_"+rowIndex).value;
          let options = document.getElementById("intOptions_"+rowIndex);
          if (type === "INT" || type === "BIGINT") {
            options.style.display = "block";
          } else {
            options.style.display = "none";
          }
        }
      </script>
    </head>
    <body>
      <h2>Step 2: Define Column Types</h2>
      <form method="POST">
        <input type="hidden" name="dbname" value="<?php echo htmlspecialchars($dbname); ?>">
        <input type="hidden" name="dbuser" value="<?php echo htmlspecialchars($dbuser); ?>">
        <input type="hidden" name="dbpass" value="<?php echo htmlspecialchars($dbpass); ?>">
        <input type="hidden" name="tablename" value="<?php echo htmlspecialchars($table); ?>">
        <input type="hidden" name="csvfile" value="<?php echo base64_encode(file_get_contents($csvFile)); ?>">

        <table>
          <tr>
            <th>Column Name</th>
            <th>Type</th>
            <th>Length (if VARCHAR/INT)</th>
            <th>Extra Options (INT/BIGINT only)</th>
          </tr>
          <?php foreach ($header as $i => $col): ?>
          <tr>
            <td><input type="text" name="columns[<?php echo $i; ?>][name]" value="<?php echo htmlspecialchars($col); ?>"></td>
            <td>
              <select name="columns[<?php echo $i; ?>][type]" id="type_<?php echo $i; ?>" onchange="toggleOptions(<?php echo $i; ?>)">
                <option value="TEXT">TEXT</option>
                <option value="VARCHAR">VARCHAR</option>
                <option value="INT">INT</option>
                <option value="BIGINT">BIGINT</option>
                <option value="DATE">DATE</option>
                <option value="TIMESTAMP">TIMESTAMP</option>
              </select>
            </td>
            <td><input type="text" name="columns[<?php echo $i; ?>][length]" placeholder="e.g. 255"></td>
            <td>
              <div id="intOptions_<?php echo $i; ?>" style="display:none">
                <label><input type="checkbox" name="columns[<?php echo $i; ?>][unsigned]"> UNSIGNED</label>
                <label><input type="checkbox" name="columns[<?php echo $i; ?>][auto_increment]"> AUTO_INCREMENT</label>
                <label><input type="checkbox" name="columns[<?php echo $i; ?>][primary]"> PRIMARY KEY</label>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <button type="submit" class="btn">Import CSV into Database</button>
      </form>
    </body>
    </html>
    <?php
    exit;
}

// Step 3: Create Table & Insert Data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["columns"])) {
    $dbname = $_POST["dbname"];
    $dbuser = $_POST["dbuser"];
    $dbpass = $_POST["dbpass"];
    $table = $_POST["tablename"];
    $csvContent = base64_decode($_POST["csvfile"]);
    $rows = array_map("str_getcsv", explode("\n", trim($csvContent)));
    $header = array_shift($rows);
    $columns = $_POST["columns"];

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Build CREATE TABLE statement
        $colDefs = [];
        $primaryKeys = [];
        foreach ($columns as $col) {
            $colName = "`" . $col['name'] . "`";
            $type = $col['type'];
            $length = !empty($col['length']) ? "({$col['length']})" : "";
            $extra = "";

            if (($type === "INT" || $type === "BIGINT") && isset($col['unsigned'])) {
                $extra .= " UNSIGNED";
            }
            if (($type === "INT" || $type === "BIGINT") && isset($col['auto_increment'])) {
                $extra .= " AUTO_INCREMENT";
            }
            if (($type === "INT" || $type === "BIGINT") && isset($col['primary'])) {
                $primaryKeys[] = $col['name'];
            }

            $colDefs[] = "$colName $type$length$extra";
        }

        if (!empty($primaryKeys)) {
            $colDefs[] = "PRIMARY KEY (" . implode(", ", array_map(fn($c) => "`$c`", $primaryKeys)) . ")";
        }

        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        $sql = "CREATE TABLE `$table` (" . implode(", ", $colDefs) . ")";
        $pdo->exec($sql);

        // Insert rows
        $placeholders = implode(",", array_fill(0, count($header), "?"));
        $stmt = $pdo->prepare("INSERT INTO `$table` (" . implode(",", array_map(fn($c) => "`$c`", $header)) . ") VALUES ($placeholders)");

        foreach ($rows as $row) {
            if (count($row) == count($header)) {
                $stmt->execute($row);
            }
        }

        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $preview = $pdo->query("SELECT * FROM `$table` LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>✅ Import Successful</h2>";
        echo "<p>File imported into table <b>$table</b></p>";
        echo "<p>Total Rows Added: <b>$count</b></p>";
        echo "<h3>First 3 Rows:</h3>";
        echo "<table border='1' cellpadding='5'><tr>";
        foreach ($header as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        foreach ($preview as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";

    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage();
    }
}
?>
