<?php
require_once __DIR__ . '/../config/session.php';
requireLogin(); if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();
$min = (float)setting('min_invest');
$max = (float)setting('max_invest');
$roi = (float)setting('roi_percent');
$days = (int)setting('roi_days');
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if ($user['kyc_status'] !== 'approved') {
        $error = "You must complete KYC verification before investing.";
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $plans = db()->query("SELECT amount FROM investment_plans ORDER BY amount ASC")->fetchAll(PDO::FETCH_COLUMN);
        $plans = array_map('floatval', $plans);
    if (!in_array($amount, $plans)) {
        $error = "Please select a valid investment plan.";
    } elseif ($user['wallet_balance'] < $amount) {
        $error = "Insufficient wallet balance. Please go to the Deposit page to top-up your wallet.";
    } else {
        // Get tier cap
        $tierData = db()->prepare("SELECT * FROM tiers WHERE name=?"); $tierData->execute([$user['tier']]); $tier = $tierData->fetch();
        $cap = $amount * ($tier['earnings_cap_multiplier'] ?? 2);
        $daily = $amount * ($roi / 100);

        db()->beginTransaction();
        // Create investment record
        db()->prepare("INSERT INTO investments (user_id,amount,daily_roi,days_remaining,total_days,cap_amount) VALUES (?,?,?,?,?,?)")
           ->execute([$user['id'], $amount, $daily, $days, $days, $cap]);
        $invId = db()->lastInsertId();
        // Update user totals and DEDUCT from wallet balance
        db()->prepare("UPDATE users SET wallet_balance=wallet_balance-?, total_invested=total_invested+? WHERE id=?")->execute([$amount, $amount, $user['id']]);
        // Log deposit transaction
        db()->prepare("INSERT INTO transactions (user_id,type,amount,description,reference_id) VALUES (?,?,?,?,?)")
           ->execute([$user['id'], 'investment', $amount, "Investment of \$$amount at $roi% daily for $days days", $invId]);
        db()->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'success')")
           ->execute([$user['id'], "You have successfully invested \$" . number_format($amount, 2) . " and will earn $roi% daily."]);

        // Direct referral bonus to referrer (5% one-time)
        if ($user['referred_by']) {
            $bonusRate = (float)setting('direct_referral_bonus') / 100;
            $bonus = round($amount * $bonusRate, 2);
            db()->prepare("UPDATE users SET wallet_balance=wallet_balance+?, total_earned=total_earned+?, total_downline=total_downline+? WHERE id=?")
               ->execute([$bonus, $bonus, $amount, $user['referred_by']]);
            db()->prepare("INSERT INTO transactions (user_id,type,amount,description,reference_id) VALUES (?,?,?,?,?)")
               ->execute([$user['referred_by'], 'direct_bonus', $bonus, "Direct referral bonus from {$user['name']}", $user['id']]);
            updateUserTier($user['referred_by']);
        }
        db()->commit();
        flash('success', "Investment of \$$amount created! You'll earn \$$daily/day for $days days.");
        }
    }
}
$plans = db()->query("SELECT amount FROM investment_plans ORDER BY amount ASC")->fetchAll(PDO::FETCH_COLUMN);
$plans = array_map('floatval', $plans);

