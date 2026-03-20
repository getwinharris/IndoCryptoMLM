<?php
require_once __DIR__ . '/../config/session.php';
requireLogin(); if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();

db()->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL")->execute([$user['id']]);

$notifications = db()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$notifications->execute([$user['id']]);
$notifications = $notifications->fetchAll();

$pageTitle = 'Inbox Notifications';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Your Notifications</h1></div>

<div class="card">
  <?php if(empty($notifications)): ?>
    <div style="padding:4rem 1rem;text-align:center;color:var(--muted)">
      <i class="fas fa-bell-slash" style="font-size:3rem;margin-bottom:1rem;color:rgba(255,255,255,.1)"></i>
      <p>No notifications yet.</p>
    </div>
  <?php else: ?>
    <div style="display:grid;gap:1rem">
    <?php foreach($notifications as $n): ?>
      <div style="display:flex;gap:1rem;padding:1.5rem;background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:12px">
        <div style="font-size:1.5rem;color:var(--<?= $n['type']==='success'?'success':($n['type']==='danger'?'danger':($n['type']==='warning'?'warning':'primary')) ?>)"><i class="fas fa-<?= $n['type']==='success'?'check-circle':($n['type']==='danger'?'times-circle':'info-circle') ?>"></i></div>
        <div>
          <div style="font-size:1.05rem;margin-bottom:.4rem"><?= htmlspecialchars($n['message']) ?></div>
          <div style="font-size:.85rem;color:var(--muted)"><?= date('F j, Y, g:i a', strtotime($n['created_at'])) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
