<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 

// Proteksyon para sa ROLE (Para hindi ma-access ng basta-bastang user ang admin page)
// Only Admin can export the database
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

$tables = array();
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$sql_script = "-- ESCR DQMS Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    // Structure
    $query = "SHOW CREATE TABLE $table";
    $res = mysqli_query($conn, $query);
    $row = mysqli_fetch_row($res);
    $sql_script .= "\n\n" . $row[1] . ";\n\n";

    // Data
    $query = "SELECT * FROM $table";
    $res = mysqli_query($conn, $query);
    $column_count = mysqli_num_fields($res);

    for ($i = 0; $i < $column_count; $i++) {
        while ($row = mysqli_fetch_row($res)) {
            $sql_script .= "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $column_count; $j++) {
                $row[$j] = $row[$j] ? '"' . mysqli_real_escape_string($conn, $row[$j]) . '"' : "NULL";
                $sql_script .= ($j < ($column_count - 1)) ? $row[$j] . ',' : $row[$j];
            }
            $sql_script .= ");\n";
        }
    }
    $sql_script .= "\n";
}

// Set headers to download the file
$filename = "ESCR_Backup_" . date('Y-m-d') . ".sql";
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $filename . "\"");
echo $sql_script;
exit;
?>