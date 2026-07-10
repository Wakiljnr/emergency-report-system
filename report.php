<?php
/* ============================================================
   report.php — Processes emergency report form submissions
   Updated: now handles severity level field
   ============================================================ */

session_start();
require 'db.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['report'])) {
    $user_id     = $_SESSION['user_id'];
    $type        = $_POST['type'];
    $severity    = $_POST['severity'];           // NEW: severity level
    $description = trim($_POST['description']);
    $latitude    = $_POST['latitude']  ?: NULL;
    $longitude   = $_POST['longitude'] ?: NULL;
    $address     = trim($_POST['address']);

    // Insert report including severity into database
    $stmt = $conn->prepare(
        "INSERT INTO reports (user_id, type, severity, description, latitude, longitude, address)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssdds", $user_id, $type, $severity, $description, $latitude, $longitude, $address);

    if ($stmt->execute()) {
        $report_id = $stmt->insert_id;
        header("Location: dashboard.php?success=Emergency+reported!+ID:%23" . $report_id);
    } else {
        header("Location: dashboard.php?error=Failed+to+submit+report");
    }
    exit();
}
?>