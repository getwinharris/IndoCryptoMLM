<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();

$pdo = db();
$referrals = $pdo->prepare("SELECT * FROM users WHERE referred_by=? ORDER BY created_at DESC");
$referrals->execute([$user['id']]);
$referrals = $referrals->fetchAll();

// Recursive tree function
function getDownlineTree($pdo, $userId, $currentLevel = 1, $maxLevel = 10) {
    if ($currentLevel > $maxLevel) return '';
    
    $stmt = $pdo->prepare("SELECT id, name, email, tier, total_invested FROM users WHERE referred_by = ?");
    $stmt->execute([$userId]);
    $refs = $stmt->fetchAll();
    
    if (empty($refs)) return '';
    
    $html = '<ul style="list-style:none;padding-left:' . ($currentLevel > 1 ? '1.5rem' : '0') . '; margin-top:.5rem">';
    foreach ($refs as $r) {
        $html .= '<li style="margin-bottom:.8rem;position:relative">';
        // Tree lines
        if ($currentLevel > 1) {
            $html .= '<div style="position:absolute;left:-1.5rem;top:1rem;width:1rem;height:1px;background:var(--border)"></div>';
            $html .= '<div style="position:absolute;left:-1.5rem;top:-.5rem;width:1px;height:1.5rem;background:var(--border)"></div>';
        }
        
        $html .= '<div style="background:rgba(255,255,255,.02);border:1px solid '.($currentLevel===1?'var(--primary)':'var(--border)').';border-radius:10px;padding:1rem;display:flex;justify-content:space-between;align-items:center;transition:background .2s">';
        $html .= '  <div style="display:flex;align-items:center;gap:1rem">';
        $html .= '    <div style="background:rgba(0,229,255,.1);color:var(--primary);width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem">L' . $currentLevel . '</div>';
        $html .= '    <div>';
        $html .= '      <div style="font-weight:700">' . htmlspecialchars($r['name']) . '</div>';
        $html .= '      <div style="color:var(--muted);font-size:.8rem">Invested: <strong style="color:var(--success)">$' . number_format($r['total_invested'], 2) . '</strong></div>';
        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '  <span class="badge-tier badge-' . $r['tier'] . '">' . $r['tier'] . '</span>';
        $html .= '</div>';
        
        // Recurse deeper
        $html .= getDownlineTree($pdo, $r['id'], $currentLevel + 1, $maxLevel);
        
        $html .= '</li>';
    }
    $html .= '</ul>';
    
    return $html;
}

$treeHtml = getDownlineTree($pdo, $user['id'], 1, 10);

$pageTitle = 'My Referrals';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar">
  <h1>My Downline Network</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:1.5rem;margin-bottom:2rem">
  <div class="card">
    <div class="label" style="font-size:.8rem;color:var(--muted);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:1px">Direct Referrals (L1)</div>
    <div class="value" style="font-size:2rem;font-weight:800;color:var(--primary)"><?= count($referrals) ?></div>
  </div>
  <div class="card">
    <div class="label" style="font-size:.8rem;color:var(--muted);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:1px">Total Network Volume</div>
    <div class="value" style="font-size:2rem;font-weight:800;color:#ffd700">$<?= number_format($user['total_downline'], 2) ?></div>
  </div>
</div>

<div class="card">
  <div style="font-weight:700;margin-bottom:1.5rem;font-size:1.2rem;display:flex;justify-content:space-between;align-items:center">
    <span>10-Level Tree Visualizer</span>
    <span style="font-size:.8rem;font-weight:500;color:var(--muted);background:rgba(255,255,255,.05);padding:.3rem .8rem;border-radius:20px"><i class="fas fa-sitemap"></i> Live Map</span>
  </div>
  
  <div style="background:var(--bg);padding:1.5rem;border-radius:12px;border:1px solid var(--border);overflow-x:auto">
    <?php if(empty($treeHtml)): ?>
      <div style="text-align:center;color:var(--muted);padding:3rem 1rem">
        <i class="fas fa-network-wired" style="font-size:3rem;color:rgba(255,255,255,.05);margin-bottom:1rem;display:block"></i>
        You have no network history yet. Share your referral link to build your downline!
      </div>
    <?php else: ?>
      <?= $treeHtml ?>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
