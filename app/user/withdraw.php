<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();

$min_withdraw = (float)setting('min_withdrawal', 10);
$fee_pct = (float)setting('withdrawal_fee', 5);

if (isset($_GET['cancel_otp'])) {
    unset($_SESSION['withdrawal_otp'], $_SESSION['withdrawal_data']);
    header('Location:/app/user/withdraw.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $wallet = trim($_POST['wallet'] ?? '');

    if ($user['kyc_status'] !== 'approved') {
        flash('danger', "You must complete KYC verification before requesting a withdrawal.");
    } elseif ($amount < $min_withdraw) {
        flash('danger', "Minimum withdrawal is $" . number_format($min_withdraw,2));
    } elseif ($amount > $user['wallet_balance']) {
        flash('danger', "Insufficient wallet balance.");
    } elseif (empty($wallet)) {
        flash('danger', "Please provide a valid receiving wallet address.");
    } else {
        if (isset($_POST['otp'])) {
            // Verify OTP
            if ($_POST['otp'] === (string)($_SESSION['withdrawal_otp'] ?? '')) {
                $fee = $amount * ($fee_pct / 100);
                $net = $amount - $fee;
                
                db()->beginTransaction();
                db()->prepare("UPDATE users SET wallet_balance=wallet_balance-? WHERE id=?")->execute([$amount, $user['id']]);
                db()->prepare("INSERT INTO withdrawals (user_id,amount,fee,net_amount,status,receiving_wallet) VALUES (?,?,?,?,?,?)")
                   ->execute([$user['id'], $amount, $fee, $net, 'pending_admin', $wallet]);
                $wID = db()->lastInsertId();
                db()->prepare("INSERT INTO transactions (user_id,type,amount,description,reference_id) VALUES (?,?,?,?,?)")
                   ->execute([$user['id'], 'withdrawal', $amount, "Withdrawal Request - $wallet", $wID]);
                db()->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'info')")
                   ->execute([$user['id'], "Withdrawal request for $" . number_format($net, 2) . " submitted. Pending admin approval."]);
                db()->commit();
                
                unset($_SESSION['withdrawal_otp'], $_SESSION['withdrawal_data']);
                flash('success', "Withdrawal request for $" . number_format($net,2) . " submitted successfully.");
                header('Location:/app/user/withdraw.php'); exit;
            } else {
                flash('danger', "Invalid OTP code. Please try again.");
            }
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION['withdrawal_otp'] = $otp;
            $_SESSION['withdrawal_data'] = ['amount' => $amount, 'wallet' => $wallet];
            mail($user['email'], "Withdrawal OTP - Indo Global Services", "Your withdrawal verification code is: $otp\n\nDo not share this code with anyone.", "From: security@indoglobalservices.in\r\nReply-To: security@indoglobalservices.in\r\nX-Mailer: PHP/" . phpversion());
            flash('success', "OTP has been sent to your email.");
        }
    }
}

$pending_otp = isset($_SESSION['withdrawal_otp']);
$saved_amount = $_SESSION['withdrawal_data']['amount'] ?? 0;
$saved_wallet = $_SESSION['withdrawal_data']['wallet'] ?? '';

