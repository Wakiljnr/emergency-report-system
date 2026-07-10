<?php
/* ============================================================
   admin.php — NERS Nigeria Admin Panel
   Admin can ONLY RECEIVE SOS alerts and reports — no SOS button,
   no SOS-sending capability exists anywhere on this page.
   ============================================================ */

session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Only admins may view this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$fullname = $_SESSION['fullname'];

// ── Handle SOS status update (respond / close) ──────────────
if (isset($_POST['update_sos_status'])) {
    $sos_id = (int)$_POST['sos_id'];
    $status = $_POST['status'];
    $allowed = ['active', 'responded', 'closed'];
    if (in_array($status, $allowed, true)) {
        $upd = $conn->prepare("UPDATE sos_alerts SET status = ? WHERE id = ?");
        $upd->bind_param("si", $status, $sos_id);
        $upd->execute();
    }
    header("Location: admin.php?success=SOS+status+updated");
    exit();
}

// ── Handle report status update (dispatch / resolve) ────────
if (isset($_POST['update_report_status'])) {
    $report_id = (int)$_POST['report_id'];
    $status = $_POST['status'];
    $allowed = ['pending', 'dispatched', 'resolved'];
    if (in_array($status, $allowed, true)) {
        $upd = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $upd->bind_param("si", $status, $report_id);
        $upd->execute();
    }
    header("Location: admin.php?success=Report+status+updated");
    exit();
}

$error   = $_GET['error']   ?? '';
$success = $_GET['success'] ?? '';

// ── Data for stat cards (system-wide, not just this admin) ──
$total      = $conn->query("SELECT COUNT(*) c FROM reports")->fetch_assoc()['c'];
$pending    = $conn->query("SELECT COUNT(*) c FROM reports WHERE status='pending'")->fetch_assoc()['c'];
$dispatched = $conn->query("SELECT COUNT(*) c FROM reports WHERE status='dispatched'")->fetch_assoc()['c'];
$resolved   = $conn->query("SELECT COUNT(*) c FROM reports WHERE status='resolved'")->fetch_assoc()['c'];
$sos_active = $conn->query("SELECT COUNT(*) c FROM sos_alerts WHERE status='active'")->fetch_assoc()['c'];
$sos_total  = $conn->query("SELECT COUNT(*) c FROM sos_alerts")->fetch_assoc()['c'];

// ── Incoming SOS alerts — from ALL users (admin RECEIVES only) ──
$sosAlerts = $conn->query(
    "SELECT id, fullname, phone, status, created_at
     FROM sos_alerts
     ORDER BY (status='active') DESC, created_at DESC
     LIMIT 50"
);

