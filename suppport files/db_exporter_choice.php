<?php
// db_exporter.php
// Improved: robust AJAX getCols (returns JSON only) and validation to avoid empty SELECT

// Turn off display_errors for AJAX responses to avoid breaking JSON (enable in dev if you want)

// at the very top of db_exporter.php
ini_set('display_errors', 0); // keep off
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
error_reporting(E_ALL);





// Helper: sanitize SQL identifiers (table/column) - allow a-zA-Z0-9_ only, prepend letter if starts with digit
function sanitize_ident($s) {
    $s = trim($s);
    $s = preg_replace('/[^a-zA-Z0-9_]/', '', $s);
    if ($s === '') return 'col';
    if (preg_match('/^\d/', $s)) $s = 'c'.$s;
    return $s;
}

// Helper: send JSON (clears any accidental output first)
function send_json($data) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------------------------
   AJAX handler: get column names
   Expects POST: dbname, dbuser, dbpass, tablename
   Returns JSON: {ok:true, cols:[...]} or {ok:false, error:"..."}
   ------------------------- */
if (isset($_GET['action']) && $_GET['action'] === 'getCols') {
    ob_start();
    try {
        $dbname = $_POST['dbname'] ?? '';
        $dbuser = $_POST['dbuser'] ?? '';
        $dbpass = $_POST['dbpass'] ?? '';
        $tablename = $_POST['tablename'] ?? '';

        if ($dbname === '' || $dbuser === '' || $tablename === '') {
            send_json(['ok' => false, 'error' => 'Missing required parameters']);
        }

        $tableSafe = sanitize_ident($tablename);

        $dsn = "mysql:host=localhost;dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbuser, $dbpass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableSafe}`");
        $cols = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = $row['Field'];
        }

        send_json(['ok' => true, 'cols' => $cols]);
    } catch (Throwable $e) {
        send_json(['ok' => false, 'error' => $e->getMessage()]);
    }
}


