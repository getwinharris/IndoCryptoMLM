<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_plan'])) {
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $error = "Amount must be greater than 0.";
        } else {
            try {
                db()->prepare("INSERT INTO investment_plans (amount) VALUES (?)")->execute([$amount]);
                $success = "Plan added successfully.";
            } catch (\Exception $e) {
                $error = "Failed to add plan. It may already exist.";
            }
        }
    } elseif (isset($_POST['delete_plan'])) {
        $id = (int)($_POST['plan_id'] ?? 0);
        db()->prepare("DELETE FROM investment_plans WHERE id = ?")->execute([$id]);
        $success = "Plan deleted successfully.";
    }
}

$plans = db()->query("SELECT * FROM investment_plans ORDER BY amount ASC")->fetchAll();

$pageTitle = 'Manage Investment Plans';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Dynamic Investment Plans</h1></div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem">
  <div class="card">
    <h3 style="margin-bottom:1.2rem">Add New USD Tier</h3>
    <form method="POST">
      <input type="hidden" name="add_plan" value="1">
      <div style="margin-bottom:1rem">
        <label style="display:block;margin-bottom:.4rem;color:var(--muted);font-size:.85rem">Amount ($)</label>
        <input type="number" name="amount" required step="1" min="1" placeholder="e.g. 750" style="width:100%;padding:1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1rem;outline:none">
      </div>
      <button type="submit" class="btn btn-success" style="width:100%"><i class="fas fa-plus"></i> Add Plan to Slider</button>
    </form>
  </div>
  
  <div class="card">
    <h3 style="margin-bottom:1.2rem">Active Slider Tiers</h3>
    <p style="color:var(--muted);font-size:.85rem;margin-bottom:1rem">These exact amounts will dynamically populate the user's investment slider.</p>
    <div style="display:flex;flex-wrap:wrap;gap:.8rem">
      <?php foreach ($plans as $p): ?>
      <div style="background:rgba(0,229,255,.05);border:1px solid var(--primary);border-radius:20px;padding:.5rem 1rem;font-weight:700;display:flex;align-items:center;gap:10px">
        $<?= number_format($p['amount']) ?>
        <form method="POST" style="display:inline">
            <input type="hidden" name="delete_plan" value="1">
            <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
            <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:.9rem" title="Delete"><i class="fas fa-times"></i></button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
