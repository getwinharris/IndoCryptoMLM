<?php
require_once __DIR__ . '/app/config/db.php';
$db = db();

try {
    // 1. investment_plans
    $db->exec("CREATE TABLE IF NOT EXISTS investment_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        amount DECIMAL(15,2) NOT NULL UNIQUE
    )");
    if ($db->query("SELECT COUNT(*) FROM investment_plans")->fetchColumn() == 0) {
        $plans = [50, 100, 200, 300, 400, 1000, 1500, 2000, 3000, 4000, 5000, 10000, 15000, 20000, 30000, 40000, 50000];
        $stmt = $db->prepare("INSERT IGNORE INTO investment_plans (amount) VALUES (?)");
        foreach($plans as $p) $stmt->execute([$p]);
    }

    // 2. mlm_levels
    $db->exec("CREATE TABLE IF NOT EXISTS mlm_levels (
        level INT PRIMARY KEY,
        commission_percent DECIMAL(5,2) NOT NULL
    )");
    if ($db->query("SELECT COUNT(*) FROM mlm_levels")->fetchColumn() == 0) {
        $levels = [1=>25, 2=>15, 3=>10, 4=>5, 5=>5, 6=>5, 7=>5, 8=>5, 9=>10, 10=>10];
        $stmt = $db->prepare("INSERT IGNORE INTO mlm_levels (level, commission_percent) VALUES (?,?)");
        foreach($levels as $lvl => $pct) $stmt->execute([$lvl, $pct]);
    }

    // 3. notifications
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        read_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    )");

    echo "CRITICAL FIX: Database tables generated perfectly.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
