<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/google_oauth.php';
if (isLoggedIn()) { header('Location:/app/user/dashboard.php'); exit; }

$code = $_GET['code'] ?? '';
$error = '';
if (!$code) { flash('danger', 'Google login failed. Please try again.'); header('Location:/app/auth/login.php'); exit; }

$tokenData = googleGetToken($code);
if (!$tokenData) { flash('danger', 'Could not retrieve Google token.'); header('Location:/app/auth/login.php'); exit; }

$gUser = googleGetUser($tokenData['access_token']);
if (!$gUser || empty($gUser['email'])) { flash('danger', 'Could not get Google user info.'); header('Location:/app/auth/login.php'); exit; }

$email = $gUser['email'];
$name  = $gUser['name'] ?? explode('@', $email)[0];

// Check if user exists
$stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Auto-register with Google
    $ref = $_COOKIE['ref_code'] ?? ($_GET['ref'] ?? '');
    $referredById = null;
    if ($ref) {
        $rs = db()->prepare("SELECT id FROM users WHERE referral_code=?");
        $rs->execute([$ref]); $referredById = $rs->fetchColumn() ?: null;
    }
    $code = generateCode(8);
    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT); // Random password for Google users
    db()->prepare("INSERT INTO users (name,email,password,referral_code,referred_by,email_verified) VALUES (?,?,?,?,?,1)")
       ->execute([$name, $email, $hash, $code, $referredById]);
    $user = db()->prepare("SELECT * FROM users WHERE email=?");
    $user->execute([$email]); $user = $user->fetch();
}

if (!$user['status']) { flash('danger', 'Your account is suspended.'); header('Location:/app/auth/login.php'); exit; }

$_SESSION['user_id'] = $user['id'];
$_SESSION['is_admin'] = $user['is_admin'];
header('Location: ' . ($user['is_admin'] ? '/app/admin/dashboard.php' : '/app/user/dashboard.php'));
exit;
