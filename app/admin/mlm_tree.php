<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$db = db();
$searchTerm = $_GET['search'] ?? '';
$rootUsers = [];

if ($searchTerm) {
    // Search for a specific user to be the root
    $stmt = $db->prepare("SELECT id, name, email, tier, wallet_balance, total_downline FROM users WHERE name LIKE ? OR email LIKE ? LIMIT 1");
    $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
    $user = $stmt->fetch();
    if ($user) {
        $rootUsers = [$user];
    }
} else {
    // Show top-level users (those not referred by anyone)
    $rootUsers = $db->query("SELECT id, name, email, tier, wallet_balance, total_downline FROM users WHERE referred_by IS NULL OR referred_by = 0 ORDER BY id ASC")->fetchAll();
}

function renderTree($parentId, $level = 1) {
    global $db;
    if ($level > 10) return; // Limit to 10 levels

    $stmt = $db->prepare("SELECT id, name, email, tier, wallet_balance, total_downline FROM users WHERE referred_by = ?");
    $stmt->execute([$parentId]);
    $children = $stmt->fetchAll();

    if (empty($children)) return;

    echo '<ul>';
    foreach ($children as $child) {
        $tierColor = match($child['tier']) {
            'Bronze' => '#cd7f32',
            'Silver' => '#c0c0c0',
            'Gold' => '#ffd700',
            'Diamond' => '#b9f2ff',
            default => 'var(--primary)'
        };
        echo '<li>';
        echo '<div class="tree-node" style="border-left: 4px solid ' . $tierColor . '">';
        echo '<div class="name">' . htmlspecialchars($child['name']) . ' <span class="badge" style="background:' . $tierColor . '; color:#000">' . $child['tier'] . '</span></div>';
        echo '<div class="stats">';
        echo '<span><i class="fas fa-wallet"></i> $' . number_format($child['wallet_balance'], 2) . '</span>';
        echo '<span><i class="fas fa-users"></i> Vol: $' . number_format($child['total_downline'], 2) . '</span>';
        echo '</div>';
        echo '</div>';
        renderTree($child['id'], $level + 1);
        echo '</li>';
    }
    echo '</ul>';
}

$pageTitle = 'MLM Genealogy Tree';
include __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <h1><i class="fas fa-sitemap"></i> MLM Genealogy Tree</h1>
    <div class="search-box">
        <form method="GET" style="display:flex; gap:10px">
            <input type="text" name="search" placeholder="Search username or email..." value="<?= htmlspecialchars($searchTerm) ?>" style="padding:0.6rem 1rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:white; width:250px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <?php if ($searchTerm): ?>
                <a href="mlm_tree.php" class="btn" style="background:rgba(255,255,255,0.1)">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card" style="margin-top:1.5rem; overflow-x:auto">
    <div class="genealogy-container">
        <?php if (empty($rootUsers)): ?>
            <div style="text-align:center; padding:3rem; color:var(--muted)">
                <i class="fas fa-user-slash" style="font-size:3rem; margin-bottom:1rem"></i>
                <p>No users found matching your search.</p>
            </div>
        <?php else: ?>
            <ul class="tree-root">
                <?php foreach ($rootUsers as $root): 
                    $tierColor = match($root['tier']) {
                        'Bronze' => '#cd7f32',
                        'Silver' => '#c0c0c0',
                        'Gold' => '#ffd700',
                        'Diamond' => '#b9f2ff',
                        default => 'var(--primary)'
                    };
                ?>
                <li>
                    <div class="tree-node root-node" style="border-left: 4px solid <?= $tierColor ?>">
                        <div class="name"><?= htmlspecialchars($root['name']) ?> <span class="badge" style="background:<?= $tierColor ?>; color:#000"><?= $root['tier'] ?></span></div>
                        <div class="stats">
                            <span><i class="fas fa-wallet"></i> $<?= number_format($root['wallet_balance'], 2) ?></span>
                            <span><i class="fas fa-users"></i> Vol: $<?= number_format($root['total_downline'], 2) ?></span>
                        </div>
                    </div>
                    <?php renderTree($root['id']); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
.genealogy-container {
    padding: 1rem;
    min-width: 800px;
}
.genealogy-container ul {
    list-style: none;
    padding-left: 2rem;
    position: relative;
}
.genealogy-container ul::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 1px;
    background: var(--border);
}
.genealogy-container li {
    margin: 1.5rem 0;
    position: relative;
}
.genealogy-container li::before {
    content: '';
    position: absolute;
    left: -1.25rem;
    top: 1rem;
    width: 1.25rem;
    height: 1px;
    background: var(--border);
}
.tree-node {
    background: rgba(255,255,255,0.03);
    padding: 0.8rem 1.2rem;
    border-radius: 8px;
    display: inline-block;
    min-width: 220px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: transform 0.2s;
}
.tree-node:hover {
    transform: translateX(5px);
    background: rgba(255,255,255,0.06);
}
.root-node {
    background: rgba(0,229,255,0.05);
    border: 1px solid rgba(0,229,255,0.2);
}
.tree-node .name {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 0.4rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.tree-node .stats {
    display: flex;
    gap: 15px;
    font-size: 0.8rem;
    color: var(--muted);
}
.badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
