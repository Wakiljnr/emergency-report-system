<?php
/* Database connection for Render */

$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$database = getenv("DB_NAME");
$port = getenv("DB_PORT") ?: 3306;

// Create MySQL connection
$conn = new mysqli(
    $host,
    $user,
    $password,
    $database,
    $port
);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>
