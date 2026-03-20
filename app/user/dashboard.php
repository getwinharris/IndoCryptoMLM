<?php
require_once __DIR__ . '/../config/session.php';
requireLogin(); if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();
$roi_pct = (float)setting('roi_percent');
$roi_days = (int)setting('roi_days');
// Stats
$activeInv = db()->prepare("SELECT COUNT(*),COALESCE(SUM(amount),0) FROM investments WHERE user_id=? AND status='active'");
$activeInv->execute([$user['id']]); [$invCount,$invTotal] = $activeInv->fetch(PDO::FETCH_NUM);
$totalEarned = $user['total_earned'];
$walletBal   = $user['wallet_balance'];
$downline     = $user['total_downline'];
// Recent transactions
$txns = db()->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 8");
$txns->execute([$user['id']]); $txns = $txns->fetchAll();

// 7-day chart data
$chartData = db()->prepare("SELECT DATE(created_at) as dt, SUM(amount) as total FROM transactions WHERE user_id=? AND type IN ('roi', 'referral_bonus', 'direct_bonus') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY dt ASC");
$chartData->execute([$user['id']]);
$cData = $chartData->fetchAll(PDO::FETCH_KEY_PAIR);
$dates = []; $amounts = [];
for($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($d));
    $amounts[] = (float)($cData[$d] ?? 0);
}

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar">
  <div>
    <h1>Dashboard 👋</h1>
    <p style="color:var(--muted);font-size:.9rem">Welcome back, <?= htmlspecialchars($user['name']) ?>!</p>
  </div>
  <a href="/app/user/invest.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Investment</a>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
      <div class="label" style="margin-bottom:0">Wallet Balance</div>
      <a href="/app/user/deposit.php" style="color:var(--primary);text-decoration:none;font-size:.75rem;font-weight:700;background:rgba(0,229,255,.1);padding:2px 8px;border-radius:4px"><i class="fas fa-plus"></i> Add Funds</a>
    </div>
    <div class="value cyan">$<?= number_format($walletBal,2) ?></div>
  </div>
  <div class="stat-card"><div class="label">Total Invested</div><div class="value">$<?= number_format($invTotal,2) ?></div></div>
  <div class="stat-card"><div class="label">Total Earned</div><div class="value green">$<?= number_format($totalEarned,2) ?></div></div>
  <div class="stat-card"><div class="label">Downline Volume</div><div class="value gold">$<?= number_format($downline,2) ?></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
  <!-- Referral Code Card -->
  <div class="card">
    <div style="font-weight:700;margin-bottom:1rem">Your Referral Link</div>
    <div style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:.8rem 1rem;font-size:.85rem;word-break:break-all;color:var(--primary)">
      <?= SITE_URL ?>/app/auth/register.php?ref=<?= $user['referral_code'] ?>
    </div>
    <div style="margin-top:.8rem;color:var(--muted);font-size:.85rem">Share this link to earn referral commissions across 10 levels</div>
  </div>
  <!-- Tier Card -->
  <div class="card" style="text-align:center">
    <div style="font-size:3rem;margin-bottom:.5rem">
      <?php $icons=['Bronze'=>'🥉','Silver'=>'🥈','Gold'=>'🥇','Diamond'=>'💎']; echo $icons[$user['tier']]??'🥉'; ?>
    </div>
    <div style="font-weight:700;font-size:1.2rem"><?= $user['tier'] ?> Tier</div>
    <div style="color:var(--muted);font-size:.85rem;margin-top:.3rem">Downline: $<?= number_format($downline,2) ?></div>
    <div style="margin-top:.8rem">
      <?php
      $allTiers = db()->query("SELECT * FROM tiers ORDER BY min_downline ASC")->fetchAll();
      foreach ($allTiers as $t): if ($t['name'] === $user['tier']) continue; if ($t['min_downline'] > $downline): ?>
      <div style="font-size:.8rem;color:var(--muted)">Next: <b style="color:var(--primary)"><?= $t['name'] ?></b> at $<?= number_format($t['min_downline'],0) ?> downline</div>
      <?php break; endif; endforeach; ?>
    </div>
  </div>
</div>

<!-- Earnings Chart -->
<div class="card" style="margin-bottom:1.5rem">
  <div style="font-weight:700;margin-bottom:1rem">Earnings (Last 7 Days)</div>
  <canvas id="earningsChart" height="80"></canvas>
</div>

<!-- Recent Transactions -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <span style="font-weight:700">Recent Transactions</span>
    <a href="/app/user/earnings.php" style="color:var(--primary);font-size:.85rem;text-decoration:none">View All →</a>
  </div>
  <table>
    <thead><tr><th>Type</th><th>Amount</th><th>Description</th><th>Date</th></tr></thead>
    <tbody>
    <?php if (empty($txns)): ?>
    <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:2rem">No transactions yet. <a href="/app/user/invest.php" style="color:var(--primary)">Make your first investment →</a></td></tr>
    <?php else: foreach ($txns as $t): ?>
    <tr>
      <td><span class="status-badge status-<?= in_array($t['type'],['deposit','roi','referral_bonus','direct_bonus'])?'active':'pending' ?>"><?= str_replace('_',' ',ucfirst($t['type'])) ?></span></td>
      <td style="color:<?= $t['type']==='withdrawal'?'var(--danger)':'var(--success)' ?>;font-weight:600"><?= $t['type']==='withdrawal'?'-':'+' ?>$<?= number_format($t['amount'],2) ?></td>
      <td style="color:var(--muted);font-size:.85rem"><?= htmlspecialchars($t['description']) ?></td>
      <td style="color:var(--muted);font-size:.8rem"><?= date('M d, Y',strtotime($t['created_at'])) ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
Chart.defaults.color = '#8892a4';
new Chart(document.getElementById('earningsChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($dates) ?>,
    datasets: [{
      label: 'Earnings ($)',
      data: <?= json_encode($amounts) ?>,
      borderColor: '#00e5ff',
      backgroundColor: 'rgba(0,229,255,0.1)',
      fill: true,
      tension: 0.4
    }]
  },
  options: { 
    responsive: true, 
    plugins: { legend: { display: false } }, 
    scales: { 
      y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } }, 
      x: { grid: { display: false } } 
    } 
  }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
