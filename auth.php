<?php
/* ============================================================
   auth.php — Handles login and registration form submissions
   Called via POST from index.php
   ============================================================ */

session_start();          // Start session to track logged-in user
require 'db.php';         // Include database connection

// ── SIGNUP HANDLER ──────────────────────────────────────────
if (isset($_POST['signup'])) {

    // Sanitize inputs to prevent XSS attacks
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Validate that passwords match before saving
    if ($password !== $confirm) {
        header("Location: index.php?error=Passwords+do+not+match&tab=signup");
        exit();
    }

    // Hash the password using bcrypt for secure storage
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    // Check if email is already registered
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Email already exists — redirect with error
        header("Location: index.php?error=Email+already+registered&tab=signup");
        exit();
    }

    // Insert new user into the database
    $stmt = $conn->prepare(
        "INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $fullname, $email, $phone, $hashed);

    if ($stmt->execute()) {
        // Registration successful — redirect to login with success message
        header("Location: index.php?success=Account+created!+Please+log+in");
    } else {
        header("Location: index.php?error=Registration+failed&tab=signup");
    }
    exit();
}

// ── LOGIN HANDLER ────────────────────────────────────────────
if (isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user record matching the email
    $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        // No account with that email
        header("Location: index.php?error=Invalid+email+or+password");
        exit();
    }

    $stmt->bind_result($id, $fullname, $hashed, $role);
    $stmt->fetch();

    // Verify the entered password against the stored hash
    if (!password_verify($password, $hashed)) {
        header("Location: index.php?error=Invalid+email+or+password");
        exit();
    }

    // Store user info in session variables
    $_SESSION['user_id']  = $id;
    $_SESSION['fullname'] = $fullname;
    $_SESSION['role']     = $role;

    // Redirect admin users to admin panel, regular users to dashboard
    if ($role === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}
?>