<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$fullname  = $_SESSION['fullname'];
$userInfo  = $conn->query("SELECT phone FROM users WHERE id=$user_id")->fetch_assoc();
$userPhone = $userInfo['phone'] ?? '';

$adminInfo  = $conn->query("SELECT phone FROM users WHERE role='admin' LIMIT 1")->fetch_assoc();
$adminPhone = $adminInfo['phone'] ?? '08000000000';

$reports     = $conn->query("SELECT * FROM reports WHERE user_id=$user_id ORDER BY reported_at DESC LIMIT 8");
$mySOSAlerts = $conn->query("SELECT * FROM sos_alerts WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");

$total      = $conn->query("SELECT COUNT(*) c FROM reports WHERE user_id=$user_id")->fetch_assoc()['c'];
$pending    = $conn->query("SELECT COUNT(*) c FROM reports WHERE user_id=$user_id AND status='pending'")->fetch_assoc()['c'];
$dispatched = $conn->query("SELECT COUNT(*) c FROM reports WHERE user_id=$user_id AND status='dispatched'")->fetch_assoc()['c'];
$resolved   = $conn->query("SELECT COUNT(*) c FROM reports WHERE user_id=$user_id AND status='resolved'")->fetch_assoc()['c'];
$sos_total  = $conn->query("SELECT COUNT(*) c FROM sos_alerts WHERE user_id=$user_id")->fetch_assoc()['c'];

