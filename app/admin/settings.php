<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$db = db();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // 1. Update general settings
        $settingsKeys = [
            'roi_percent', 'roi_days', 'min_invest', 'max_invest', 
            'withdrawal_fee', 'min_withdrawal', 'direct_referral_bonus', 
            'site_name', 'site_email', 'stripe_pub', 'stripe_secret', 
            'google_client_id', 'google_client_secret'
        ];
        foreach ($settingsKeys as $k) {
            if (isset($_POST[$k])) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([trim($_POST[$k]), $k]);
            }
        }

        // 2. Update Tiers
        $tiers = $db->query("SELECT name FROM tiers")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tiers as $tname) {
            $min_d = $_POST["tier_min_$tname"] ?? null;
            $cap   = $_POST["tier_cap_$tname"] ?? null;
            if ($min_d !== null && $cap !== null) {
                $stmt = $db->prepare("UPDATE tiers SET min_downline = ?, earnings_cap_multiplier = ? WHERE name = ?");
                $stmt->execute([(float)$min_d, (float)$cap, $tname]);
            }
        }

        // 3. Update MLM Levels
        for ($i = 1; $i <= 10; $i++) {
            if (isset($_POST["mlm_level_$i"])) {
                $stmt = $db->prepare("UPDATE mlm_levels SET commission_percent = ? WHERE level = ?");
                $stmt->execute([(float)$_POST["mlm_level_$i"], $i]);
            }
        }

        $db->commit();
        $success = 'Settings updated successfully!';
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

$allSettings = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$tiers = $db->query("SELECT * FROM tiers ORDER BY min_downline ASC")->fetchAll();
$mlmLevels = $db->query("SELECT * FROM mlm_levels ORDER BY level ASC")->fetchAll();

$pageTitle = 'Platform Settings';
include __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <h1><i class="fas fa-tools"></i> Platform Command Center</h1>
    <p style="color:var(--muted)">Configure financial parameters, MLM structure, and API integrations.</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<form method="POST">
    <div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        
        <!-- Financial & ROI Settings -->
        <div class="card">
            <h3 style="margin-bottom:1.5rem; color:var(--primary)"><i class="fas fa-chart-line"></i> Financial & ROI</h3>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Daily ROI (%)</label>
                    <input type="number" name="roi_percent" step="0.01" value="<?= $allSettings['roi_percent'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Duration (Days)</label>
                    <input type="number" name="roi_days" value="<?= $allSettings['roi_days'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Min Invest ($)</label>
                    <input type="number" name="min_invest" value="<?= $allSettings['min_invest'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Max Invest ($)</label>
                    <input type="number" name="max_invest" value="<?= $allSettings['max_invest'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Withdraw Fee (%)</label>
                    <input type="number" name="withdrawal_fee" step="0.1" value="<?= $allSettings['withdrawal_fee'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Min Withdraw ($)</label>
                    <input type="number" name="min_withdrawal" value="<?= $allSettings['min_withdrawal'] ?>" required>
                </div>
            </div>
            <div class="form-group" style="margin-top:1rem">
                <label>Direct Referral Bonus (%)</label>
                <input type="number" name="direct_referral_bonus" step="0.1" value="<?= $allSettings['direct_referral_bonus'] ?>" required>
            </div>
        </div>

        <!-- MLM Structure (Levels 1-10) -->
        <div class="card">
            <h3 style="margin-bottom:1.5rem; color:var(--accent)"><i class="fas fa-sitemap"></i> MLM Commission Structure</h3>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 0.8rem;">
                <?php foreach ($mlmLevels as $level): ?>
                <div style="background:rgba(255,255,255,0.03); padding:0.8rem; border-radius:8px; border:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
                    <span style="font-weight:600; font-size:0.9rem">Level <?= $level['level'] ?></span>
                    <div style="display:flex; align-items:center; gap:5px">
                        <input type="number" name="mlm_level_<?= $level['level'] ?>" step="0.1" value="<?= $level['commission_percent'] ?>" style="width:60px; padding:0.3rem; background:transparent; border:none; border-bottom:1px solid var(--primary); color:white; text-align:right">
                        <span style="font-size:0.8rem; color:var(--muted)">%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="font-size:0.75rem; color:var(--muted); margin-top:1rem">Note: Commissions are calculated as a percentage of the downline's daily ROI.</p>
        </div>

        <!-- Tiers & Earning Caps -->
        <div class="card">
            <h3 style="margin-bottom:1.5rem; color:var(--success)"><i class="fas fa-trophy"></i> Achievement Tiers</h3>
            <?php foreach ($tiers as $tier): ?>
            <div style="margin-bottom:1rem; padding:1rem; background:rgba(255,255,255,0.02); border-radius:10px; border:1px solid var(--border)">
                <div style="font-weight:700; color:white; margin-bottom:0.8rem"><?= $tier['name'] ?> Rank</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem">
                    <div class="form-group">
                        <label>Min Downline ($)</label>
                        <input type="number" name="tier_min_<?= $tier['name'] ?>" value="<?= $tier['min_downline'] ?>" step="1" <?= $tier['name'] === 'Bronze' ? 'readonly' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label>Earnings Cap (x)</label>
                        <input type="number" name="tier_cap_<?= $tier['name'] ?>" value="<?= $tier['earnings_cap_multiplier'] ?>" step="0.1" required>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- API & Site Integration -->
        <div class="card">
            <h3 style="margin-bottom:1.5rem; color:#ffd700"><i class="fas fa-key"></i> API & Site Integration</h3>
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="<?= htmlspecialchars($allSettings['site_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Site Email (Public)</label>
                <input type="email" name="site_email" value="<?= htmlspecialchars($allSettings['site_email']) ?>" required>
            </div>
            <hr style="border:0; border-top:1px solid var(--border); margin:1.5rem 0">
            <div class="form-group">
                <label>Stripe Publishable Key</label>
                <input type="text" name="stripe_pub" value="<?= htmlspecialchars($allSettings['stripe_pub']) ?>" style="font-family:monospace; font-size:0.85rem">
            </div>
            <div class="form-group">
                <label>Stripe Secret Key</label>
                <input type="password" name="stripe_secret" value="<?= htmlspecialchars($allSettings['stripe_secret']) ?>" style="font-family:monospace; font-size:0.85rem">
            </div>
            <div class="form-group">
                <label>Google Client ID</label>
                <input type="text" name="google_client_id" value="<?= htmlspecialchars($allSettings['google_client_id']) ?>" style="font-family:monospace; font-size:0.85rem">
            </div>
            <div class="form-group">
                <label>Google Client Secret</label>
                <input type="password" name="google_client_secret" value="<?= htmlspecialchars($allSettings['google_client_secret']) ?>" style="font-family:monospace; font-size:0.85rem">
            </div>
        </div>

    </div>

    <div style="position: sticky; bottom: 1.5rem; margin-top: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="box-shadow: 0 10px 20px rgba(0,229,255,0.3); padding: 1rem 3rem; font-size: 1.1rem">
            <i class="fas fa-save"></i> Commit Global Settings
        </button>
    </div>
</form>

<style>
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; color: var(--muted); font-size: 0.85rem; margin-bottom: 0.4rem; font-weight: 500; }
.form-group input { 
    width: 100%; padding: 0.8rem 1rem; 
    background: rgba(255,255,255, 0.04); 
    border: 1px solid var(--border); 
    border-radius: 8px; 
    color: var(--text); 
    outline: none; 
    transition: 0.2s;
}
.form-group input:focus { border-color: var(--primary); background: rgba(255,255,255, 0.07); }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
