<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$users = db()->query("SELECT u.*, (SELECT COUNT(*) FROM investments WHERE user_id=u.id AND status='active') as active_inv, (SELECT COUNT(*) FROM users WHERE referred_by=u.id) as ref_count FROM users u WHERE u.is_admin=0 ORDER BY u.created_at DESC")->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Registered Users</h1></div>

<div class="card">
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>Name</th><th>Email</th><th>Tier</th><th>Wallet Bal</th><th>Active Inv.</th><th>Referrals</th><th>Joined</th></tr></thead>
    <tbody>
      <?php if(empty($users)): ?>
      <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">No users found.</td></tr>
      <?php else: foreach($users as $u): ?>
      <tr>
        <td style="font-weight:700"><?= htmlspecialchars($u['name']) ?></td>
        <td style="color:var(--muted);font-size:.9rem"><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="badge-tier badge-<?= $u['tier'] ?>"><?= $u['tier'] ?></span></td>
        <td style="color:var(--primary);font-weight:600">$<?= number_format($u['wallet_balance'], 2) ?></td>
        <td><?= $u['active_inv'] ?></td>
        <td><?= $u['ref_count'] ?></td>
        <td style="font-size:.85rem;color:var(--muted)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
