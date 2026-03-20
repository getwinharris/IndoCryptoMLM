<?php
require_once __DIR__ . '/../config/session.php';
if (isLoggedIn()) { header('Location: /app/user/dashboard.php'); exit; }

$email = $_GET['email'] ?? '';
if (!$email) {
    header('Location: /app/auth/login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        // Generate new OTP
        $otp = random_int(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        db()->prepare("UPDATE users SET otp_code=?, otp_expires_at=? WHERE email=?")->execute([$otp, $expires, $email]);
        
        $msg = "Your Indo Global Services verification code is: $otp\nThis code will expire in 15 minutes.";
        $headers = "From: noreply@indoglobalservices.in\r\n";
        mail($email, "Registration Verification Code", $msg, $headers);
        
        $success = "A new code has been sent to your email.";
    } elseif (isset($_POST['verify'])) {
        $enteredCode = trim($_POST['otp']);
        $stmt = db()->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = "User not found.";
        } elseif ($user['email_verified']) {
            flash('success', 'Your email is already verified. You can log in.');
            header('Location: /app/auth/login.php');
            exit;
        } elseif ($user['otp_code'] !== $enteredCode) {
            $error = "Invalid verification code.";
        } elseif (strtotime($user['otp_expires_at']) < time()) {
            $error = "This code has expired. Please request a new one.";
        } else {
            db()->prepare("UPDATE users SET email_verified=1, otp_code=NULL, otp_expires_at=NULL WHERE id=?")->execute([$user['id']]);
            flash('success', 'Email verified successfully! You can now log in.');
            header('Location: /app/auth/login.php');
            exit;
        }
    }
}

$pageTitle = 'Verify Email';
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
input{width:100%;padding:1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1.5rem;outline:none;text-align:center;letter-spacing:5px}
input:focus{border-color:var(--primary)}
.btn-main{width:100%;padding:1rem;border:none;border-radius:10px;background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;font-size:1rem;font-weight:700;cursor:pointer;margin-top:1rem}
.alert{padding:1rem;border-radius:10px;margin-bottom:1rem;font-size:.9rem;text-align:center}
.alert-danger{background:rgba(255,23,68,.1);border:1px solid rgba(255,23,68,.3);color:var(--danger)}
.alert-success{background:rgba(0,200,83,.1);border:1px solid rgba(0,200,83,.3);color:var(--success)}
.resend{background:none;border:none;color:var(--primary);cursor:pointer;font-size:.9rem;text-decoration:underline;margin-top:1.5rem;display:block;width:100%;text-align:center}
</style>
</head>
<body>
<div class="box">
  <div class="logo"><img src="/assets/logo.svg" alt="IGS" style="height:60px;margin-bottom:1rem;display:block;margin-left:auto;margin-right:auto"> Indo <span>Global</span></div>
  <h2>Verify Your Email</h2>
  <p class="sub">We sent a 6-digit code to <b><?= htmlspecialchars($email) ?></b>. Check your spam folder if it doesn't arrive.</p>
  
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <input type="text" name="otp" required maxlength="6" pattern="\d{6}" placeholder="------" autocomplete="one-time-code">
    </div>
    <button type="submit" name="verify" class="btn-main">Verify Account</button>
  </form>
  
  <form method="POST">
    <button type="submit" name="resend" class="resend">Didn't get a code? Resend</button>
  </form>
</div>
</body>
</html>