$history = db()->prepare("SELECT * FROM withdrawals WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$history->execute([$user['id']]);
$history = $history->fetchAll();

$pageTitle = 'Withdraw Funds';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Withdraw Earnings</h1></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <div class="card">
    <h3 style="margin-bottom:1.2rem">Request Withdrawal</h3>
    <form method="POST">
      <?php if ($user['kyc_status'] !== 'approved'): ?>
      <div class="alert alert-warning" style="margin-bottom:1.5rem">
          <strong>Action Required:</strong> Your KYC verification is currently <b><?= htmlspecialchars($user['kyc_status'] ?? 'None') ?></b>. You must have an approved KYC document before withdrawing funds.
          <a href="/app/user/profile.php" style="color:var(--primary);margin-left:10px;text-decoration:underline">Go to Profile</a>
      </div>
      <?php endif; ?>
      <div style="background:rgba(0,229,255,.05);border:1px solid rgba(0,229,255,.15);border-radius:10px;padding:1rem;margin-bottom:1.5rem;font-size:1rem;display:flex;justify-content:space-between">
        <span style="color:var(--muted)">Available Balance</span><strong style="color:var(--primary)">$<?= number_format($user['wallet_balance'],2) ?></strong>
      </div>
      <?php if($pending_otp): ?>
      <div style="margin-bottom:1.5rem">
        <div class="alert alert-success">An OTP has been sent to your email. Please enter it to confirm the withdrawal.</div>
        <input type="hidden" name="amount" value="<?= htmlspecialchars($saved_amount) ?>">
        <input type="hidden" name="wallet" value="<?= htmlspecialchars($saved_wallet) ?>">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Enter 6-digit OTP</label>
        <input type="text" name="otp" placeholder="123456" maxlength="6" style="width:100%;padding:.85rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1.2rem;letter-spacing:4px;text-align:center;outline:none" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-check-circle"></i> Verify & Withdraw</button>
      <div style="text-align:center;margin-top:1rem"><a href="?cancel_otp=1" style="color:var(--muted);font-size:.85rem;text-decoration:none">Cancel</a></div>
      <?php else: ?>
      <div style="margin-bottom:1.2rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Withdrawal Amount (Min $<?= $min_withdraw ?>)</label>
        <input type="number" name="amount" id="w-amount" min="<?= $min_withdraw ?>" max="<?= $user['wallet_balance'] ?>" step="0.01" style="width:100%;padding:.85rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1rem;outline:none" required>
      </div>
      <div style="margin-bottom:1.5rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">USDT TRC20 Wallet Address</label>
        <input type="text" name="wallet" placeholder="TEk..." style="width:100%;padding:.85rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1rem;outline:none" required>
      </div>
      <div style="font-size:.85rem;color:var(--muted);margin-bottom:1.5rem;display:grid;gap:.4rem">
        <div style="display:flex;justify-content:space-between"><span>Service Fee (<?= $fee_pct ?>%)</span><span style="color:var(--danger)" id="w-fee">$0.00</span></div>
        <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:.4rem;margin-top:.4rem"><span>You will receive</span><span style="color:var(--success);font-weight:700;font-size:1rem" id="w-net">$0.00</span></div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%" <?= ($user['wallet_balance']<$min_withdraw || $user['kyc_status']!=='approved')?'disabled':'' ?>><i class="fas fa-paper-plane"></i> Send OTP to Email</button>
      <?php endif; ?>
    </form>
  </div>
  
  <div class="card">
    <h3 style="margin-bottom:1rem">Recent Withdrawals</h3>
    <table>
      <thead><tr><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php if(empty($history)): ?>
        <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:2rem">No withdrawal history.</td></tr>
        <?php else: foreach($history as $w): ?>
        <tr>
          <td><div style="font-weight:700">$<?= number_format($w['amount'],2) ?></div><div style="color:var(--muted);font-size:.75rem">Net: $<?= number_format($w['net_amount'],2) ?></div></td>
          <td><span class="status-badge status-<?= $w['status']==='pending_admin'?'pending':($w['status']==='paid'?'active':'rejected') ?>"><?= str_replace('_',' ',ucfirst($w['status'])) ?></span></td>
          <td style="color:var(--muted);font-size:.8rem"><?= date('M d',strtotime($w['created_at'])) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
document.getElementById('w-amount').addEventListener('input', function() {
  const v = parseFloat(this.value||0);
  const fee = v * (<?= $fee_pct ?>/100);
  document.getElementById('w-fee').innerText = '-$' + fee.toFixed(2);
  document.getElementById('w-net').innerText = '$' + (v-fee).toFixed(2);
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
