<?php
// 1. Define your local connection credentials
$host = "localhost";
$username = "root";
$password = "";
$database = "student_hub";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<h1>Connected successfully</h1>";
}
catch (Exception $e) 
{
    error_log("Database connection error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}