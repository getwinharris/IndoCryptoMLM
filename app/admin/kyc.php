<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND kyc_status = 'pending'");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    
    if ($u) {
        if ($_POST['action'] === 'approve') {
            db()->prepare("UPDATE users SET kyc_status='approved' WHERE id=?")->execute([$id]);
            mail($u['email'], "KYC Approved - Indo Global Services", "Your identity verification has been approved.", "From: no-reply@indoglobalservices.in");
            flash('success', "User KYC approved.");
        } elseif ($_POST['action'] === 'reject') {
            db()->prepare("UPDATE users SET kyc_status='rejected', kyc_document=NULL WHERE id=?")->execute([$id]);
            if (file_exists(__DIR__ . '/../../' . $u['kyc_document'])) unlink(__DIR__ . '/../../' . $u['kyc_document']);
            mail($u['email'], "KYC Rejected - Indo Global Services", "Your identity verification was rejected. Please upload a clear, valid government ID.", "From: no-reply@indoglobalservices.in");
            flash('success', "User KYC rejected. Document deleted.");
        }
    }
    header('Location:/app/admin/kyc.php'); exit;
}

$pending_kyc = db()->query("SELECT * FROM users WHERE kyc_status='pending' ORDER BY created_at ASC")->fetchAll();
$pageTitle = 'KYC Verification';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Identity Verifications (KYC)</h1></div>

<div class="card">
  <table style="width:100%;border-collapse:collapse">
    <thead><tr><th>User</th><th>Age & Gender</th><th>Location</th><th>Document</th><th>Action</th></tr></thead>
    <tbody>
      <?php if(empty($pending_kyc)): ?>
      <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--muted)">No pending KYC requests.</td></tr>
      <?php else: foreach($pending_kyc as $u): ?>
      <tr>
        <td>
            <div style="font-weight:700"><?= htmlspecialchars($u['name']) ?></div>
            <div style="color:var(--muted);font-size:.85rem"><?= htmlspecialchars($u['email']) ?></div>
        </td>
        <td>
            <?= $u['age'] ? htmlspecialchars($u['age']) . ' yrs' : '<span style="color:var(--danger)">Missing</span>' ?>, 
            <?= $u['gender'] ? htmlspecialchars($u['gender']) : '<span style="color:var(--danger)">Missing</span>' ?>
        </td>
        <td style="font-size:.85rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($u['address'] ?? 'No address provided') ?>
        </td>
        <td>
            <a href="<?= htmlspecialchars($u['kyc_document']) ?>" target="_blank" class="btn btn-sm" style="background:rgba(0,229,255,.1);color:var(--primary);border:1px solid rgba(0,229,255,.3)"><i class="fas fa-file-alt"></i> View Doc</a>
        </td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" style="margin-right:.2rem"><i class="fas fa-check"></i></button>
            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
