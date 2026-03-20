<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();

$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$total = db()->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND type IN ('roi','referral_bonus','direct_bonus')");
$total->execute([$user['id']]);
$totalCount = $total->fetchColumn();
$pages = ceil($totalCount / $limit);

$txns = db()->prepare("SELECT * FROM transactions WHERE user_id=? AND type IN ('roi','referral_bonus','direct_bonus') ORDER BY created_at DESC LIMIT ? OFFSET ?");
$txns->bindValue(1, $user['id'], PDO::PARAM_INT);
$txns->bindValue(2, $limit, PDO::PARAM_INT);
$txns->bindValue(3, $offset, PDO::PARAM_INT);
$txns->execute();
$txns = $txns->fetchAll();

$pageTitle = 'Earnings History';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar">
  <h1>Earnings History</h1>
</div>

<div class="card">
  <div style="margin-bottom:1rem;font-weight:700">Detailed Earnings Log</div>
  <table>
    <thead><tr><th>Type</th><th>Amount</th><th>Description</th><th>Date</th></tr></thead>
    <tbody>
      <?php if(empty($txns)): ?>
      <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:2rem">No earnings recorded yet.</td></tr>
      <?php else: foreach($txns as $t): ?>
      <tr>
        <td><span class="status-badge status-active"><?= str_replace('_',' ',ucfirst($t['type'])) ?></span></td>
        <td style="color:var(--success);font-weight:700">+$<?= number_format($t['amount'],2) ?></td>
        <td style="color:var(--muted);font-size:.85rem"><?= htmlspecialchars($t['description']) ?></td>
        <td style="color:var(--muted);font-size:.8rem"><?= date('M d, Y H:i',strtotime($t['created_at'])) ?></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  <?php if($pages>1): ?>
  <div style="margin-top:1.5rem;display:flex;gap:.5rem;justify-content:center">
    <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?= $i ?>" style="padding:.4rem .8rem;background:<?= $i===$page?'var(--primary)':'rgba(255,255,255,.05)' ?>;color:<?= $i===$page?'#000':'var(--muted)' ?>;text-decoration:none;border-radius:4px"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
