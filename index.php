<?php
/* ============================================================
   index.php — Main entry page with Login and Signup tabs
   Styled to match the Nigerian Emergency Response System branding
   ============================================================ */

session_start();

// If user is already logged in, redirect them to the right page
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit();
}

// Get the active tab from URL parameter (default: login)
$tab    = isset($_GET['tab']) ? $_GET['tab'] : 'login';
$error  = isset($_GET['error'])   ? $_GET['error']   : '';
$success= isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NERS Nigeria — Emergency Response System</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Fonts: Inter for clean government/tech feel -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

    <!-- ── ANIMATED BACKGROUND OVERLAY ── -->
    <div class="bg-overlay">
        <!-- Hexagon grid pattern mimicking the image's honeycomb design -->
        <div class="hex-grid"></div>
        <!-- Animated radar pulse circles (bottom-right like in the image) -->
        <div class="radar-pulse">
            <span></span><span></span><span></span>
        </div>
    </div>

    <!-- ── HEADER / BRANDING BAR ── -->
    <header class="auth-header">
        <div class="brand">
            <!-- Nigeria coat of arms placeholder (text version) -->
            <div class="coat-of-arms">🦅</div>
            <div class="brand-text">
                <span class="brand-sub">National Emergency Response System</span>
                <span class="brand-main">NIGERIA</span>
            </div>
        </div>
        <!-- Emergency hotline shown in header like the image -->
        <div class="hotline">
            <i class="fas fa-phone-volume"></i>
            <span>Call <strong>112</strong> for Emergencies</span>
        </div>
    </header>

    <!-- ── MAIN AUTH CARD ── -->
    <main class="auth-main">

        <!-- Left panel: service icons (mirrors the hexagon icons in the image) -->
        <div class="auth-info">
            <h2>One Call. Every Agency.</h2>
            <p>Nigeria's unified emergency platform connecting you to Police, Fire, and Medical services instantly.</p>
            <div class="service-icons">
                <div class="svc-icon"><i class="fas fa-shield-alt"></i><span>Police</span></div>
                <div class="svc-icon"><i class="fas fa-fire"></i><span>Fire Service</span></div>
                <div class="svc-icon"><i class="fas fa-plus-circle"></i><span>Medical</span></div>
                <div class="svc-icon"><i class="fas fa-phone"></i><span>112 Hotline</span></div>
            </div>
            <!-- Live clock displayed on the left panel -->
            <div class="live-clock">
                <i class="fas fa-clock"></i>
                <span id="liveClock">--:--:--</span>
                <span id="liveDate"></span>
            </div>
        </div>

        <!-- Right panel: the login/signup form card -->
        <div class="auth-card">

            <!-- Alert messages for errors or success -->
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Tab switcher: Login vs Signup -->
            <div class="tab-switcher">
                <button class="tab-btn <?= $tab === 'login'  ? 'active' : '' ?>" onclick="switchTab('login')">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                <button class="tab-btn <?= $tab === 'signup' ? 'active' : '' ?>" onclick="switchTab('signup')">
                    <i class="fas fa-user-plus"></i> Sign Up
                </button>
            </div>

            <!-- ── LOGIN FORM ── -->
            <div id="loginForm" class="form-panel <?= $tab === 'login' ? 'active' : '' ?>">
                <h3>Welcome Back</h3>
                <p class="form-sub">Sign in to access the Emergency Response System</p>
                <form action="auth.php" method="POST">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <!-- Hidden field tells auth.php which action to perform -->
                    <button type="submit" name="login" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>

            <!-- ── SIGNUP FORM ── -->
            <div id="signupForm" class="form-panel <?= $tab === 'signup' ? 'active' : '' ?>">
                <h3>Create Account</h3>
                <p class="form-sub">Register to report and track emergencies</p>
                <form action="auth.php" method="POST">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="fullname" placeholder="Full Name" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" placeholder="Phone Number" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required minlength="6">
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>
                    <button type="submit" name="signup" class="btn-primary">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
            </div>

        </div><!-- /auth-card -->
    </main>

    <script>
    /* ── Tab Switcher Logic ──────────────────────────────── */
    function switchTab(tab) {
        // Hide all form panels
        document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b  => b.classList.remove('active'));

        // Show the selected tab
        document.getElementById(tab + 'Form').classList.add('active');
        event.target.classList.add('active');
    }

    /* ── Live Clock (updates every second) ──────────────── */
    function updateClock() {
        const now  = new Date();

        // Format time as HH:MM:SS
        const time = now.toLocaleTimeString('en-NG', { hour12: false });

        // Format date as Day, DD Month YYYY
        const date = now.toLocaleDateString('en-NG', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });

        document.getElementById('liveClock').textContent = time;
        document.getElementById('liveDate').textContent  = date;
    }

    // Run immediately, then repeat every 1000ms (1 second)
    updateClock();
    setInterval(updateClock, 1000);
    </script>
</body>
</html>