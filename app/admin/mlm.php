<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mlm'])) {
    $levels = $_POST['levels'] ?? [];
    db()->beginTransaction();
    try {
        foreach ($levels as $lvl => $pct) {
            $pct = max(0, min(100, (float)$pct));
            db()->prepare("UPDATE mlm_levels SET commission_percent = ? WHERE level = ?")->execute([$pct, (int)$lvl]);
        }
        db()->commit();
        $success = "MLM tier percentages updated successfully!";
    } catch (\Exception $e) {
        db()->rollBack();
        $error = "Failed to update MLM levels.";
    }
}

$tiers = db()->query("SELECT * FROM tiers ORDER BY min_downline ASC")->fetchAll();
$mlm = db()->query("SELECT * FROM mlm_levels ORDER BY level ASC")->fetchAll();

$pageTitle = 'MLM Architecture';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar">
    <h1><i class="fas fa-sitemap"></i> MLM & Achievement Tiers</h1>
    <a href="mlm_tree.php" class="btn btn-primary"><i class="fas fa-network-wired"></i> View Genealogy Tree</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:2rem">
  <div style="margin-bottom:1.5rem">
    <h3 style="margin-bottom:.5rem">Achievement Tiers Architecture</h3>
    <p style="color:var(--muted);font-size:.9rem">These tiers dynamically lock user earnings to a multiple of their active investments based on their total downline volume. Users automatically upgrade when their 10-level downline hits the requirement.</p>
  </div>
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>Tier Name</th><th>Min. Downline Volume</th><th>Earnings Cap Multiplier</th></tr></thead>
    <tbody>
      <?php foreach($tiers as $t): ?>
      <tr>
        <td><span class="badge-tier badge-<?= $t['name'] ?>"><?= $t['name'] ?></span></td>
        <td style="color:var(--primary);font-weight:600">$<?= number_format($t['min_downline']) ?></td>
        <td style="font-weight:700;color:var(--warning)"><?= $t['cap_multiplier'] ?>x</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <div style="margin-bottom:1.5rem">
    <h3 style="margin-bottom:.5rem">10-Level Referral Commission Distribution</h3>
    <p style="color:var(--muted);font-size:.9rem">These are the dynamic percentage cuts distributed to the upline whenever a user receives their daily ROI. Updates will apply to the next cron cycle.</p>
  </div>
  
  <form method="POST">
    <input type="hidden" name="update_mlm" value="1">
    <table style="width:100%;border-collapse:collapse;margin-bottom:1.5rem">
      <thead><tr><th style="width:120px">Level</th><th>Commission (% of Daily ROI)</th><th>Type</th></tr></thead>
      <tbody>
        <?php foreach ($mlm as $lvl): ?>
        <tr>
          <td style="font-weight:<?= $lvl['level']===1?'700':'500' ?>;color:<?= $lvl['level']===1?'var(--primary)':'inherit' ?>">Level <?= $lvl['level'] ?> <?= $lvl['level']===1?'(Direct)':'' ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem;max-width:200px">
              <input type="number" name="levels[<?= $lvl['level'] ?>]" value="<?= $lvl['commission_percent'] ?>" step="0.01" min="0" max="100" style="width:100%;padding:.6rem .8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:.95rem;outline:none">
              <span style="color:var(--muted)">%</span>
            </div>
          </td>
          <td><span class="status-badge status-active">Daily Recurring</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save MLM Structure</button>
  </form>
  
  <div class="alert alert-success" style="margin-top:2rem">
    <strong>Direct Deposit Bonus:</strong> The exact first investment amount triggers a one-time 5% bonus to their direct sponsor.
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
