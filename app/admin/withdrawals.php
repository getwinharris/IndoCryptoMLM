<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = db()->prepare("SELECT w.*, u.email FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.id = ?");
    $stmt->execute([$id]);
    $w = $stmt->fetch();
    
    if ($w && $w['status'] === 'pending_admin') {
        if ($_POST['action'] === 'approve') {
            db()->prepare("UPDATE withdrawals SET status='paid' WHERE id=?")->execute([$id]);
            db()->prepare("UPDATE transactions SET description=CONCAT(description, ' - APPROVED') WHERE reference_id=? AND type='withdrawal'")->execute([$id]);
            mail($w['email'], "Withdrawal Approved", "Your withdrawal of $" . number_format($w['net_amount'],2) . " has been sent to " . $w['receiving_wallet'], "From: no-reply@indoglobalservices.in");
            db()->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'success')")->execute([$w['user_id'], "Withdrawal approved. $" . number_format($w['net_amount'],2) . " sent to " . $w['receiving_wallet']]);
            flash('success', "Withdrawal #$id marked as paid.");
        } elseif ($_POST['action'] === 'reject') {
            // Refund wallet
            db()->prepare("UPDATE withdrawals SET status='rejected' WHERE id=?")->execute([$id]);
            db()->prepare("UPDATE users SET wallet_balance=wallet_balance+? WHERE id=?")->execute([$w['amount'], $w['user_id']]);
            db()->prepare("UPDATE transactions SET description=CONCAT(description, ' - REJECTED (Refunded)') WHERE reference_id=? AND type='withdrawal'")->execute([$id]);
            mail($w['email'], "Withdrawal Rejected", "Your withdrawal was rejected and funds were returned to your wallet.", "From: no-reply@indoglobalservices.in");
            db()->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'danger')")->execute([$w['user_id'], "Withdrawal rejected. $" . number_format($w['amount'],2) . " refunded to your wallet."]);
            flash('success', "Withdrawal #$id rejected and funds refunded.");
        }
    }
    header('Location:/app/admin/withdrawals.php'); exit;
}

$withdrawals = db()->query("SELECT w.*, u.name, u.email FROM withdrawals w JOIN users u ON w.user_id = u.id ORDER BY w.created_at DESC")->fetchAll();
$pageTitle = 'Manage Withdrawals';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Withdrawal Requests</h1></div>

<div class="card">
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>User</th><th>Amount</th><th>Net (after fee)</th><th>Wallet</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
      <?php if(empty($withdrawals)): ?>
      <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">No withdrawals found.</td></tr>
      <?php else: foreach($withdrawals as $w): ?>
      <tr>
        <td><div><strong><?= htmlspecialchars($w['name']) ?></strong></div><div style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($w['email']) ?></div></td>
        <td>$<?= number_format($w['amount'], 2) ?></td>
        <td style="color:var(--success);font-weight:700">$<?= number_format($w['net_amount'], 2) ?></td>
        <td style="font-size:.85rem;color:var(--muted)"><?= htmlspecialchars($w['receiving_wallet']) ?></td>
        <td><span class="status-badge status-<?= $w['status']==='pending_admin'?'pending':($w['status']==='paid'?'active':'rejected') ?>"><?= str_replace('_',' ',ucfirst($w['status'])) ?></span></td>
        <td style="font-size:.85rem;color:var(--muted)"><?= date('M d, Y H:i', strtotime($w['created_at'])) ?></td>
        <td>
          <?php if($w['status'] === 'pending_admin'): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="id" value="<?= $w['id'] ?>">
            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" style="margin-right:.5rem"><i class="fas fa-check"></i> Approve</button>
            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Reject</button>
          </form>
          <?php else: ?>
          <span style="color:var(--muted);font-size:.85rem">Processed</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
