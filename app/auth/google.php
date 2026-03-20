<?php
require_once __DIR__ . '/../config/session.php';

$client_id = setting('google_client_id');
$client_secret = setting('google_client_secret');
$redirect_uri = SITE_URL . '/app/auth/google.php';

if (!$client_id || !$client_secret) {
    die("Google SSO is not configured by the admin yet.");
}

// 1. Initial Redirect to Google
if (!isset($_GET['code'])) {
    $url = "https://accounts.google.com/o/oauth2/v2/auth?scope=" . urlencode("email profile") . "&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&client_id=" . $client_id . "&access_type=online";
    header("Location: $url");
    exit;
}

// 2. Callback from Google
$code = $_GET['code'];
$post = http_build_query([
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'code' => $code
]);

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);
if (isset($token_data['error'])) {
    die("Google SSO Error: " . $token_data['error_description']);
}

$access_token = $token_data['access_token'];

// 3. Get User Profile
$ch2 = curl_init("https://www.googleapis.com/oauth2/v2/userinfo");
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
$profile = json_decode(curl_exec($ch2), true);
curl_close($ch2);

if (isset($profile['email'])) {
    $email = $profile['email'];
    $name = $profile['name'] ?? 'Google User';
    
    // Check if user exists
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Register new user via Google
        $password = password_hash(bin2hex(random_bytes(10)), PASSWORD_BCRYPT);
        // Look for sponsor in session cookie if existed
        $sponsorId = $_COOKIE['ref_id'] ?? null;
        
        $refCode = substr(md5(uniqid()), 0, 8);
        db()->prepare("INSERT INTO users (name, email, password, referral_code, referred_by, email_verified, kyc_status) VALUES (?, ?, ?, ?, ?, 1, 'none')")
           ->execute([$name, $email, $password, $refCode, $sponsorId]);
        $uid = db()->lastInsertId();
        
        // Fetch to login
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } elseif ($user['status'] == 0) {
        die("Your account is banned.");
    }
    
    // Force email verified if they used Google
    if ($user['email_verified'] == 0) {
        db()->prepare("UPDATE users SET email_verified=1 WHERE id=?")->execute([$user['id']]);
    }

    // Login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_admin'] = $user['is_admin'];
    
    header('Location: ' . ($user['is_admin'] ? '/app/admin/dashboard.php' : '/app/user/dashboard.php'));
    exit;
} else {
    die("Failed to fetch Google profile.");
}