/* -------------------------
   GET form (step 1)
   ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>DB Exporter — Select Columns</title>
      <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f3f6fa;padding:24px}
        .card{max-width:820px;margin:0 auto;background:#fff;padding:22px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.06)}
        label{display:block;margin-top:12px;font-weight:600}
        input[type=text], input[type=password], select{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
        .small{width:260px}
        .row{display:flex;gap:12px}
        .muted{color:#666;font-size:13px}
        .btn{margin-top:14px;padding:10px 14px;background:#007bff;color:#fff;border:none;border-radius:8px;cursor:pointer}
        #columnsBox{margin-top:12px;padding:12px;border:1px dashed #d0d7e6;border-radius:8px;background:#fbfdff;display:none}
        .checkbox-inline{display:inline-block;margin-right:12px;margin-bottom:8px}
        .error{color:#b00020;background:#ffefef;padding:10px;border-radius:6px;margin-top:12px}
      </style>
    </head>
    <body>
      <div class="card">
        <h2>Export table → CSV / Excel</h2>
        <p class="muted">Enter DB credentials, table name. By default the full table is exported. Uncheck to pick specific columns.</p>

        <form id="exportForm" method="post">
          <label>Database</label>
          <input type="text" id="dbname" name="dbname" placeholder="database name" required>

          <div style="margin-top:8px" class="row">
            <div style="flex:1">
              <label>Username</label>
              <input type="text" id="dbuser" name="dbuser" value="root" required>
            </div>
            <div style="flex:1">
              <label>Password</label>
              <input type="password" id="dbpass" name="dbpass" value="">
            </div>
          </div>

          <label>Table name</label>
          <input type="text" id="tablename" name="tablename" placeholder="table name" required>

          <label style="margin-top:12px">Format</label>
          <select id="format" name="format">
            <option value="csv">CSV (UTF-8, comma delimited)</option>
            <option value="xlsx">Excel (XLSX)</option>
          </select>

          <label style="margin-top:12px">
            <input type="checkbox" id="allCols" name="allCols" checked> Export complete table (all columns)
          </label>

          <div id="columnsBox"></div>

          <div style="margin-top:16px">
            <button class="btn" type="submit">Export</button>
          </div>

          <div id="messageArea"></div>
        </form>
      </div>

      <script>
        // when user unchecks "allCols", fetch columns dynamically and show checkboxes
        document.getElementById('allCols').addEventListener('change', async function() {
          const box = document.getElementById('columnsBox');
          const messageArea = document.getElementById('messageArea');
          messageArea.innerHTML = '';
          if (!this.checked) {
            const dbname = document.getElementById('dbname').value.trim();
            const dbuser = document.getElementById('dbuser').value.trim();
            const dbpass = document.getElementById('dbpass').value;
            const tablename = document.getElementById('tablename').value.trim();

            if (!dbname || !dbuser || !tablename) {
              messageArea.innerHTML = "<div class='error'>Please fill Database, Username and Table fields first.</div>";
              this.checked = true;
              return;
            }

            box.innerHTML = "<div class='muted'>Loading columns…</div>";
            box.style.display = 'block';

            try {
              const params = new URLSearchParams();
              params.append('dbname', dbname);
              params.append('dbuser', dbuser);
              params.append('dbpass', dbpass);
              params.append('tablename', tablename);

              const res = await fetch('db_exporter.php?action=getCols', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params.toString()
              });

              const json = await res.json();
              if (!json.ok) {
                box.style.display = 'none';
                messageArea.innerHTML = "<div class='error'>❌ " + (json.error || 'Failed to get columns') + "</div>";
                document.getElementById('allCols').checked = true;
                return;
              }

              // build checkboxes
              const cols = json.cols;
              if (!Array.isArray(cols) || cols.length === 0) {
                box.innerHTML = "<div class='muted'>No columns found.</div>";
                return;
              }
              let html = "<strong>Select columns to export:</strong><div style='margin-top:8px'>";
              cols.forEach(c => {
                // escape for HTML
                const esc = c.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                html += "<label class='checkbox-inline'><input type='checkbox' name='columns[]' value='" + esc + "' checked> " + esc + "</label>";
              });
              html += "</div>";
              box.innerHTML = html;

            } catch (err) {
              box.style.display = 'none';
              messageArea.innerHTML = "<div class='error'>❌ Error fetching columns: " + String(err) + "</div>";
              document.getElementById('allCols').checked = true;
            }
          } else {
            box.style.display = 'none';
            box.innerHTML = '';
          }
        });

        // On submit: do simple client validation: if allCols unchecked ensure >=1 checkbox selected
        document.getElementById('exportForm').addEventListener('submit', function(e) {
          const allChecked = document.getElementById('allCols').checked;
          if (!allChecked) {
            const chosen = document.querySelectorAll('#columnsBox input[type="checkbox"]:checked');
            if (!chosen || chosen.length === 0) {
              e.preventDefault();
              document.getElementById('messageArea').innerHTML = "<div class='error'>Please select at least one column to export (or keep 'Export complete table' checked).</div>";
              return false;
            }
          }
          // allow normal POST to server
        });
      </script>
    </body>
    </html>
    <?php
    exit;
}

/* -------------------------
   POST export handler
   ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read POST
    $dbname = $_POST['dbname'] ?? '';
    $dbuser = $_POST['dbuser'] ?? '';
    $dbpass = $_POST['dbpass'] ?? '';
    $tablename = $_POST['tablename'] ?? '';
    $format = $_POST['format'] ?? 'csv';
    $allCols = isset($_POST['allCols']) ? true : false;
    $selectedCols = $allCols ? [] : ($_POST['columns'] ?? []);

    // basic validation
    if ($dbname === '' || $dbuser === '' || $tablename === '') {
        echo "<p style='color:red'>Please provide database, username and table name.</p>";
        exit;
    }

    // sanitize table name
    $tableSafe = sanitize_ident($tablename);

    try {
        $dsn = "mysql:host=localhost;dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbuser, $dbpass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET CHARACTER SET utf8mb4");

        // determine columns to export
        if ($allCols) {
            $colList = '*';
            // fetch column names for header later using SHOW COLUMNS
            $stmtCols = $pdo->query("SHOW COLUMNS FROM `{$tableSafe}`");
            $cols = [];
            while ($r = $stmtCols->fetch(PDO::FETCH_ASSOC)) $cols[] = $r['Field'];
        } else {
            // selectedCols must be non-empty
            if (!is_array($selectedCols) || count($selectedCols) === 0) {
                echo "<p style='color:red'>No columns selected. Go back and choose at least one column.</p>";
                exit;
            }
            // sanitize each column name and build safe list
            $colsSanitized = [];
            foreach ($selectedCols as $c) {
                $cSan = sanitize_ident($c);
                $colsSanitized[] = "`{$cSan}`";
            }
            $colList = implode(',', $colsSanitized);
            // also set human-readable header names (use original selected names)
            $cols = array_values($selectedCols);
        }

        // prepare and run select
        $sql = "SELECT {$colList} FROM `{$tableSafe}`";
        $stmt = $pdo->query($sql);

        $timestamp = date('Y-m-d_His');

        if ($format === 'csv') {
            $filename = __DIR__ . "/{$tableSafe}_export_{$timestamp}.csv";
            $fp = fopen($filename, 'w');
            if (!$fp) throw new Exception("Cannot create file: {$filename}");
            // UTF-8 BOM for Excel
            fwrite($fp, "\xEF\xBB\xBF");

            // header row: use $cols array (human labels)
            fputcsv($fp, $cols);

            // reset cursor (we already ran query) - fetch again
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // ensure encoding
                foreach ($row as &$v) {
                    if ($v !== null && $v !== '' && !mb_detect_encoding($v, 'UTF-8', true)) {
                        $v = mb_convert_encoding($v, 'UTF-8');
                    }
                }
                fputcsv($fp, array_values($row));
            }
            fclose($fp);

            // output success + link
            $basename = basename($filename);
            echo "<h2>✅ Export Complete</h2>";
            echo "<p>Table <b>" . htmlspecialchars($tablename) . "</b> exported to CSV:</p>";
            echo "<p><a href='{$basename}' download>📥 Download {$basename}</a></p>";

        } else { // xlsx
            // PhpSpreadsheet required
            if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
                echo "<p style='color:red'>PhpSpreadsheet not found. Run <code>composer require phpoffice/phpspreadsheet</code> in this folder.</p>";
                exit;
            }
            require __DIR__ . '/vendor/autoload.php';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // header row
            foreach ($cols as $i => $h) {
                $sheet->setCellValueByColumnAndRow($i+1, 1, $h);
            }

            // fetch again
            $stmt = $pdo->query($sql);
            $rowIndex = 2;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $colIndex = 1;
                foreach ($row as $val) {
                    $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $val);
                    $colIndex++;
                }
                $rowIndex++;
            }

            $filename = __DIR__ . "/{$tableSafe}_export_{$timestamp}.xlsx";
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filename);

            $basename = basename($filename);
            echo "<h2>✅ Export Complete</h2>";
            echo "<p>Table <b>" . htmlspecialchars($tablename) . "</b> exported to Excel:</p>";
            echo "<p><a href='{$basename}' download>📥 Download {$basename}</a></p>";
        }

    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    exit;
}
?>
