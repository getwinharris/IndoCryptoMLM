<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/google_oauth.php';
if (isLoggedIn()) { header('Location:/app/user/dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $ref   = trim($_POST['referral'] ?? '');
    if (!$name || !$email || !$pass) { $error = 'All fields are required.'; }
    elseif (strlen($pass) < 8) { $error = 'Password must be at least 8 characters.'; }
    elseif (!isset($_POST['terms'])) { $error = 'You must accept the Terms of Service and Risk Disclaimer to proceed.'; }
    else {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) { $error = 'This email is already registered.'; }
        else {
            $referredById = null;
            if ($ref) {
                $rs = db()->prepare("SELECT id FROM users WHERE referral_code = ?");
                $rs->execute([$ref]); $referredById = $rs->fetchColumn() ?: null;
            }
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $code = generateCode(8);
            
            // 1. Insert user with email_verified = 0
            db()->prepare("INSERT INTO users (name,email,password,referral_code,referred_by,email_verified) VALUES (?,?,?,?,?,0)")
               ->execute([$name, $email, $hash, $code, $referredById]);
            
            // 2. Generate OTP
            $otp = random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            db()->prepare("UPDATE users SET otp_code=?, otp_expires_at=? WHERE email=?")
               ->execute([$otp, $expires, $email]);
               
            // 3. Send Email
            $msg = "Welcome to Indo Global Services!\n\nYour email verification code is: $otp\nThis code will expire in 15 minutes.";
            $headers = "From: noreply@indoglobalservices.in\r\n";
            mail($email, "Verify Your IGS Account", $msg, $headers);
            
            header('Location: /app/auth/verify.php?email=' . urlencode($email)); exit;
        }
    }
}
$refParam = htmlspecialchars($_GET['ref'] ?? '');
$googleURL = googleAuthURL();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account | Indo Global Services</title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#00e5ff">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="IGS">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0c10;--card:#141820;--border:rgba(255,255,255,.07);--primary:#00e5ff;--secondary:#7000ff;--danger:#ff1744;--text:#fff;--muted:#8892a4}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Outfit',sans-serif}
body{background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
.glow-bg{position:fixed;top:-20%;left:-10%;width:60vw;height:60vw;background:radial-gradient(circle,rgba(112,0,255,.25),transparent 70%);pointer-events:none;border-radius:50%}
.glow-bg2{position:fixed;bottom:-20%;right:-10%;width:50vw;height:50vw;background:radial-gradient(circle,rgba(0,229,255,.15),transparent 70%);pointer-events:none;border-radius:50%}
.box{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:2.5rem;width:100%;max-width:440px;position:relative;z-index:1}
.logo{text-align:center;font-size:1.4rem;font-weight:800;margin-bottom:1.5rem}.logo span{color:var(--primary)}
h2{font-size:1.4rem;margin-bottom:.3rem}
p.sub{color:var(--muted);font-size:.9rem;margin-bottom:1.3rem}
.form-group{margin-bottom:1rem}
label{display:block;font-size:.85rem;color:var(--muted);margin-bottom:.4rem;font-weight:500}
input{width:100%;padding:.8rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:.95rem;outline:none;transition:border .2s}
input:focus{border-color:var(--primary)}
.btn-main{width:100%;padding:.9rem;border:none;border-radius:10px;background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;font-size:1rem;font-weight:700;cursor:pointer;margin-top:.5rem}
.btn-main:hover{opacity:.9}
.divider{text-align:center;color:var(--muted);margin:1.1rem 0;font-size:.85rem;position:relative}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:var(--border)}
.divider::before{left:0}.divider::after{right:0}
.btn-google{width:100%;padding:.82rem;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.03);color:var(--text);font-size:.93rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.7rem;text-decoration:none;transition:background .2s}
.btn-google:hover{background:rgba(255,255,255,.07)}
.google-icon{width:20px;height:20px;background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path fill="%23FFC107" d="M43.6 20H24v8h11.3C33.5 32.4 29.2 35 24 35c-6.1 0-11-4.9-11-11s4.9-11 11-11c2.8 0 5.3 1 7.2 2.7l5.7-5.7C33.7 7.1 29.1 5 24 5 13.5 5 5 13.5 5 24s8.5 19 19 19c10.2 0 18.5-8.1 18.5-19 0-1.3-.1-2.5-.4-3.6z"/><path fill="%23FF3D00" d="m6.3 14.7 6.6 4.8C14.5 16 19 13 24 13c2.8 0 5.3 1 7.2 2.7l5.7-5.7C33.7 7.1 29.1 5 24 5c-7.6 0-14.1 4.3-17.7 9.7z"/><path fill="%234CAF50" d="M24 43c5 0 9.5-1.9 12.9-4.9l-5.9-5C29.3 34.5 26.8 35 24 35c-5.1 0-9.4-3.4-11-8H6.2C9.7 38.3 16.3 43 24 43z"/><path fill="%231976D2" d="M43.6 20H24v8h11.3c-.8 2.5-2.3 4.6-4.3 6.1l5.9 5C40 35.9 44 30.4 44 24c0-1.3-.1-2.5-.4-3.6z"/></svg>') center/contain no-repeat}
.error{background:rgba(255,23,68,.1);border:1px solid rgba(255,23,68,.3);color:var(--danger);padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem}
.info-box{background:rgba(0,229,255,.06);border:1px solid rgba(0,229,255,.2);border-radius:8px;padding:.7rem 1rem;font-size:.85rem;color:var(--muted);margin-bottom:1rem}
.link{text-align:center;margin-top:.9rem;font-size:.9rem;color:var(--muted)}.link a{color:var(--primary);text-decoration:none;font-weight:600}
.legal{text-align:center;margin-top:1rem;font-size:.77rem;color:var(--muted)}.legal a{color:var(--muted);text-decoration:underline}
</style>
</head>
<body>
<div class="glow-bg"></div><div class="glow-bg2"></div>
<div class="box">
  <div class="logo">Indo <span>Global</span> Services</div>
  <h2>Create Account</h2>
  <p class="sub">Start earning 0.5% daily returns on your investment</p>
  <?php if ($refParam): ?><div class="info-box">🎁 You were referred — your referral link has been pre-filled.</div><?php endif; ?>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <a href="<?= htmlspecialchars($googleURL) ?>" class="btn-google">
    <span class="google-icon"></span> Sign up with Google
  </a>
  <div class="divider">or register with email</div>

  <form method="POST">
    <div class="form-group"><label>Full Name</label><input type="text" name="name" placeholder="John Doe" required></div>
    <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="you@example.com" required></div>
    <div class="form-group"><label>Password (min 8 chars)</label><input type="password" name="password" placeholder="••••••••" required></div>
    <div class="form-group"><label>Referral Code (optional)</label><input type="text" name="referral" value="<?= $refParam ?>"></div>
    <label style="display:flex;align-items:flex-start;gap:.6rem;font-size:.85rem;color:var(--muted);margin:1.5rem 0;cursor:pointer">
        <input type="checkbox" name="terms" required style="margin-top:.2rem;width:auto">
        <span>By registering, I confirm I have read and agree to the <a href="/legal/terms.html" style="color:var(--primary)">Terms of Service</a> & <a href="/legal/privacy.html" style="color:var(--primary)">Risk Disclaimer</a>. I understand that all investments carry risk.</span>
    </label>
    <button class="btn-main" type="submit">Create Account</button>
  </form>
  <div class="link">Already have an account? <a href="/app/auth/login.php">Sign in</a></div>
  <div class="legal">By registering you agree to our <a href="/legal/terms.html">Terms</a>, <a href="/legal/privacy.html">Privacy Policy</a> & <a href="/legal/refund.html">Refund Policy</a></div>
</div>
<script>if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');</script>
</body></html>
