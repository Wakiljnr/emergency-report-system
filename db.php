<?php
$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$password = getenv("DB_PASS");
$database = getenv("DB_NAME");
$port = getenv("DB_PORT") ?: 3306;

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database,
    $port
);

=======
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
>>>>>>> 8ca110764e3b267a6003318296766200a842cb24
if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
?>
