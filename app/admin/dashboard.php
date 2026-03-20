<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();
$user = currentUser();

// Platform stats
$stats = db()->query("SELECT
  (SELECT COUNT(*) FROM users WHERE is_admin=0) AS total_users,
  (SELECT COUNT(*) FROM investments WHERE status='active') AS active_investments,
  (SELECT COALESCE(SUM(amount),0) FROM investments) AS total_deposited,
  (SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type IN ('roi','referral_bonus','direct_bonus')) AS total_paid_out,
  (SELECT COUNT(*) FROM withdrawals WHERE status='pending_admin') AS pending_withdrawals
")->fetch();

$recentUsers = db()->query("SELECT * FROM users WHERE is_admin=0 ORDER BY created_at DESC LIMIT 5")->fetchAll();

// 7-day chart data
$chartData = db()->query("
    SELECT DATE(created_at) as dt, 
           SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) as deposits,
           SUM(CASE WHEN type IN ('roi','referral_bonus','direct_bonus') THEN amount ELSE 0 END) as payouts
    FROM transactions 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY dt ASC
")->fetchAll(PDO::FETCH_ASSOC);

$cData = []; foreach($chartData as $row) $cData[$row['dt']] = $row;
$dates = []; $deps = []; $outs = [];
for($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($d));
    $deps[] = (float)($cData[$d]['deposits'] ?? 0);
    $outs[] = (float)($cData[$d]['payouts'] ?? 0);
}

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar">
  <h1>Admin Dashboard</h1>
  <a href="/app/auth/logout.php" class="btn" style="background:rgba(255,23,68,.15);color:#ff1744;border:1px solid rgba(255,23,68,.3)"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="label">Total Users</div><div class="value cyan"><?= number_format($stats['total_users']) ?></div></div>
  <div class="stat-card"><div class="label">Total Deposited</div><div class="value green">$<?= number_format($stats['total_deposited'],2) ?></div></div>
  <div class="stat-card"><div class="label">Total Paid Out</div><div class="value purple">$<?= number_format($stats['total_paid_out'],2) ?></div></div>
  <div class="stat-card"><div class="label">Pending Withdrawals</div><div class="value" style="color:<?= $stats['pending_withdrawals']>0?'var(--warning)':'var(--success)' ?>"><?= $stats['pending_withdrawals'] ?></div></div>
</div>

<!-- Platform Activity Chart -->
<div class="card" style="margin-bottom:1.5rem">
  <div style="font-weight:700;margin-bottom:1rem">Platform Activity (Last 7 Days)</div>
  <canvas id="adminChart" height="80"></canvas>
</div>

<div class="card">
  <div style="font-weight:700;margin-bottom:1rem">Recent Members</div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Tier</th><th>Invested</th><th>Earned</th><th>Joined</th></tr></thead>
    <tbody>
    <?php foreach ($recentUsers as $u): ?>
    <tr>
      <td><?= htmlspecialchars($u['name']) ?></td>
      <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
      <td><span class="badge-tier badge-<?= $u['tier'] ?>"><?= $u['tier'] ?></span></td>
      <td>$<?= number_format($u['total_invested'],2) ?></td>
      <td style="color:var(--success)">$<?= number_format($u['total_earned'],2) ?></td>
      <td style="color:var(--muted);font-size:.8rem"><?= date('M d, Y',strtotime($u['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($recentUsers)): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:2rem">No users yet</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
Chart.defaults.color = '#8892a4';
new Chart(document.getElementById('adminChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($dates) ?>,
    datasets: [
      { label: 'Deposits', data: <?= json_encode($deps) ?>, borderColor: '#00c853', backgroundColor: 'rgba(0,200,83,0.1)', fill: true, tension: 0.4 },
      { label: 'Payouts', data: <?= json_encode($outs) ?>, borderColor: '#7000ff', backgroundColor: 'rgba(112,0,255,0.1)', fill: true, tension: 0.4 }
    ]
  },
  options: { 
    responsive: true, 
    scales: { 
      y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } }, 
      x: { grid: { display: false } } 
    } 
  }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
