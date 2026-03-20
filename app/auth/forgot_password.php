<?php
require_once __DIR__ . '/../config/session.php';
if (isLoggedIn()) { header('Location: /app/user/dashboard.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $error = "Please enter your email.";
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email=? AND status=1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $otp = random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            db()->prepare("UPDATE users SET otp_code=?, otp_expires_at=? WHERE email=?")->execute([$otp, $expires, $email]);
            
            $msg = "We received a password reset request.\n\nYour OTP code is: $otp\nThis code will expire in 15 minutes.\nIf you did not request this, you can ignore this email.";
            $headers = "From: noreply@indoglobalservices.in\r\n";
            mail($email, "Password Reset Code", $msg, $headers);
        }
        
        // Always redirect so we don't expose if an email exists
        header('Location: /app/auth/reset_password.php?email=' . urlencode($email));
        exit;
    }
}

$pageTitle = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?> | IGS</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0c10;--card:#141820;--border:rgba(255,255,255,.07);--primary:#00e5ff;--secondary:#7000ff;--danger:#ff1744;--success:#00c853;--text:#fff;--muted:#8892a4}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Outfit',sans-serif}
body{background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:2.5rem;width:100%;max-width:440px;position:relative;z-index:1}
.logo{text-align:center;font-size:1.4rem;font-weight:800;margin-bottom:1.5rem}.logo span{color:var(--primary)}
h2{margin-bottom:.5rem;text-align:center}
p.sub{color:var(--muted);font-size:.9rem;text-align:center;margin-bottom:1.5rem}
.form-group{margin-bottom:1rem}
input{width:100%;padding:.9rem 1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1.05rem;outline:none;letter-spacing:1px}
input:focus{border-color:var(--primary)}
.btn-main{width:100%;padding:1rem;border:none;border-radius:10px;background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;font-size:1rem;font-weight:700;cursor:pointer;margin-top:1rem}
.alert{padding:1rem;border-radius:10px;margin-bottom:1rem;font-size:.9rem;text-align:center}
.alert-danger{background:rgba(255,23,68,.1);border:1px solid rgba(255,23,68,.3);color:var(--danger)}
.link{text-align:center;margin-top:1.5rem;font-size:.9rem;color:var(--muted)}.link a{color:var(--primary);text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="box">
  <div class="logo">Indo <span>Global</span></div>
  <h2>Forgot Password?</h2>
  <p class="sub">Enter your email address and we'll send you a 6-digit code to reset your password.</p>
  
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <input type="email" name="email" required placeholder="you@example.com">
    </div>
    <button type="submit" class="btn-main">Send Recovery Code</button>
  </form>
  
  <div class="link">Remembered it? <a href="/app/auth/login.php">Sign In</a></div>
</div>
</body>
</html>
