<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/google_oauth.php';
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '/app/admin/dashboard.php' : '/app/user/dashboard.php'));
    exit;
}
$error = '';
$flash = getFlash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            if ($user['email_verified'] == 0) {
                // Prevent login if unverified
                header('Location: /app/auth/verify.php?email=' . urlencode($user['email']));
                exit;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header('Location: ' . ($user['is_admin'] ? '/app/admin/dashboard.php' : '/app/user/dashboard.php'));
            exit;
        } else { $error = 'Invalid email or password.'; }
    } else { $error = 'Please fill all fields.'; }
}
$googleURL = googleAuthURL();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login | Indo Global Services</title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#00e5ff">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="IGS">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0c10;--card:#141820;--border:rgba(255,255,255,.07);--primary:#00e5ff;--secondary:#7000ff;--danger:#ff1744;--success:#00c853;--text:#fff;--muted:#8892a4}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Outfit',sans-serif}
body{background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
.glow-bg{position:fixed;top:-20%;left:-10%;width:60vw;height:60vw;background:radial-gradient(circle,rgba(112,0,255,.25),transparent 70%);pointer-events:none;border-radius:50%}
.glow-bg2{position:fixed;bottom:-20%;right:-10%;width:50vw;height:50vw;background:radial-gradient(circle,rgba(0,229,255,.15),transparent 70%);pointer-events:none;border-radius:50%}
.box{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:2.5rem;width:100%;max-width:420px;position:relative;z-index:1}
.logo{text-align:center;font-size:1.4rem;font-weight:800;margin-bottom:1.5rem}.logo span{color:var(--primary)}
h2{font-size:1.5rem;margin-bottom:.3rem}
p.sub{color:var(--muted);font-size:.9rem;margin-bottom:1.5rem}
.form-group{margin-bottom:1.1rem}
label{display:block;font-size:.85rem;color:var(--muted);margin-bottom:.4rem;font-weight:500}
input{width:100%;padding:.85rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1rem;outline:none;transition:border .2s}
input:focus{border-color:var(--primary)}
.btn-main{width:100%;padding:.9rem;border:none;border-radius:10px;background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;font-size:1rem;font-weight:700;cursor:pointer;transition:opacity .2s;margin-top:.5rem}
.btn-main:hover{opacity:.9}
.divider{text-align:center;color:var(--muted);margin:1.2rem 0;font-size:.85rem;position:relative}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:var(--border)}
.divider::before{left:0}.divider::after{right:0}
.btn-google{width:100%;padding:.85rem;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.03);color:var(--text);font-size:.95rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.7rem;text-decoration:none;transition:background .2s}
.btn-google:hover{background:rgba(255,255,255,.07)}
.google-icon{width:20px;height:20px;background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path fill="%23FFC107" d="M43.6 20H24v8h11.3C33.5 32.4 29.2 35 24 35c-6.1 0-11-4.9-11-11s4.9-11 11-11c2.8 0 5.3 1 7.2 2.7l5.7-5.7C33.7 7.1 29.1 5 24 5 13.5 5 5 13.5 5 24s8.5 19 19 19c10.2 0 18.5-8.1 18.5-19 0-1.3-.1-2.5-.4-3.6z"/><path fill="%23FF3D00" d="m6.3 14.7 6.6 4.8C14.5 16 19 13 24 13c2.8 0 5.3 1 7.2 2.7l5.7-5.7C33.7 7.1 29.1 5 24 5c-7.6 0-14.1 4.3-17.7 9.7z"/><path fill="%234CAF50" d="M24 43c5 0 9.5-1.9 12.9-4.9l-5.9-5C29.3 34.5 26.8 35 24 35c-5.1 0-9.4-3.4-11-8H6.2C9.7 38.3 16.3 43 24 43z"/><path fill="%231976D2" d="M43.6 20H24v8h11.3c-.8 2.5-2.3 4.6-4.3 6.1l5.9 5C40 35.9 44 30.4 44 24c0-1.3-.1-2.5-.4-3.6z"/></svg>') center/contain no-repeat}
.error{background:rgba(255,23,68,.1);border:1px solid rgba(255,23,68,.3);color:var(--danger);padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem}
.alert-success{background:rgba(0,200,83,.1);border:1px solid rgba(0,200,83,.3);color:var(--success);padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem}
.link{text-align:center;margin-top:1rem;font-size:.9rem;color:var(--muted)}.link a{color:var(--primary);text-decoration:none;font-weight:600}
.legal{text-align:center;margin-top:1.2rem;font-size:.78rem;color:var(--muted)}.legal a{color:var(--muted);text-decoration:underline}
.install-bar{text-align:center;margin-top:1rem;font-size:.82rem;color:var(--muted)}.install-bar a{color:var(--primary);text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="glow-bg"></div><div class="glow-bg2"></div>
<div class="box">
  <div class="logo"><img src="/assets/logo.svg" alt="IGS" style="height:60px;margin-bottom:1rem;display:block;margin-left:auto;margin-right:auto"> Indo <span>Global</span> Services</div>
  <h2>Welcome back</h2>
  <p class="sub">Sign in to your investment account</p>
  <?php if ($flash): ?><div class="<?= $flash['type']==='success'?'alert-success':'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- Google Sign In -->
  <a href="<?= htmlspecialchars($googleURL) ?>" class="btn-google">
    <span class="google-icon"></span> Continue with Google
  </a>
  <div class="divider">or sign in with email</div>

  <form method="POST">
    <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="you@example.com" required></div>
    <div class="form-group">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
        <label style="margin:0">Password</label>
        <a href="/app/auth/forgot_password.php" style="color:var(--primary);font-size:.8rem;text-decoration:none">Forgot?</a>
      </div>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button class="btn-main" type="submit">Sign In</button>
  </form>
  <div class="link">Don't have an account? <a href="/app/auth/register.php">Create one</a></div>
  <div class="link" style="margin-top:.5rem"><a href="/">← Home</a> &nbsp;·&nbsp; <a href="/install.php">📱 Install App</a></div>
  <div class="legal">By signing in you agree to our <a href="/legal/terms.html">Terms</a>, <a href="/legal/privacy.html">Privacy Policy</a> & <a href="/legal/refund.html">Refund Policy</a></div>
</div>
<script>
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
</script>
</body></html>
