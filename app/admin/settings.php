<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $keys = ['roi_percent','roi_days','min_invest','max_invest','withdrawal_fee','min_withdrawal','direct_referral_bonus','site_name','site_email','stripe_pub','stripe_secret','google_client_id','google_client_secret'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            db()->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")->execute([trim($_POST[$k]), $k]);
        }
    }
    // Update tiers
    $tierNames = ['Bronze','Silver','Gold','Diamond'];
    foreach ($tierNames as $tname) {
        $min_d = $_POST["tier_min_$tname"] ?? null;
        $cap   = $_POST["tier_cap_$tname"] ?? null;
        if ($min_d !== null && $cap !== null) {
            db()->prepare("UPDATE tiers SET min_downline=?, earnings_cap_multiplier=? WHERE name=?")->execute([(float)$min_d, (float)$cap, $tname]);
        }
    }
    flash('success', 'Settings saved successfully!');
    header('Location:/app/admin/settings.php'); exit;
}

$allSettings = db()->query("SELECT * FROM settings ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
$tiers = db()->query("SELECT * FROM tiers ORDER BY min_downline ASC")->fetchAll();
$pageTitle = 'Platform Settings';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>⚙️ Platform Settings</h1></div>
<form method="POST">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

  <div class="card">
    <h3 style="margin-bottom:1.2rem;color:var(--primary)">💰 Investment Settings</h3>
    <?php
    $fields = [
      'roi_percent'          => ['Daily ROI (%)', 'e.g. 0.5 for 0.5%'],
      'roi_days'             => ['ROI Duration (Days)', 'e.g. 400'],
      'min_invest'           => ['Minimum Investment ($)', 'e.g. 50'],
      'max_invest'           => ['Maximum Investment ($)', 'e.g. 50000'],
      'withdrawal_fee'       => ['Withdrawal Fee (%)', 'e.g. 5'],
      'min_withdrawal'       => ['Minimum Withdrawal ($)', 'e.g. 10'],
      'direct_referral_bonus'=> ['Direct Referral Bonus (%)', 'One-time, e.g. 5'],
    ];
    foreach ($fields as $k => [$label, $hint]):
    $val = $allSettings[$k] ?? '';
    ?>
    <div style="margin-bottom:1rem">
      <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.3rem"><?= $label ?> <span style="font-size:.75rem;color:#555">(<?= $hint ?>)</span></label>
      <input type="number" name="<?= $k ?>" value="<?= htmlspecialchars($val) ?>" step="any" style="width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.95rem;outline:none">
    </div>
    <?php endforeach; ?>
  </div>

  <div>
    <div class="card" style="margin-bottom:1.5rem">
      <h3 style="margin-bottom:1.2rem;color:var(--primary)">🏆 Achievement Tiers & Caps</h3>
      <?php foreach ($tiers as $tier): ?>
      <div style="border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:.8rem">
        <div style="font-weight:700;margin-bottom:.7rem"><?= $tier['name'] ?></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem">
          <div>
            <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:.3rem">Min Downline ($)</label>
            <input type="number" name="tier_min_<?= $tier['name'] ?>" value="<?= $tier['min_downline'] ?>" step="any" style="width:100%;padding:.6rem .8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.9rem;outline:none" <?= $tier['name']==='Bronze'?'readonly':'' ?>>
          </div>
          <div>
            <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:.3rem">Earnings Cap (x)</label>
            <input type="number" name="tier_cap_<?= $tier['name'] ?>" value="<?= $tier['earnings_cap_multiplier'] ?>" step="0.01" style="width:100%;padding:.6rem .8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.9rem;outline:none">
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <h3 style="margin-bottom:1rem;color:var(--primary)">🌐 Site Info</h3>
      <div style="margin-bottom:1rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.3rem">Site Name</label>
        <input type="text" name="site_name" value="<?= htmlspecialchars($allSettings['site_name']??'') ?>" style="width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.95rem;outline:none">
      </div>
      <div>
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.3rem">Site Email</label>
        <input type="email" name="site_email" value="<?= htmlspecialchars($allSettings['site_email']??'') ?>" style="width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.95rem;outline:none">
      </div>
    </div>
    
    <div class="card" style="margin-top:1.5rem">
      <h3 style="margin-bottom:1rem;color:var(--primary)">🔑 API Credentials (Auth & Payments)</h3>
      <div style="margin-bottom:1rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.3rem">Stripe Publishable Key</label>
        <input type="text" name="stripe_pub" value="<?= htmlspecialchars($allSettings['stripe_pub']??'') ?>" style="width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.85rem;outline:none;font-family:monospace">
      </div>
      <div style="margin-bottom:1rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.3rem">Stripe Secret Key</label>
        <input type="password" name="stripe_secret" value="<?= htmlspecialchars($allSettings['stripe_secret']??'') ?>" style="width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.85rem;outline:none;font-family:monospace">
      </div>
      <div style="margin-bottom:1rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.3rem">Google OAuth Client ID</label>
        <input type="text" name="google_client_id" value="<?= htmlspecialchars($allSettings['google_client_id']??'') ?>" style="width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.85rem;outline:none;font-family:monospace">
      </div>
      <div>
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.3rem">Google OAuth Client Secret</label>
        <input type="password" name="google_client_secret" value="<?= htmlspecialchars($allSettings['google_client_secret']??'') ?>" style="width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.85rem;outline:none;font-family:monospace">
      </div>
    </div>

  </div>
</div>
<div style="margin-top:1.5rem;text-align:right">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save All Settings</button>
</div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