$pageTitle = 'New Investment';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Make an Investment</h1></div>
<?php if ($user['kyc_status'] !== 'approved'): ?>
    <div class="alert alert-warning">
        <strong>Action Required:</strong> Your KYC verification is currently <b><?= htmlspecialchars($user['kyc_status'] ?? 'None') ?></b>. You must have an approved KYC document before making investments.
        <a href="/app/user/profile.php" style="color:var(--primary);margin-left:10px;text-decoration:underline">Go to Profile</a>
    </div>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <div class="card">
    <h3 style="margin-bottom:1.5rem">Investment Details</h3>
    <form method="POST">
      <div style="margin-bottom:1.5rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Investment Plan Selection</label>
        <div style="text-align:center;font-size:2rem;font-weight:800;color:var(--primary);margin-bottom:.5rem" id="inv-display-label">$<?= number_format($plans[0] ?? 50) ?></div>
        
        <div style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;padding:2rem 1rem">
            <?php $max_idx = max(0, count($plans)-1); ?>
            <input type="range" id="native-slider" min="0" max="<?= $max_idx ?>" step="1" value="0" style="width:100%;cursor:pointer" <?= $max_idx===0?'disabled':'' ?>>
            <div style="display:flex;justify-content:space-between;margin-top:.5rem;font-size:.8rem;color:var(--muted)">
                <span>$<?= number_format($plans[0] ?? 50) ?></span><span>$<?= number_format(end($plans) ?? 50000) ?></span>
            </div>
        </div>
        <input type="hidden" name="amount" id="actual-amount" value="<?= $plans[0] ?? 50 ?>">
      </div>
      <div style="background:rgba(0,229,255,.05);border:1px solid rgba(0,229,255,.15);border-radius:10px;padding:1rem;margin-bottom:1.5rem;font-size:.9rem">
        <div style="display:flex;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid var(--border)"><span style="color:var(--muted)">Wallet Balance</span><span style="color:<?= $user['wallet_balance'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700">$<?= number_format($user['wallet_balance'], 2) ?></span></div>
        <div style="display:flex;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid var(--border)"><span style="color:var(--muted)">Daily ROI</span><span style="color:var(--primary);font-weight:700"><?= $roi ?>%</span></div>
        <div style="display:flex;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid var(--border)"><span style="color:var(--muted)">Duration</span><span><?= $days ?> Days</span></div>
        <div style="display:flex;justify-content:space-between;padding:.3rem 0"><span style="color:var(--muted)">Your Tier Cap</span><span style="color:#ffd700;font-weight:700"><?= $user['tier'] ?></span></div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%" <?= $user['kyc_status']!=='approved'?'disabled':'' ?>><i class="fas fa-arrow-up"></i> Confirm Investment</button>
    </form>
  </div>
  <div>
    <div class="card" style="margin-bottom:1rem">
      <h4 style="margin-bottom:1rem">Live Returns Preview</h4>
      <div id="preview" style="font-size:.9rem;color:var(--muted)">Enter an amount to see your projected returns</div>
    </div>
    <div class="card">
      <h4 style="margin-bottom:.8rem">Your Active Investments</h4>
      <?php
      $invs = db()->prepare("SELECT * FROM investments WHERE user_id=? AND status='active' ORDER BY created_at DESC");
      $invs->execute([$user['id']]); $invs = $invs->fetchAll();
      if (empty($invs)): ?><p style="color:var(--muted);font-size:.9rem">No active investments.</p>
      <?php else: foreach ($invs as $inv): ?>
      <div style="border:1px solid var(--border);border-radius:10px;padding:.8rem;margin-bottom:.7rem;font-size:.85rem">
        <div style="display:flex;justify-content:space-between"><span style="font-weight:700">$<?= number_format($inv['amount'],2) ?></span><span style="color:var(--success)">+$<?= number_format($inv['daily_roi'],2) ?>/day</span></div>
        <div style="color:var(--muted);margin-top:.3rem"><?= $inv['days_remaining'] ?> days remaining · Earned $<?= number_format($inv['total_earned'],2) ?></div>
        <div style="margin-top:.4rem;background:rgba(255,255,255,.05);border-radius:4px;height:6px"><div style="background:var(--primary);border-radius:4px;height:100%;width:<?= min(100,round(($inv['total_days']-$inv['days_remaining'])/$inv['total_days']*100)) ?>%"></div></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<script>
const PLANS = <?= json_encode($plans) ?>;
if (PLANS.length === 0) PLANS.push(50);
const sliderElem = document.getElementById('native-slider');
const actualInput = document.getElementById('actual-amount');
const displayLabel = document.getElementById('inv-display-label');

function updateUI() {
    const v = PLANS[sliderElem.value];
    actualInput.value = v;
    displayLabel.innerText = '$' + new Intl.NumberFormat('en-US').format(v);
    
    const roi=<?= $roi ?>, days=<?= $days ?>;
    const daily=(v*roi/100).toFixed(2), total=(v*2).toFixed(2);
    
    document.getElementById('preview').innerHTML = 
        `<div style="display:grid;gap:.5rem">
        <div style="display:flex;justify-content:space-between"><span>Daily Income</span><span style="color:#00e5ff;font-weight:700">$${daily}</span></div>
        <div style="display:flex;justify-content:space-between"><span>Total ROI (${days} days)</span><span style="color:#00c853;font-weight:700">$${total}</span></div>
        <div style="display:flex;justify-content:space-between"><span>Base Return (2x Bronze)</span><span style="font-weight:700">$${(v*2).toFixed(2)}</span></div>
        </div>`;
}

sliderElem.addEventListener('input', updateUI);
updateUI(); // run init
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
