<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$investments = db()->query("
    SELECT i.*, u.name, u.email 
    FROM investments i 
    JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC
")->fetchAll();

$pageTitle = 'All Investments';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>All Platform Investments</h1></div>

<div class="card">
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>User</th><th>Amount</th><th>Daily ROI</th><th>Duration</th><th>Earned So Far</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
      <?php if(empty($investments)): ?>
      <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">No investments found.</td></tr>
      <?php else: foreach($investments as $i): ?>
      <tr>
        <td><div><strong><?= htmlspecialchars($i['name']) ?></strong></div><div style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($i['email']) ?></div></td>
        <td style="font-weight:700;color:var(--primary)">$<?= number_format($i['amount'], 2) ?></td>
        <td><?= $i['roi_percent'] ?>%</td>
        <td style="color:var(--muted);font-size:.85rem"><?= $i['days_passed'] ?> / <?= $i['total_days'] ?> days</td>
        <td style="color:var(--success)">$<?= number_format($i['total_earned'], 2) ?></td>
        <td><span class="status-badge status-<?= $i['status']==='active'?'active':'completed' ?>"><?= ucfirst($i['status']) ?></span></td>
        <td style="font-size:.85rem;color:var(--muted)"><?= date('M d, Y', strtotime($i['created_at'])) ?></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
