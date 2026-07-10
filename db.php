<?php
/* ============================================================
   db.php — Database connection file
   Connect to MySQL via XAMPP (localhost, root, no password)
   ============================================================ */

$host     = "localhost";   // XAMPP default host
$user     = "root";        // XAMPP default MySQL user
$password = "";            // XAMPP default: no password
$database = "emergency_db"; // Our database name

// Create a MySQLi connection
$conn = new mysqli($host, $user, $password, $database);

// Check if connection failed and stop execution with error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>