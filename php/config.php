<?php
$host = "localhost";
$dbUser = "root";
$dbPassword = "";
$database = "student_hub";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $dbUser, $dbPassword, $database);
    $conn->set_charset('utf8mb4');
}
catch (mysqli_sql_exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    throw new RuntimeException("Database connection failed.", 0, $e);
}
echo "<h1>Database connection successful.</h1>";