// ── All emergency reports — from ALL users ──
$reports = $conn->query(
    "SELECT r.id, u.fullname, u.phone, r.type, r.severity, r.description,
            r.address, r.status, r.reported_at
     FROM reports r
     JOIN users u ON r.user_id = u.id
     ORDER BY (r.status='pending') DESC, r.reported_at DESC
     LIMIT 50"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>NERS Admin Panel</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;max-width:100%;overflow-x:hidden;font-family:'Inter',sans-serif;}
.dash-body{display:flex;min-height:100vh;background:#f0f4f0;}

/* Sidebar */
.sidebar{width:195px;min-height:100vh;background:linear-gradient(180deg,#001a00,#003300);position:fixed;left:0;top:0;bottom:0;z-index:200;display:flex;flex-direction:column;transition:transform .3s;}
.sidebar-brand{display:flex;align-items:center;gap:8px;padding:11px 13px;border-bottom:1px solid rgba(0,166,81,.3);}
.brand-icon{font-size:1.3rem;}
.s-title{font-size:.8rem;font-weight:800;color:#00e676;}
.s-sub{font-size:.58rem;color:rgba(255,255,255,.5);}
.sidebar-nav{flex:1;padding:6px 0;}
.nav-link{display:flex;align-items:center;gap:7px;padding:7px 13px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.72rem;font-weight:500;border-left:3px solid transparent;transition:all .2s;}
.nav-link:hover,.nav-link.active{background:rgba(0,166,81,.2);color:#00e676;border-left-color:#00e676;}
.nav-link.logout{color:rgba(255,120,120,.8);}
.nav-link.logout:hover{background:rgba(255,0,0,.12);color:#ff8080;}
.sidebar-hotline{padding:9px 13px;background:rgba(0,166,81,.2);border-top:1px solid rgba(0,166,81,.3);color:rgba(255,255,255,.8);font-size:.68rem;display:flex;align-items:center;gap:5px;}
.sidebar-hotline strong{color:#00e676;}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:199;}
.sidebar-overlay.show{display:block;}
.hamburger{display:none;background:#006400;color:white;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;font-size:.78rem;font-family:'Inter',sans-serif;align-items:center;gap:4px;flex-shrink:0;}

/* Main */
.dash-main{margin-left:195px;flex:1;padding:0 13px 16px;max-width:calc(100% - 195px);}

/* Topbar */
.topbar{position:sticky;top:0;z-index:100;background:#f0f4f0;padding:7px 0;margin-bottom:10px;border-bottom:2px solid #d0e8d0;display:flex;align-items:center;flex-wrap:wrap;gap:6px;width:100%;}
.topbar-greet{display:flex;align-items:center;gap:6px;font-size:.76rem;color:#003300;}
.topbar-greet i{color:#006400;}
.topbar-meta{display:flex;gap:5px;flex-wrap:wrap;margin-left:auto;}
.meta-tag{background:white;border:1px solid #c8e0c8;border-radius:14px;padding:3px 8px;font-size:.66rem;color:#333;display:flex;align-items:center;gap:3px;}
.meta-tag i{color:#006400;font-size:.6rem;}
.admin-tag{background:#003300;color:#00e676;border-color:#003300;font-weight:700;}

/* Alerts */
.alert{padding:7px 11px;border-radius:7px;margin-bottom:8px;font-size:.72rem;display:flex;align-items:center;gap:6px;font-weight:500;width:100%;}
.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}

/* Stat cards */
.stat-cards{display:grid;grid-template-columns:repeat(6,1fr);gap:6px;margin-bottom:10px;width:100%;}
.stat-card{background:white;border-radius:8px;padding:9px 8px;border-left:3px solid #006400;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.stat-card.yellow{border-left-color:#d97706;}.stat-card.blue{border-left-color:#1d4ed8;}.stat-card.green{border-left-color:#059669;}.stat-card.red{border-left-color:#dc2626;}
.stat-num{font-size:1.3rem;font-weight:800;color:#003300;line-height:1;}
.stat-label{font-size:.58rem;color:#666;margin-top:2px;display:flex;align-items:center;gap:3px;}

/* Cards */
.card{background:white;border-radius:10px;padding:11px;margin-bottom:10px;box-shadow:0 1px 6px rgba(0,0,0,.06);border:1px solid #e0ece0;width:100%;overflow:hidden;}
.section-title{font-size:.8rem;font-weight:700;color:#003300;margin-bottom:10px;display:flex;align-items:center;gap:6px;padding-bottom:7px;border-bottom:2px solid #e8f4e8;}
.section-title i{color:#006400;}
.no-data{text-align:center;padding:16px;color:#888;font-size:.72rem;}

/* Live SOS banner */
.live-sos-banner{background:linear-gradient(135deg,#dc2626,#7f1d1d);color:white;border-radius:10px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:10px;animation:bannerPulse 2s ease-in-out infinite;}
.live-sos-banner i{font-size:1.3rem;}
.live-sos-banner strong{font-size:.82rem;}
.live-sos-banner span{font-size:.68rem;opacity:.9;}
@keyframes bannerPulse{0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.4);}50%{box-shadow:0 0 0 8px rgba(220,38,38,0);}}

/* Table */
.table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}
.data-table{width:100%;min-width:480px;border-collapse:collapse;}
.data-table th{background:#003300;color:white;padding:6px 7px;text-align:left;font-weight:600;font-size:.58rem;text-transform:uppercase;letter-spacing:.04em;}
.data-table td{padding:6px 7px;border-bottom:1px solid #e8f0e8;vertical-align:middle;font-size:.65rem;}
.data-table tr:hover{background:#f8fbf8;}

/* Badges */
.badge{padding:2px 6px;border-radius:8px;font-size:.55rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap;}
.badge-police{background:#dbeafe;color:#1e40af;}.badge-fire{background:#fee2e2;color:#991b1b;}.badge-medical{background:#d1fae5;color:#065f46;}.badge-other{background:#f3e8ff;color:#6b21a8;}
.sev-badge-low{background:#dcfce7;color:#166534;}.sev-badge-moderate{background:#fef3c7;color:#92400e;}.sev-badge-high{background:#fee2e2;color:#991b1b;}.sev-badge-critical{background:#450a0a;color:#fff;}
.status-pending{background:#fef3c7;color:#92400e;}.status-dispatched{background:#dbeafe;color:#1e3a8a;}.status-resolved{background:#d1fae5;color:#065f46;}
.sos-status-active{background:#fee2e2;color:#991b1b;}.sos-status-responded{background:#dbeafe;color:#1e3a8a;}.sos-status-closed{background:#d1fae5;color:#065f46;}

/* Inline status-update select in tables */
.inline-status{padding:3px 5px;font-size:.6rem;border:1px solid #d0e0d0;border-radius:5px;font-family:'Inter',sans-serif;background:white;color:#333;}

@media(max-width:768px){
    .sidebar{transform:translateX(-100%);}
    .sidebar.open{transform:translateX(0);}
    .dash-main{margin-left:0!important;padding:0 8px 12px!important;max-width:100%!important;}
    .hamburger{display:flex;}
    .stat-cards{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:480px){
    .stat-cards{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body class="dash-body">

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🦅</span>
        <div><div class="s-title">NERS</div><div class="s-sub">Admin Panel</div></div>
    </div>
    <nav class="sidebar-nav">
        <a href="admin.php" class="nav-link active"><i class="fas fa-home"></i> Overview</a>
        <a href="#sosAlerts" class="nav-link" onclick="closeSidebar()"><i class="fas fa-broadcast-tower"></i> SOS Alerts</a>
        <a href="#allReports" class="nav-link" onclick="closeSidebar()"><i class="fas fa-list"></i> All Reports</a>
        <a href="logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    <div class="sidebar-hotline">
        <i class="fas fa-phone-volume"></i>
        <span>Emergency: <strong>112</strong></span>
    </div>
</aside>

<main class="dash-main">

    <div class="topbar">
        <button class="hamburger" onclick="openSidebar()"><i class="fas fa-bars"></i> Menu</button>
        <div class="topbar-greet">
            <i class="fas fa-user-shield"></i>
            <span>Hello, <strong><?= htmlspecialchars($fullname) ?></strong></span>
        </div>
        <div class="topbar-meta">
            <span class="meta-tag admin-tag"><i class="fas fa-user-shield"></i> Admin</span>
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

    <?php if($sos_active > 0): ?>
    <div class="live-sos-banner">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong><?= $sos_active ?> ACTIVE SOS ALERT<?= $sos_active > 1 ? 'S' : '' ?></strong><br>
            <span>Immediate attention required — see SOS Alerts below</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="stat-cards">
        <div class="stat-card"><div class="stat-num"><?=$total?></div><div class="stat-label"><i class="fas fa-file-alt"></i>Reports</div></div>
        <div class="stat-card yellow"><div class="stat-num"><?=$pending?></div><div class="stat-label"><i class="fas fa-hourglass-half"></i>Pending</div></div>
        <div class="stat-card blue"><div class="stat-num"><?=$dispatched?></div><div class="stat-label"><i class="fas fa-truck"></i>Dispatched</div></div>
        <div class="stat-card green"><div class="stat-num"><?=$resolved?></div><div class="stat-label"><i class="fas fa-check"></i>Resolved</div></div>
        <div class="stat-card red"><div class="stat-num" style="color:#dc2626;"><?=$sos_active?></div><div class="stat-label"><i class="fas fa-broadcast-tower" style="color:#dc2626;"></i>Active SOS</div></div>
        <div class="stat-card"><div class="stat-num"><?=$sos_total?></div><div class="stat-label"><i class="fas fa-history"></i>Total SOS</div></div>
    </div>

    <!-- ══ INCOMING SOS ALERTS (admin RECEIVES only — no send button anywhere) ══ -->
    <section class="card" id="sosAlerts">
        <h2 class="section-title" style="color:#991b1b;border-bottom-color:#fee2e2;">
            <i class="fas fa-broadcast-tower" style="color:#991b1b;"></i> Incoming SOS Alerts
        </h2>
        <?php if($sosAlerts->num_rows===0): ?>
            <p class="no-data">No SOS alerts received yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Phone</th><th>Status</th><th>Received At</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php while($s=$sosAlerts->fetch_assoc()): ?>
                <tr <?=$s['status']==='active'?'style="background:#fff5f5;"':''?>>
                    <td><strong style="color:#dc2626;">#<?=$s['id']?></strong></td>
                    <td><?=htmlspecialchars($s['fullname'])?></td>
                    <td>
                        <a href="tel:<?=htmlspecialchars($s['phone'])?>" style="color:#006400;text-decoration:none;font-weight:600;">
                            <i class="fas fa-phone"></i> <?=htmlspecialchars($s['phone'])?>
                        </a>
                    </td>
                    <td><span class="badge sos-status-<?=$s['status']?>"><?=ucfirst($s['status'])?></span></td>
                    <td style="white-space:nowrap;"><?=date('d M, H:i',strtotime($s['created_at']))?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="sos_id" value="<?=$s['id']?>">
                            <input type="hidden" name="update_sos_status" value="1">
                            <select name="status" class="inline-status" onchange="this.form.submit()">
                                <option value="active"    <?=$s['status']==='active'?'selected':''?>>Active</option>
                                <option value="responded" <?=$s['status']==='responded'?'selected':''?>>Responded</option>
                                <option value="closed"    <?=$s['status']==='closed'?'selected':''?>>Closed</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ══ ALL EMERGENCY REPORTS ══ -->
    <section class="card" id="allReports">
        <h2 class="section-title"><i class="fas fa-list-alt"></i> All Emergency Reports</h2>
        <?php if($reports->num_rows===0): ?>
            <p class="no-data">No reports submitted yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Reported By</th><th>Phone</th><th>Type</th><th>Sev</th><th>Address</th><th>Status</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php while($r=$reports->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?=$r['id']?></strong></td>
                    <td><?=htmlspecialchars($r['fullname'])?></td>
                    <td><a href="tel:<?=htmlspecialchars($r['phone'])?>" style="color:#006400;text-decoration:none;"><?=htmlspecialchars($r['phone'])?></a></td>
                    <td><span class="badge badge-<?=strtolower($r['type'])?>"><?=htmlspecialchars($r['type'])?></span></td>
                    <td><span class="badge sev-badge-<?=$r['severity']??'moderate'?>"><?=ucfirst(substr($r['severity']??'mod',0,3))?></span></td>
                    <td style="max-width:110px;word-break:break-word;"><?=htmlspecialchars($r['address']?:'N/A')?></td>
                    <td><span class="badge status-<?=$r['status']?>"><?=ucfirst($r['status'])?></span></td>
                    <td style="white-space:nowrap;"><?=date('d M, H:i',strtotime($r['reported_at']))?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="report_id" value="<?=$r['id']?>">
                            <input type="hidden" name="update_report_status" value="1">
                            <select name="status" class="inline-status" onchange="this.form.submit()">
                                <option value="pending"    <?=$r['status']==='pending'?'selected':''?>>Pending</option>
                                <option value="dispatched" <?=$r['status']==='dispatched'?'selected':''?>>Dispatched</option>
                                <option value="resolved"   <?=$r['status']==='resolved'?'selected':''?>>Resolved</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

</main>

<script>
function el(id){return document.getElementById(id);}

/* Clock */
function tick(){
    var n=new Date();
    el('dashClock').textContent=n.toLocaleTimeString('en-NG',{hour12:false});
    el('dashDate').textContent=n.toLocaleDateString('en-NG',{weekday:'short',day:'numeric',month:'short'});
}
tick();setInterval(tick,1000);

/* Sidebar */
function openSidebar(){el('sidebar').classList.add('open');el('sidebarOverlay').classList.add('show');}
function closeSidebar(){el('sidebar').classList.remove('open');el('sidebarOverlay').classList.remove('show');}

/* Auto-refresh every 20s so new SOS alerts show up without manual reload */
setTimeout(function(){ location.reload(); }, 20000);
</script>
</body>
</html>