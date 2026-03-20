<?php
/**
 * Daily ROI Cron Job
 * Run: php /root/Dev/app/cron/daily_roi.php
 * Schedule: 0 0 * * * /usr/bin/php /root/Dev/app/cron/daily_roi.php >> /var/log/igs_cron.log 2>&1
 */
define('RUNNING_CLI', php_sapi_name() === 'cli');
if (!RUNNING_CLI) { http_response_code(403); die('CLI only'); }

require_once __DIR__ . '/../config/db.php';

$today = date('Y-m-d H:i:s');
echo "[{$today}] Starting daily ROI distribution...\n";

// Get all MLM commission rates
$mlmRates = db()->query("SELECT level, commission_percent FROM mlm_levels ORDER BY level")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch all active investments
$investments = db()->query("SELECT i.*, u.referred_by, u.id AS uid, u.tier, u.total_earned, u.wallet_balance
    FROM investments i JOIN users u ON i.user_id=u.id WHERE i.status='active' AND i.days_remaining > 0")->fetchAll();

$processed = 0;
foreach ($investments as $inv) {
    // Get the tier cap for this user
    $tierData = db()->prepare("SELECT earnings_cap_multiplier FROM tiers WHERE name=?");
    $tierData->execute([$inv['tier']]);
    $capMultiplier = (float)($tierData->fetchColumn() ?: 2);
    $totalCap = $inv['cap_amount']; // stored at investment creation time based on tier at that point

    // Calculate daily ROI, check if user has hit cap
    $alreadyEarned = (float)$inv['total_earned'] + (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id={$inv['uid']} AND type IN ('referral_bonus','direct_bonus')")->fetchColumn();
    $globalCap = $inv['amount'] * 5; // Hard max = 5x, wallet + all earned

    $actualDailyROI = $inv['daily_roi'];
    $remaining = $totalCap - $inv['total_earned'];
    if ($remaining <= 0) {
        // Cap reached, mark complete
        db()->prepare("UPDATE investments SET status='completed', days_remaining=0 WHERE id=?")->execute([$inv['id']]);
        echo "  ✓ Investment #{$inv['id']} cap reached – marked complete\n";
        continue;
    }
    if ($actualDailyROI > $remaining) $actualDailyROI = $remaining;

    db()->beginTransaction();
    // Pay ROI to investor
    db()->prepare("UPDATE users SET wallet_balance=wallet_balance+?, total_earned=total_earned+? WHERE id=?")->execute([$actualDailyROI, $actualDailyROI, $inv['uid']]);
    db()->prepare("UPDATE investments SET total_earned=total_earned+?, days_remaining=days_remaining-1 WHERE id=?")->execute([$actualDailyROI, $inv['id']]);
    db()->prepare("INSERT INTO transactions (user_id,type,amount,description,reference_id) VALUES (?,?,?,?,?)")
       ->execute([$inv['uid'], 'roi', $actualDailyROI, "Daily ROI on \${$inv['amount']} investment", $inv['id']]);
    db()->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'info')")
       ->execute([$inv['uid'], "You received \$" . number_format($actualDailyROI,2) . " daily ROI."]);

    // Mark complete if days run out
    if (($inv['days_remaining'] - 1) <= 0) {
        db()->prepare("UPDATE investments SET status='completed' WHERE id=?")->execute([$inv['id']]);
    }

    // Distribute MLM commissions up 10 referral levels
    $currentUserId = $inv['uid'];
    for ($level = 1; $level <= 10; $level++) {
        $sponsorStmt = db()->prepare("SELECT id, referred_by, wallet_balance, total_earned, total_downline, tier FROM users WHERE id=(SELECT referred_by FROM users WHERE id=?)");
        $sponsorStmt->execute([$currentUserId]);
        $sponsor = $sponsorStmt->fetch();
        if (!$sponsor) break;

        $rate = ($mlmRates[$level] ?? 0) / 100;
        $commission = round($actualDailyROI * $rate, 8);
        if ($commission <= 0) { $currentUserId = $sponsor['id']; continue; }

        // Check sponsor's own cap (5x of their largest investment, simplified: 5x total_invested)
        $sponsorInvested = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM investments WHERE user_id={$sponsor['id']}")->fetchColumn();
        $sponsorTierData = db()->prepare("SELECT earnings_cap_multiplier FROM tiers WHERE name=?");
        $sponsorTierData->execute([$sponsor['tier']]);
        $sponsorCap = $sponsorInvested * (float)($sponsorTierData->fetchColumn() ?: 2);
        $sponsorEarned = (float)$sponsor['total_earned'];
        if ($sponsorEarned >= $sponsorCap) { $currentUserId = $sponsor['id']; continue; }

        db()->prepare("UPDATE users SET wallet_balance=wallet_balance+?, total_earned=total_earned+? WHERE id=?")->execute([$commission, $commission, $sponsor['id']]);
        db()->prepare("INSERT INTO transactions (user_id,type,amount,description,reference_id) VALUES (?,?,?,?,?)")
           ->execute([$sponsor['id'], 'referral_bonus', $commission, "Level $level MLM commission from investment #{$inv['id']}", $inv['uid']]);
        db()->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'success')")
           ->execute([$sponsor['id'], "You earned a \$" . number_format($commission,2) . " Level $level referral bonus!"]);

        updateUserTier($sponsor['id']);
        $currentUserId = $sponsor['id'];
    }

    db()->commit();
    $processed++;
}

echo "[".date('Y-m-d H:i:s')."] Done. Processed $processed investments.\n";

function updateUserTier(int $userId): void {
    $stmt = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type IN ('direct_bonus','referral_bonus')");
    $stmt->execute([$userId]);
    $downline = (float)$stmt->fetchColumn();
    db()->prepare("UPDATE users SET total_downline=? WHERE id=?")->execute([$downline, $userId]);
    $tiers = db()->query("SELECT * FROM tiers ORDER BY min_downline DESC")->fetchAll();
    $tier = 'Bronze';
    foreach ($tiers as $t) { if ($downline >= $t['min_downline']) { $tier = $t['name']; break; } }
    db()->prepare("UPDATE users SET tier=? WHERE id=?")->execute([$tier, $userId]);
}
