<?php
require_once __DIR__ . '/../app/config/session.php';
requireLogin();
if (!isAdmin()) {
    db()->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ?")->execute([currentUser()['id']]);
}
$ref = $_SERVER['HTTP_REFERER'] ?? '/app/user/dashboard.php';
header("Location: $ref");
exit;
