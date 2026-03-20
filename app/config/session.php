<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_name(SESSION_NAME);
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/app/auth/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/app/user/dashboard.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function generateCode(int $len = 8): string {
    return strtoupper(substr(bin2hex(random_bytes($len)), 0, $len));
}

function updateUserTier(int $userId): void {
    $stmt = db()->prepare("SELECT total_downline FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $downline = (float)$stmt->fetchColumn();

    $tiers = db()->query("SELECT * FROM tiers ORDER BY min_downline DESC")->fetchAll();
    $tier = 'Bronze';
    foreach ($tiers as $t) {
        if ($downline >= $t['min_downline']) { $tier = $t['name']; break; }
    }
    db()->prepare("UPDATE users SET tier = ? WHERE id = ?")->execute([$tier, $userId]);
}