$error   = $_GET['error']   ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>NERS Dashboard</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── GLOBAL ─────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;max-width:100%;overflow-x:hidden;font-family:'Inter',sans-serif;}
.dash-body{display:flex;min-height:100vh;background:#f0f4f0;}

/* ── SIDEBAR ─────────────────────────────────────────────── */
.sidebar{
    width:195px;min-height:100vh;
    background:linear-gradient(180deg,#001a00,#003300);
    position:fixed;left:0;top:0;bottom:0;
    z-index:200;display:flex;flex-direction:column;
    transition:transform .3s;
}
.sidebar-brand{display:flex;align-items:center;gap:8px;padding:11px 13px;border-bottom:1px solid rgba(0,166,81,.3);}
.brand-icon{font-size:1.3rem;}
.s-title{font-size:.8rem;font-weight:800;color:#00e676;}
.s-sub{font-size:.58rem;color:rgba(255,255,255,.5);}
.sidebar-nav{flex:1;padding:6px 0;}
.nav-link{
    display:flex;align-items:center;gap:7px;
    padding:7px 13px;color:rgba(255,255,255,.7);
    text-decoration:none;font-size:.72rem;font-weight:500;
    border-left:3px solid transparent;transition:all .2s;
}
.nav-link:hover,.nav-link.active{background:rgba(0,166,81,.2);color:#00e676;border-left-color:#00e676;}
.nav-link.logout{color:rgba(255,120,120,.8);}
.nav-link.logout:hover{background:rgba(255,0,0,.12);color:#ff8080;}
.sidebar-hotline{
    padding:9px 13px;background:rgba(0,166,81,.2);
    border-top:1px solid rgba(0,166,81,.3);
    color:rgba(255,255,255,.8);font-size:.68rem;
    display:flex;align-items:center;gap:5px;
}
.sidebar-hotline strong{color:#00e676;}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:199;}
.sidebar-overlay.show{display:block;}
.hamburger{
    display:none;background:#006400;color:white;
    border:none;border-radius:6px;padding:6px 10px;
    cursor:pointer;font-size:.78rem;font-family:'Inter',sans-serif;
    align-items:center;gap:4px;flex-shrink:0;
}

/* ── MAIN ────────────────────────────────────────────────── */
.dash-main{
    margin-left:195px;flex:1;
    padding:0 13px 16px;
    max-width:calc(100% - 195px);
}

/* ── TOPBAR ──────────────────────────────────────────────── */
.topbar{
    position:sticky;top:0;z-index:100;
    background:#f0f4f0;padding:7px 0;
    margin-bottom:10px;
    border-bottom:2px solid #d0e8d0;
    display:flex;align-items:center;
    flex-wrap:wrap;gap:6px;width:100%;
}
.topbar-greet{display:flex;align-items:center;gap:6px;font-size:.76rem;color:#003300;}
.topbar-greet i{color:#006400;}
.topbar-meta{display:flex;gap:5px;flex-wrap:wrap;margin-left:auto;}
.meta-tag{
    background:white;border:1px solid #c8e0c8;
    border-radius:14px;padding:3px 8px;
    font-size:.66rem;color:#333;
    display:flex;align-items:center;gap:3px;
}
.meta-tag i{color:#006400;font-size:.6rem;}

/* ── ALERTS ──────────────────────────────────────────────── */
.alert{padding:7px 11px;border-radius:7px;margin-bottom:8px;font-size:.72rem;display:flex;align-items:center;gap:6px;font-weight:500;width:100%;}
.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}

/* ── STAT CARDS ──────────────────────────────────────────── */
.stat-cards{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-bottom:10px;width:100%;}
.stat-card{background:white;border-radius:8px;padding:9px 8px;border-left:3px solid #006400;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.stat-card.yellow{border-left-color:#d97706;}.stat-card.blue{border-left-color:#1d4ed8;}.stat-card.green{border-left-color:#059669;}
.stat-num{font-size:1.3rem;font-weight:800;color:#003300;line-height:1;}
.stat-label{font-size:.58rem;color:#666;margin-top:2px;display:flex;align-items:center;gap:3px;}

/* ── CARDS ───────────────────────────────────────────────── */
.card{background:white;border-radius:10px;padding:11px;margin-bottom:10px;box-shadow:0 1px 6px rgba(0,0,0,.06);border:1px solid #e0ece0;width:100%;overflow:hidden;}
.section-title{font-size:.8rem;font-weight:700;color:#003300;margin-bottom:10px;display:flex;align-items:center;gap:6px;padding-bottom:7px;border-bottom:2px solid #e8f4e8;}
.section-title i{color:#006400;}
.no-data{text-align:center;padding:16px;color:#888;font-size:.72rem;}

/* ── EMERGENCY CARD ──────────────────────────────────────── */
.emergency-card{border:2px solid #fecaca;background:linear-gradient(135deg,#fff8f8,#fff);}

/* Tabs */
.step-tabs{display:flex;margin-bottom:12px;border-radius:8px;overflow:hidden;border:2px solid #e5e7eb;width:100%;}
.step-tab{flex:1;padding:8px 6px;border:none;background:#f9fafb;font-size:.72rem;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:5px;color:#555;}
.step-tab.sos-tab.active{background:linear-gradient(135deg,#dc2626,#7f1d1d);color:white;}
.step-tab.report-tab.active{background:linear-gradient(135deg,#006400,#00a651);color:white;}
.step-tab:not(.active):hover{background:#f3f4f6;}
.tab-panel{display:none;}.tab-panel.active{display:block;}

/* ── SOS PANEL ───────────────────────────────────────────── */
.sos-panel{display:grid;grid-template-columns:1fr auto;gap:14px;align-items:center;}
.sos-info-col h3{font-size:.8rem;font-weight:700;color:#991b1b;margin-bottom:5px;display:flex;align-items:center;gap:5px;}
.sos-info-col p{font-size:.68rem;color:#555;margin-bottom:8px;line-height:1.5;}
.admin-call-box{background:#fef2f2;border:1px solid #fecaca;border-radius:7px;padding:7px 10px;margin-bottom:8px;display:flex;align-items:center;gap:7px;}
.admin-call-box i{color:#dc2626;font-size:.9rem;flex-shrink:0;}
.admin-call-box strong{color:#991b1b;font-size:.66rem;display:block;margin-bottom:1px;}
.admin-call-box .phone-num{font-size:.86rem;font-weight:800;color:#dc2626;letter-spacing:.04em;}
.sos-user-details{display:flex;flex-direction:column;gap:4px;}
.sos-detail-row{display:inline-flex;align-items:center;gap:6px;font-size:.66rem;color:#333;background:white;border:1px solid #fee2e2;padding:4px 10px;border-radius:14px;max-width:100%;word-break:break-word;}
.sos-detail-row i{color:#dc2626;font-size:.62rem;flex-shrink:0;}

/* SOS Button */
.sos-btn-col{display:flex;flex-direction:column;align-items:center;gap:6px;flex-shrink:0;}
#sosBtn{
    width:86px;height:86px;border-radius:50%;
    border:4px solid #dc2626;
    background:radial-gradient(circle,#ff2020,#b91c1c 55%,#7f1d1d);
    cursor:pointer;position:relative;
    box-shadow:0 0 0 6px rgba(220,38,38,.15),0 0 0 12px rgba(220,38,38,.07),0 5px 20px rgba(220,38,38,.4);
    animation:sosPulse 2s ease-in-out infinite;
    transition:all .2s;
}
#sosBtn:hover{transform:scale(1.05);}
#sosBtn:active{transform:scale(.96);}
.sos-btn-label{display:flex;flex-direction:column;align-items:center;color:white;pointer-events:none;}
.sos-btn-label i{font-size:1.3rem;margin-bottom:1px;}
.sos-btn-label span{font-size:.88rem;font-weight:900;letter-spacing:.08em;}
@keyframes sosPulse{
    0%,100%{box-shadow:0 0 0 6px rgba(220,38,38,.15),0 0 0 12px rgba(220,38,38,.07),0 5px 20px rgba(220,38,38,.4);}
    50%{box-shadow:0 0 0 10px rgba(220,38,38,.2),0 0 0 20px rgba(220,38,38,.05),0 5px 28px rgba(220,38,38,.5);}
}
.sos-hold-hint{font-size:.6rem;color:#888;text-align:center;}
.sos-countdown-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:900;color:white;pointer-events:none;}
#sosResult{margin-top:9px;padding:8px 11px;border-radius:8px;font-size:.7rem;font-weight:600;display:none;align-items:center;gap:8px;width:100%;}
.sos-result-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;}
.sos-result-err{background:#fff1f2;border:1px solid #fecaca;color:#991b1b;}

/* ── REPORT FORM ──────────────────────────────────────────── */
.report-form{width:100%;}
.form-row{margin-bottom:9px;width:100%;}
.form-row label{display:block;font-size:.7rem;font-weight:600;color:#003300;margin-bottom:4px;}
.input-field{width:100%;padding:7px 9px;border:1.5px solid #d0e0d0;border-radius:7px;font-size:.74rem;font-family:'Inter',sans-serif;outline:none;resize:vertical;transition:border-color .2s;}
.input-field:focus{border-color:#006400;}
textarea.input-field{min-height:55px;}

/* Type */
.type-selector{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;width:100%;}
.type-opt{display:flex;align-items:center;justify-content:center;gap:4px;padding:5px 3px;border:1.5px solid #d0e0d0;border-radius:6px;cursor:pointer;font-size:.64rem;font-weight:600;transition:all .2s;background:white;}
.type-opt.police{color:#1d4ed8;}.type-opt.fire{color:#b91c1c;}.type-opt.medical{color:#059669;}.type-opt.other{color:#6b21a8;}
.type-opt:has(input:checked){border-color:#006400;background:#f0fff4;box-shadow:0 0 0 2px rgba(0,100,0,.1);}
.type-opt input[type="radio"]{display:none;}

/* Severity */
.severity-selector{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin-bottom:7px;width:100%;}
.sev-opt{cursor:pointer;border:1.5px solid #e5e7eb;border-radius:7px;padding:6px 4px;text-align:center;transition:all .2s;background:white;user-select:none;}
.sev-opt:hover{transform:translateY(-1px);box-shadow:0 2px 7px rgba(0,0,0,.08);}
.sev-opt input[type="radio"]{display:none;}
.sev-content{display:flex;flex-direction:column;align-items:center;gap:2px;}
.sev-content i{font-size:.9rem;}
.sev-label{font-size:.62rem;font-weight:700;}
.sev-desc{font-size:.55rem;color:#888;line-height:1.2;}
.sev-low .sev-content i{color:#16a34a;}.sev-moderate .sev-content i{color:#d97706;}.sev-high .sev-content i{color:#dc2626;}.sev-critical .sev-content i{color:#7f1d1d;}
.sev-low:has(input:checked){border-color:#16a34a;background:#f0fdf4;box-shadow:0 0 0 2px rgba(22,163,74,.15);}
.sev-moderate:has(input:checked){border-color:#d97706;background:#fffbeb;box-shadow:0 0 0 2px rgba(217,119,6,.15);}
.sev-high:has(input:checked){border-color:#dc2626;background:#fff1f2;box-shadow:0 0 0 2px rgba(220,38,38,.15);}
.sev-critical:has(input:checked){border-color:#7f1d1d;background:#fef2f2;box-shadow:0 0 0 2px rgba(127,29,29,.18);}

.severity-meter{display:flex;align-items:center;gap:5px;margin-top:5px;flex-wrap:wrap;width:100%;}
.meter-label{font-size:.62rem;font-weight:600;color:#555;white-space:nowrap;}
.meter-bar{flex:1;height:6px;background:#e5e7eb;border-radius:6px;overflow:hidden;min-width:50px;}
.meter-fill{height:100%;width:0%;background:#16a34a;border-radius:6px;transition:width .5s ease,background .4s ease;}
.meter-text{font-size:.6rem;font-weight:500;color:#888;flex:1;min-width:90px;transition:color .3s;}

/* Manual location */
.manual-location-section{background:#f8fbf8;border:1.5px solid #d0e8d0;border-radius:8px;padding:9px;width:100%;}
.manual-location-section h4{font-size:.7rem;font-weight:700;color:#003300;margin-bottom:6px;display:flex;align-items:center;gap:4px;}
.state-selector{display:grid;grid-template-columns:repeat(3,1fr);gap:4px;margin-bottom:6px;}
.state-btn{padding:5px 4px;background:white;border:1.5px solid #d0e0d0;border-radius:6px;font-size:.6rem;cursor:pointer;color:#003300;font-weight:600;transition:all .2s;font-family:'Inter',sans-serif;text-align:center;}
.state-btn:hover{background:#f0fdf4;border-color:#006400;color:#006400;}
.state-btn.selected{background:#006400;color:white;border-color:#006400;}
.landmark-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:4px;margin-top:5px;}
.landmark-btn{padding:5px 7px;background:white;border:1.5px solid #d0e0d0;border-radius:6px;font-size:.58rem;cursor:pointer;color:#003300;transition:all .2s;font-family:'Inter',sans-serif;text-align:left;width:100%;}
.landmark-btn:hover{background:#f0fdf4;border-color:#006400;color:#006400;}
.landmark-btn.picked{background:#006400;color:white;border-color:#006400;}

/* Submit */
.btn-emergency{width:100%;padding:9px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;border:none;border-radius:8px;font-size:.76rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;letter-spacing:.04em;font-family:'Inter',sans-serif;}
.btn-emergency:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(220,38,38,.35);}
@keyframes criticalPulse{0%,100%{box-shadow:0 0 0 0 rgba(127,29,29,.7);}50%{box-shadow:0 0 0 10px rgba(127,29,29,0);}}
.btn-pulse{animation:criticalPulse 1.2s ease-in-out infinite;}

/* ── TABLE ───────────────────────────────────────────────── */
.table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}
.data-table{width:100%;min-width:440px;border-collapse:collapse;}
.data-table th{background:#003300;color:white;padding:5px 7px;text-align:left;font-weight:600;font-size:.57rem;text-transform:uppercase;letter-spacing:.04em;}
.data-table td{padding:5px 7px;border-bottom:1px solid #e8f0e8;vertical-align:middle;font-size:.65rem;}
.data-table tr:hover{background:#f8fbf8;}

/* ── BADGES ──────────────────────────────────────────────── */
.badge{padding:2px 6px;border-radius:9px;font-size:.56rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap;}
.badge-police{background:#dbeafe;color:#1e40af;}.badge-fire{background:#fee2e2;color:#991b1b;}.badge-medical{background:#d1fae5;color:#065f46;}.badge-other{background:#f3e8ff;color:#6b21a8;}
.sev-badge-low{background:#dcfce7;color:#166534;}.sev-badge-moderate{background:#fef3c7;color:#92400e;}.sev-badge-high{background:#fee2e2;color:#991b1b;}.sev-badge-critical{background:#450a0a;color:#fff;}
.status-pending{background:#fef3c7;color:#92400e;}.status-dispatched{background:#dbeafe;color:#1e3a8a;}.status-resolved{background:#d1fae5;color:#065f46;}
.gps-link{color:#006400;font-size:.62rem;text-decoration:none;display:inline-flex;align-items:center;gap:2px;}
.gps-link:hover{text-decoration:underline;}

/* ── RESPONSIVE ──────────────────────────────────────────── */
@media(max-width:768px){
    .sidebar{transform:translateX(-100%);}
    .sidebar.open{transform:translateX(0);}
    .dash-main{margin-left:0!important;padding:0 8px 14px!important;max-width:100%!important;}
    .hamburger{display:flex;}
    .stat-cards{grid-template-columns:repeat(3,1fr);}
    .sos-panel{grid-template-columns:1fr;justify-items:center;}
    .severity-selector,.type-selector{grid-template-columns:repeat(2,1fr);}
    .state-selector{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:480px){
    .stat-cards{grid-template-columns:repeat(2,1fr);}
    .state-selector{grid-template-columns:repeat(2,1fr);}
    .landmark-grid{grid-template-columns:1fr;}
}

/* Blink keyframe */
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.25;}}
</style>
</head>
<body class="dash-body">

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🦅</span>
        <div><div class="s-title">NERS</div><div class="s-sub">Nigeria</div></div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="#emergencySection" class="nav-link" onclick="closeSidebar()"><i class="fas fa-bolt"></i> Emergency</a>
        <a href="#myReports" class="nav-link" onclick="closeSidebar()"><i class="fas fa-list"></i> My Reports</a>
        <a href="#mySOSHistory" class="nav-link" onclick="closeSidebar()"><i class="fas fa-broadcast-tower"></i> SOS History</a>
        <a href="logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <div class="sidebar-hotline">
        <i class="fas fa-phone-volume"></i>
        <span>Emergency: <strong>112</strong></span>
    </div>
</aside>

<main class="dash-main">

    <!-- ── TOPBAR ── -->
    <div class="topbar">
        <button class="hamburger" onclick="openSidebar()"><i class="fas fa-bars"></i> Menu</button>
        <div class="topbar-greet">
            <i class="fas fa-user-circle"></i>
            <span>Hello, <strong><?= htmlspecialchars($fullname) ?></strong></span>
        </div>
        <div class="topbar-meta">
            <span class="meta-tag"><i class="fas fa-clock"></i><span id="dashClock">--:--:--</span></span>
            <span class="meta-tag"><i class="fas fa-calendar-alt"></i><span id="dashDate"></span></span>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- ── STAT CARDS ── -->
    <div class="stat-cards">
        <div class="stat-card"><div class="stat-num"><?=$total?></div><div class="stat-label"><i class="fas fa-file-alt"></i>Total</div></div>
        <div class="stat-card yellow"><div class="stat-num"><?=$pending?></div><div class="stat-label"><i class="fas fa-hourglass-half"></i>Pending</div></div>
        <div class="stat-card blue"><div class="stat-num"><?=$dispatched?></div><div class="stat-label"><i class="fas fa-truck"></i>Dispatched</div></div>
        <div class="stat-card green"><div class="stat-num"><?=$resolved?></div><div class="stat-label"><i class="fas fa-check"></i>Resolved</div></div>
        <div class="stat-card" style="border-left-color:#dc2626;"><div class="stat-num" style="color:#dc2626;"><?=$sos_total?></div><div class="stat-label"><i class="fas fa-broadcast-tower" style="color:#dc2626;"></i>SOS</div></div>
    </div>

    <!-- ── EMERGENCY SECTION ── -->
    <section class="card emergency-card" id="emergencySection">
        <h2 class="section-title">
            <i class="fas fa-bolt" style="color:#dc2626;"></i> Emergency Action
        </h2>

        <div class="step-tabs">
            <button class="step-tab sos-tab active" id="tabSOS" onclick="switchTab('sos')">
                <i class="fas fa-phone-volume"></i> SOS — Call Admin
            </button>
            <button class="step-tab report-tab" id="tabReport" onclick="switchTab('report')">
                <i class="fas fa-exclamation-triangle"></i> Report Emergency
            </button>
        </div>

        <!-- SOS PANEL -->
        <div class="tab-panel active" id="panelSOS">
            <div class="sos-panel">
                <div class="sos-info-col">
                    <h3><i class="fas fa-phone-volume"></i> SOS Direct Call</h3>
                    <p>Hold 3 seconds to call admin and save your emergency alert.</p>
                    <div class="admin-call-box">
                        <i class="fas fa-headset"></i>
                        <div>
                            <strong>Admin Emergency Line</strong>
                            <span class="phone-num"><?= htmlspecialchars($adminPhone) ?></span>
                        </div>
                    </div>
                    <div class="sos-user-details">
                        <div class="sos-detail-row"><i class="fas fa-user"></i><span><?= htmlspecialchars($fullname) ?></span></div>
                        <div class="sos-detail-row"><i class="fas fa-phone"></i><span><?= htmlspecialchars($userPhone) ?></span></div>
                        <div class="sos-detail-row"><i class="fas fa-clock"></i><span id="sosTime">--:--:--</span></div>
                    </div>
                </div>
                <div class="sos-btn-col">
                    <button type="button" id="sosBtn"
                            onmousedown="startSOSHold()"
                            onmouseup="cancelSOSHold()"
                            onmouseleave="cancelSOSHold()"
                            ontouchstart="startSOSHold(event)"
                            ontouchend="cancelSOSHold()">
                        <div class="sos-btn-label">
                            <i class="fas fa-phone"></i>
                            <span>SOS</span>
                        </div>
                    </button>
                    <p class="sos-hold-hint"><i class="fas fa-hand-pointer"></i> Hold 3s</p>
                </div>
            </div>
            <div id="sosResult"></div>
        </div>

        <!-- REPORT PANEL -->
        <div class="tab-panel" id="panelReport">
            <form action="report.php" method="POST" class="report-form">

                <div class="form-row">
                    <label>Emergency Type</label>
                    <div class="type-selector">
                        <label class="type-opt police"><input type="radio" name="type" value="Police" required><i class="fas fa-shield-alt"></i> Police</label>
                        <label class="type-opt fire"><input type="radio" name="type" value="Fire"><i class="fas fa-fire"></i> Fire</label>
                        <label class="type-opt medical"><input type="radio" name="type" value="Medical"><i class="fas fa-plus-circle"></i> Medical</label>
                        <label class="type-opt other"><input type="radio" name="type" value="Other"><i class="fas fa-exclamation"></i> Other</label>
                    </div>
                </div>

                <div class="form-row">
                    <label>Severity</label>
                    <div class="severity-selector">
                        <label class="sev-opt sev-low"><input type="radio" name="severity" value="low" required>
                            <div class="sev-content"><i class="fas fa-check-circle"></i><span class="sev-label">Low</span><span class="sev-desc">Minor</span></div>
                        </label>
                        <label class="sev-opt sev-moderate"><input type="radio" name="severity" value="moderate">
                            <div class="sev-content"><i class="fas fa-exclamation-circle"></i><span class="sev-label">Moderate</span><span class="sev-desc">Urgent</span></div>
                        </label>
                        <label class="sev-opt sev-high"><input type="radio" name="severity" value="high">
                            <div class="sev-content"><i class="fas fa-exclamation-triangle"></i><span class="sev-label">High</span><span class="sev-desc">Serious</span></div>
                        </label>
                        <label class="sev-opt sev-critical"><input type="radio" name="severity" value="critical">
                            <div class="sev-content"><i class="fas fa-skull-crossbones"></i><span class="sev-label">Critical</span><span class="sev-desc">Life threat</span></div>
                        </label>
                    </div>
                    <div class="severity-meter">
                        <div class="meter-label">Level:</div>
                        <div class="meter-bar"><div class="meter-fill" id="meterFill"></div></div>
                        <div class="meter-text" id="meterText">Select above</div>
                    </div>
                </div>

                <div class="form-row">
                    <label><i class="fas fa-map-marker-alt" style="color:#006400;"></i> Location</label>
                    <input type="hidden" name="latitude"  id="lat">
                    <input type="hidden" name="longitude" id="lng">
                    <div class="manual-location-section">
                        <h4><i class="fas fa-map-pin"></i> Select State &amp; Landmark</h4>
                        <div class="state-selector">
                            <button type="button" class="state-btn" onclick="selectState('Yobe')">📍 Yobe</button>
                            <button type="button" class="state-btn" onclick="selectState('Borno')">📍 Borno</button>
                            <button type="button" class="state-btn" onclick="selectState('Adamawa')">📍 Adamawa</button>
                            <button type="button" class="state-btn" onclick="selectState('Bauchi')">📍 Bauchi</button>
                            <button type="button" class="state-btn" onclick="selectState('Gombe')">📍 Gombe</button>
                            <button type="button" class="state-btn" onclick="selectState('Kano')">📍 Kano</button>
                        </div>
                        <div id="landmarkSection" style="display:none;margin-top:6px;">
                            <p style="font-size:.62rem;color:#555;margin-bottom:4px;font-weight:600;"><i class="fas fa-map-signs"></i> Landmarks:</p>
                            <div class="landmark-grid" id="landmarkGrid"></div>
                        </div>
                        <div style="margin-top:7px;">
                            <p style="font-size:.62rem;color:#555;margin-bottom:4px;font-weight:600;"><i class="fas fa-keyboard"></i> Or type address:</p>
                            <input type="text" name="address" id="address" class="input-field" placeholder="e.g. Pompomari Road, Damaturu" required autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <label>Description</label>
                    <textarea name="description" rows="2" class="input-field" placeholder="What happened, how many people, nearest landmark..." required></textarea>
                </div>

                <button type="submit" name="report" class="btn-emergency" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> SUBMIT EMERGENCY REPORT
                </button>
            </form>
        </div>
    </section>

    <!-- ── MY REPORTS ── -->
    <section class="card" id="myReports">
        <h2 class="section-title"><i class="fas fa-list-alt"></i> My Reports</h2>
        <?php if($reports->num_rows===0): ?>
            <p class="no-data">No reports yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>#</th><th>Type</th><th>Sev</th><th>Description</th><th>Address</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while($r=$reports->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?=$r['id']?></strong></td>
                    <td><span class="badge badge-<?=strtolower($r['type'])?>"><?=htmlspecialchars($r['type'])?></span></td>
                    <td><span class="badge sev-badge-<?=$r['severity']??'moderate'?>"><?=ucfirst(substr($r['severity']??'mod',0,3))?></span></td>
                    <td><?=htmlspecialchars(substr($r['description'],0,30))?>...</td>
                    <td style="max-width:100px;word-break:break-word;"><?=htmlspecialchars($r['address']?:'N/A')?></td>
                    <td><span class="badge status-<?=$r['status']?>"><?=ucfirst($r['status'])?></span></td>
                    <td style="white-space:nowrap;"><?=date('d M, H:i',strtotime($r['reported_at']))?></td>
                </tr>
                <?php endwhile;?>
                </tbody>
            </table>
        </div>
        <?php endif;?>
    </section>

    <!-- ── SOS HISTORY ── -->
    <section class="card" id="mySOSHistory">
        <h2 class="section-title" style="color:#991b1b;border-bottom-color:#fee2e2;">
            <i class="fas fa-broadcast-tower" style="color:#991b1b;"></i> SOS History
        </h2>
        <?php if($mySOSAlerts->num_rows===0): ?>
            <p class="no-data">No SOS alerts sent yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Status</th><th>Sent At</th></tr></thead>
                <tbody>
                <?php while($s=$mySOSAlerts->fetch_assoc()): ?>
                <tr <?=$s['status']==='active'?'style="background:#fff5f5;"':''?>>
                    <td><strong style="color:#dc2626;">#<?=$s['id']?></strong></td>
                    <td style="max-width:120px;word-break:break-word;"><?=htmlspecialchars($s['fullname']?:'N/A')?></td>
                    <td style="white-space:nowrap;"><?=htmlspecialchars($s['phone']?:'N/A')?></td>
                    <td><span class="badge <?=$s['status']==='active'?'status-pending':($s['status']==='responded'?'status-dispatched':'status-resolved')?>"><?=ucfirst($s['status'])?></span></td>
                    <td style="white-space:nowrap;"><?=date('d M, H:i',strtotime($s['created_at']))?></td>
                </tr>
                <?php endwhile;?>
                </tbody>
            </table>
        </div>
        <?php endif;?>
    </section>

</main>

<script>
function el(id){return document.getElementById(id);}

/* Clock */
function tick(){
    var n=new Date();
    el('dashClock').textContent=n.toLocaleTimeString('en-NG',{hour12:false});
    el('dashDate').textContent=n.toLocaleDateString('en-NG',{weekday:'short',day:'numeric',month:'short'});
    var s=el('sosTime');if(s)s.textContent=n.toLocaleTimeString('en-NG',{hour12:false});
}
tick();setInterval(tick,1000);

/* Sidebar */
function openSidebar(){el('sidebar').classList.add('open');el('sidebarOverlay').classList.add('show');}
function closeSidebar(){el('sidebar').classList.remove('open');el('sidebarOverlay').classList.remove('show');}

/* Tabs */
function switchTab(tab){
    ['tabSOS','tabReport','panelSOS','panelReport'].forEach(function(id){el(id).classList.remove('active');});
    var t=tab.charAt(0).toUpperCase()+tab.slice(1);
    el('tab'+t).classList.add('active');
    el('panel'+t).classList.add('active');
}

/* Landmarks */
var LANDMARKS={
    'Yobe':[
        {name:'Govt House Damaturu',lat:11.7471,lng:11.9608},
        {name:'Central Market Damaturu',lat:11.7467,lng:11.9596},
        {name:'Yobe State University',lat:11.7520,lng:11.9650},
        {name:'Potiskum Market',lat:11.7071,lng:11.0796},
        {name:'FMC Nguru',lat:12.8794,lng:10.4565},
        {name:'Gashua Hospital',lat:12.8714,lng:11.0453}
    ],
    'Borno':[
        {name:'Govt House Maiduguri',lat:11.8333,lng:13.1500},
        {name:'Unimaid',lat:11.8367,lng:13.1536},
        {name:'Specialist Hospital',lat:11.8450,lng:13.1550},
        {name:'Monday Market',lat:11.8500,lng:13.1550}
    ],
    'Adamawa':[
        {name:'Govt House Yola',lat:9.2035,lng:12.4954},
        {name:'Modibbo Adama Univ',lat:9.2281,lng:12.4698},
        {name:'Specialist Hospital Yola',lat:9.2100,lng:12.4800}
    ],
    'Bauchi':[
        {name:'Govt House Bauchi',lat:10.3158,lng:9.8442},
        {name:'ATBU Bauchi',lat:10.3025,lng:9.8408},
        {name:'Bauchi Specialist Hospital',lat:10.3100,lng:9.8400}
    ],
    'Gombe':[
        {name:'Govt House Gombe',lat:10.2791,lng:11.1672},
        {name:'FTH Gombe',lat:10.2800,lng:11.1700},
        {name:'Central Market Gombe',lat:10.2833,lng:11.1667}
    ],
    'Kano':[
        {name:'Govt House Kano',lat:12.0022,lng:8.5920},
        {name:'BUK Kano',lat:11.9804,lng:8.4844},
        {name:'Aminu Kano Airport',lat:12.0476,lng:8.5246}
    ]
};

function selectState(state){
    document.querySelectorAll('.state-btn').forEach(function(b){
        b.classList.remove('selected');
        if(b.textContent.indexOf(state)>-1)b.classList.add('selected');
    });
    el('landmarkSection').style.display='block';
    var grid=el('landmarkGrid');
    grid.innerHTML='';
    (LANDMARKS[state]||[]).forEach(function(lm){
        var btn=document.createElement('button');
        btn.type='button';btn.className='landmark-btn';
        btn.textContent='📍 '+lm.name;
        btn.onclick=function(){
            el('address').value=lm.name+', '+state+' State';
            el('lat').value=lm.lat;el('lng').value=lm.lng;
            document.querySelectorAll('.landmark-btn').forEach(function(b){b.classList.remove('picked');});
            btn.classList.add('picked');
        };
        grid.appendChild(btn);
    });
}

/* Severity */
document.querySelectorAll('input[name="severity"]').forEach(function(r){
    r.addEventListener('change',function(){updateMeter(this.value);});
});
function updateMeter(level){
    var cfg={
        low:{w:'25%',c:'#16a34a',t:'🟢 Low — Minor issue.',bg:'linear-gradient(135deg,#16a34a,#15803d)',btn:'<i class="fas fa-paper-plane"></i> SUBMIT REPORT'},
        moderate:{w:'50%',c:'#d97706',t:'🟡 Moderate — Urgent.',bg:'linear-gradient(135deg,#d97706,#b45309)',btn:'<i class="fas fa-paper-plane"></i> SUBMIT URGENT REPORT'},
        high:{w:'75%',c:'#dc2626',t:'🔴 High — Serious risk!',bg:'linear-gradient(135deg,#dc2626,#b91c1c)',btn:'<i class="fas fa-exclamation-triangle"></i> HIGH PRIORITY REPORT'},
        critical:{w:'100%',c:'#7f1d1d',t:'🚨 CRITICAL — All units now!',bg:'linear-gradient(135deg,#7f1d1d,#450a0a)',btn:'<i class="fas fa-skull-crossbones"></i> CRITICAL EMERGENCY'}
    };
    var c=cfg[level];if(!c)return;
    el('meterFill').style.width=c.w;el('meterFill').style.background=c.c;
    el('meterText').textContent=c.t;el('meterText').style.color=c.c;
    el('submitBtn').style.background=c.bg;el('submitBtn').innerHTML=c.btn;
    if(level==='critical')el('submitBtn').classList.add('btn-pulse');
    else el('submitBtn').classList.remove('btn-pulse');
}

/* SOS */
var ADMIN_PHONE='<?= htmlspecialchars($adminPhone) ?>';
var sosTimer=null,sosSent=false;

function startSOSHold(e){
    if(e)e.preventDefault();
    if(sosSent)return;
    var btn=el('sosBtn');
    var ov=document.createElement('div');
    ov.className='sos-countdown-overlay';ov.id='sosOverlay';ov.textContent='3';
    btn.appendChild(ov);
    var count=3;
    sosTimer=setInterval(function(){
        count--;
        if(ov)ov.textContent=count>0?count:'📞';
        if(count<=0){
            clearInterval(sosTimer);sosTimer=null;
            var o=el('sosOverlay');if(o)o.remove();
            fireSOS();
        }
    },1000);
}
function cancelSOSHold(){
    if(sosTimer){clearInterval(sosTimer);sosTimer=null;}
    var ov=el('sosOverlay');if(ov)ov.remove();
}
function fireSOS(){
    if(sosSent)return;sosSent=true;
    var btn=el('sosBtn');
    btn.style.cssText='width:86px;height:86px;border-radius:50%;border:4px solid #16a34a;background:radial-gradient(circle,#22c55e,#15803d);animation:none;box-shadow:0 0 0 6px rgba(22,163,74,.2);cursor:default;position:relative;flex-shrink:0;';
    btn.querySelector('.sos-btn-label').innerHTML='<i class="fas fa-phone-volume" style="font-size:1.3rem;margin-bottom:1px;"></i><span style="font-size:.82rem;font-weight:900;">CALLING</span>';

    var lat=el('lat').value||'';
    var lng=el('lng').value||'';
    var address=el('address')?el('address').value||'Not specified':'Not specified';

    var fd=new FormData();
    fd.append('latitude',lat);fd.append('longitude',lng);
    fd.append('address',address);
    fd.append('message','SOS ALERT — Admin called directly. Immediate help needed!');

    fetch('sos.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(data){
        var rd=el('sosResult');rd.style.display='flex';
        rd.className='sos-result-ok';
        rd.innerHTML='<i class="fas fa-phone-volume" style="font-size:1rem;flex-shrink:0;"></i>'+
            '<div><strong>Calling Admin: '+ADMIN_PHONE+(data.success?' — Alert #'+data.sos_id+' saved':'')+'</strong><br>'+
            '<span style="font-weight:400;font-size:.65rem;">Stay on the line. Admin has been notified.</span></div>';
    })
    .catch(function(){
        var rd=el('sosResult');rd.style.display='flex';rd.className='sos-result-ok';
        rd.innerHTML='<i class="fas fa-phone-volume" style="font-size:1rem;flex-shrink:0;"></i>'+
            '<div><strong>Calling Admin: '+ADMIN_PHONE+'</strong><br>'+
            '<span style="font-weight:400;font-size:.65rem;">Stay on the line.</span></div>';
    });

    setTimeout(function(){window.location.href='tel:'+ADMIN_PHONE;},400);

    setTimeout(function(){
        sosSent=false;btn.style.cssText='';
        btn.querySelector('.sos-btn-label').innerHTML='<i class="fas fa-phone" style="font-size:1.3rem;margin-bottom:1px;"></i><span style="font-size:.88rem;font-weight:900;letter-spacing:.08em;">SOS</span>';
    },12000);
}

window.addEventListener('load',function(){selectState('Yobe');});
</script>
</body>
</html>