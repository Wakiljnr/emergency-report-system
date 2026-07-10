<?php
/* ============================================================
   sos.php — Handles SOS button submissions
   Saves full user details to sos_alerts table
   Called via AJAX when user holds SOS button 3 seconds
   ============================================================ */

session_start();
require 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = $_SESSION['user_id'];

    // Fetch full user details from database
    $user = $conn->query(
        "SELECT fullname, phone, email FROM users WHERE id = $user_id"
    )->fetch_assoc();

    $fullname = $user['fullname'];
    $phone    = $user['phone'];
    $email    = $user['email'];

    // Get location data from POST
    $latitude  = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $address   = trim($_POST['address']  ?? 'Location not specified');
    $message   = trim($_POST['message']  ?? 'SOS ALERT — Immediate emergency assistance required!');

    // Insert full SOS record into database
    $stmt = $conn->prepare(
        "INSERT INTO sos_alerts
         (user_id, fullname, phone, latitude, longitude, address, message, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
    );
    $stmt->bind_param(
        "issddss",
        $user_id,
        $fullname,
        $phone,
        $latitude,
        $longitude,
        $address,
        $message
    );

    if ($stmt->execute()) {
        $sos_id = $stmt->insert_id;
        echo json_encode([
            'success'  => true,
            'sos_id'   => $sos_id,
            'fullname' => $fullname,
            'phone'    => $phone,
            'message'  => 'SOS saved. ID #' . $sos_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save SOS alert.'
        ]);
    }
    exit();
}
?>