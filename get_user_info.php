<?php
/* ============================================================
   get_user_info.php — Returns logged-in user's phone via AJAX
   Called by the SOS panel to display the user's phone number
   ============================================================ */

session_start();
require 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$id   = $_SESSION['user_id'];
$user = $conn->query("SELECT phone, fullname FROM users WHERE id = $id")->fetch_assoc();

// Return as JSON for the JavaScript fetch() call
echo json_encode([
    'phone'    => $user['phone']    ?? '',
    'fullname' => $user['fullname'] ?? ''
]);
?>