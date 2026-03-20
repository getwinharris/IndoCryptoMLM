<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
$user = currentUser();

$unreadCount = 0;
$notifications = [];
if (!isAdmin()) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL");
    $stmt->execute([$user['id']]);
    $unreadCount = (int)$stmt->fetchColumn();
    
    $notifStmt = db()->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
    $notifStmt->execute([$user['id']]);
    $notifications = $notifStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/nouislider/15.7.0/nouislider.min.css" rel="stylesheet">
<link rel="stylesheet" href="/styles.css?v=2">
<style>
:root{--bg:#0a0c10;--bg2:#10131a;--card:#141820;--border:rgba(255,255,255,.07);--primary:#00e5ff;--primary-glow:rgba(0,229,255,.3);--secondary:#7000ff;--accent:#ff007f;--text:#fff;--muted:#8892a4;--success:#00c853;--warning:#ffab00;--danger:#ff1744}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Outfit',sans-serif}
body{background:var(--bg);color:var(--text);display:flex;min-height:100vh}
.sidebar{width:260px;min-height:100vh;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:1.5rem 0;position:fixed;top:0;left:0;z-index:100}
.sidebar .logo{padding:.5rem 1.5rem 1.5rem;font-size:1.2rem;font-weight:800;border-bottom:1px solid var(--border)}
.sidebar .logo span{color:var(--primary)}
.sidebar nav{flex:1;padding:1rem 0}
.sidebar nav a{display:flex;align-items:center;gap:.8rem;padding:.8rem 1.5rem;color:var(--muted);text-decoration:none;font-size:.95rem;transition:all .2s;border-left:3px solid transparent}
.sidebar nav a:hover,.sidebar nav a.active{color:var(--text);background:rgba(0,229,255,.05);border-left-color:var(--primary)}
.sidebar nav a i{width:20px;text-align:center}
.sidebar .user-badge{padding:1rem 1.5rem;border-top:1px solid var(--border);font-size:.85rem;color:var(--muted)}
.main{margin-left:260px;flex:1;padding:2rem;min-height:100vh}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.topbar h1{font-size:1.6rem;font-weight:700}
.bell-container{position:relative;cursor:pointer;background:rgba(255,255,255,.05);width:45px;height:45px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background .2s}
.bell-container:hover{background:rgba(255,255,255,.1)}
.bell-container i{font-size:1.2rem;color:var(--muted)}
.bell-badge{position:absolute;top:5px;right:5px;background:var(--danger);color:#fff;font-size:.65rem;font-weight:700;padding:2px 5px;border-radius:10px;line-height:1}
.notif-dropdown{position:absolute;top:55px;right:0;width:320px;background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.5);z-index:200;display:none;flex-direction:column}
.notif-dropdown.show{display:flex}
.notif-header{padding:1rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-weight:700}
.notif-item{padding:1rem;border-bottom:1px solid var(--border);font-size:.85rem;color:var(--text);display:flex;gap:.8rem;transition:background .2s}
.notif-item:hover{background:rgba(255,255,255,.02)}
.notif-item i{font-size:1.2rem;margin-top:2px}
.notif-item .success i{color:var(--success)}
.notif-item .info i{color:var(--primary)}
.badge-tier{padding:.3rem .8rem;border-radius:50px;font-size:.78rem;font-weight:700}
.badge-Bronze{background:rgba(205,127,50,.2);color:#cd7f32;border:1px solid rgba(205,127,50,.4)}
.badge-Silver{background:rgba(192,192,192,.2);color:#c0c0c0;border:1px solid rgba(192,192,192,.4)}
.badge-Gold{background:rgba(255,215,0,.15);color:#ffd700;border:1px solid rgba(255,215,0,.4)}
.badge-Diamond{background:rgba(0,229,255,.1);color:var(--primary);border:1px solid var(--primary)}
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.5rem}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.2rem}
.stat-card .label{font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:.4rem}
.stat-card .value{font-size:1.8rem;font-weight:800}
.stat-card .value.cyan{color:var(--primary)}
.stat-card .value.green{color:var(--success)}
.stat-card .value.purple{color:#b388ff}
.stat-card .value.gold{color:#ffd700}
table{width:100%;border-collapse:collapse}
th,td{padding:.8rem 1rem;text-align:left;border-bottom:1px solid var(--border);font-size:.9rem}
th{color:var(--muted);font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.5px}
.btn{padding:.6rem 1.2rem;border-radius:8px;border:none;cursor:pointer;font-weight:600;font-size:.9rem;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem}
.btn-primary{background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;box-shadow:0 0 20px var(--primary-glow)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 0 30px var(--primary-glow)}
.btn-sm{padding:.4rem .8rem;font-size:.8rem}
.btn-danger{background:rgba(255,23,68,.15);color:var(--danger);border:1px solid rgba(255,23,68,.3)}
.btn-success{background:rgba(0,200,83,.15);color:var(--success);border:1px solid rgba(0,200,83,.3)}
.alert{padding:1rem 1.2rem;border-radius:10px;margin-bottom:1.5rem;font-size:.95rem}
.alert-success{background:rgba(0,200,83,.1);border:1px solid rgba(0,200,83,.3);color:var(--success)}
.alert-danger{background:rgba(255,23,68,.1);border:1px solid rgba(255,23,68,.3);color:var(--danger)}
.status-badge{padding:.2rem .6rem;border-radius:50px;font-size:.75rem;font-weight:600}
.status-active{background:rgba(0,200,83,.15);color:var(--success)}
.status-pending{background:rgba(255,171,0,.15);color:var(--warning)}
.status-completed{background:rgba(0,229,255,.1);color:var(--primary)}
.status-rejected{background:rgba(255,23,68,.1);color:var(--danger)}
@media(max-width:800px){.sidebar{transform:translateX(-100%)}.main{margin-left:0}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="logo"><img src="/assets/logo.svg" alt="IGS" style="height:35px; border-radius:50%; vertical-align:middle; margin-right:8px;"> Indo <span>Global</span></div>
  <nav>
    <?php if (isAdmin()): ?>
    <a href="/app/admin/dashboard.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/dashboard') ? 'class="active"':'' ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/app/admin/users.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/users') ? 'class="active"':'' ?>><i class="fas fa-users"></i> Users</a>
    <a href="/app/admin/kyc.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/kyc') ? 'class="active"':'' ?>><i class="fas fa-id-card"></i> KYC Approvals</a>
    <a href="/app/admin/investments.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/investments') ? 'class="active"':'' ?>><i class="fas fa-chart-line"></i> Investments</a>
    <a href="/app/admin/withdrawals.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/withdrawals') ? 'class="active"':'' ?>><i class="fas fa-money-check-alt"></i> Withdrawals</a>
    <a href="/app/admin/settings.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/settings') ? 'class="active"':'' ?>><i class="fas fa-cog"></i> Settings</a>
    <a href="/app/admin/plans.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/plans') ? 'class="active"':'' ?>><i class="fas fa-layer-group"></i> Investment Plans</a>
    <a href="/app/admin/mlm.php" <?= str_contains($_SERVER['REQUEST_URI'],'admin/mlm') ? 'class="active"':'' ?>><i class="fas fa-sitemap"></i> MLM Levels</a>
    <?php else: ?>
    <a href="/app/user/dashboard.php" <?= str_contains($_SERVER['REQUEST_URI'],'user/dashboard') ? 'class="active"':'' ?>><i class="fas fa-home"></i> Dashboard</a>
    <a href="/app/user/invest.php" <?= str_contains($_SERVER['REQUEST_URI'],'invest') ? 'class="active"':'' ?>><i class="fas fa-dollar-sign"></i> Invest</a>
    <a href="/app/user/earnings.php" <?= str_contains($_SERVER['REQUEST_URI'],'earnings') ? 'class="active"':'' ?>><i class="fas fa-chart-bar"></i> Earnings</a>
    <a href="/app/user/referrals.php" <?= str_contains($_SERVER['REQUEST_URI'],'referrals') ? 'class="active"':'' ?>><i class="fas fa-users"></i> Referrals</a>
    <a href="/app/user/withdraw.php" <?= str_contains($_SERVER['REQUEST_URI'],'withdraw') ? 'class="active"':'' ?>><i class="fas fa-wallet"></i> Withdraw</a>
    <a href="/app/user/profile.php" <?= str_contains($_SERVER['REQUEST_URI'],'profile') ? 'class="active"':'' ?>><i class="fas fa-user"></i> My Profile & KYC</a>
    <a href="/app/auth/logout.php" style="color:#ff1744"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php endif; ?>
  </nav>
  <div class="user-badge">
    <div style="font-weight:600;color:var(--text);margin-bottom:.3rem"><?= htmlspecialchars($user['name']) ?></div>
    <span class="badge-tier badge-<?= $user['tier'] ?>"><?= $user['tier'] ?></span>
  </div>
</aside>
<main class="main">
<?php if (!isAdmin()): ?>
<div style="position:fixed;top:1rem;right:2rem;z-index:150">
    <div class="bell-container" onclick="document.getElementById('notif-drop').classList.toggle('show')">
        <i class="fas fa-bell"></i>
        <?php if($unreadCount>0): ?><span class="bell-badge"><?= $unreadCount ?></span><?php endif; ?>
        
        <div class="notif-dropdown" id="notif-drop" onclick="event.stopPropagation()">
            <div class="notif-header">
                Notifications
                <?php if($unreadCount>0): ?><a href="/api/read_notifications.php" style="color:var(--primary);font-size:.8rem;text-decoration:none;font-weight:500">Mark all read</a><?php endif; ?>
            </div>
            <?php if(empty($notifications)): ?>
                <div style="padding:2rem 1rem;text-align:center;color:var(--muted);font-size:.9rem">No notifications yet.</div>
            <?php else: foreach($notifications as $n): ?>
                <div class="notif-item <?= $n['read_at']?'':'unread' ?>">
                    <div class="<?= htmlspecialchars($n['type']) ?>"><i class="fas fa-<?= $n['type']==='success'?'check-circle':'info-circle' ?>"></i></div>
                    <div>
                        <div style="margin-bottom:.3rem"><?= htmlspecialchars($n['message']) ?></div>
                        <div style="font-size:.75rem;color:var(--muted)"><?= date('M j, Y H:i', strtotime($n['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <a href="/app/user/notifications.php" style="display:block;text-align:center;padding:.8rem;color:var(--primary);text-decoration:none;font-size:.85rem;border-top:1px solid var(--border);background:rgba(0,229,255,.05)">View All Inbox</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('click', e => {
    const drop = document.getElementById('notif-drop');
    if (drop && !e.target.closest('.bell-container')) {
        drop.classList.remove('show');
    }
});
</script>
<?php endif; ?>

<?php
$flash = getFlash();
if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ==='success'?'success':'danger' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>
