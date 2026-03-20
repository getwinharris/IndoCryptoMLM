<?php
require_once __DIR__ . '/../config/session.php';
if (isLoggedIn()) { header('Location: /app/user/dashboard.php'); exit; }

$email = $_GET['email'] ?? '';
if (!$email) {
    header('Location: /app/auth/forgot_password.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredCode = trim($_POST['otp']);
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];
    
    if (strlen($newPass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($newPass !== $confirmPass) {
        $error = "Passwords do not match.";
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = "User not found or invalid request.";
        } elseif ($user['otp_code'] !== $enteredCode) {
            $error = "Invalid reset code.";
        } elseif (strtotime($user['otp_expires_at']) < time()) {
            $error = "This code has expired. Please request a new one.";
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            db()->prepare("UPDATE users SET password=?, otp_code=NULL, otp_expires_at=NULL WHERE id=?")->execute([$hash, $user['id']]);
            flash('success', 'Your password has been successfully reset! You can now log in.');
            header('Location: /app/auth/login.php');
            exit;
        }
    }
}

$pageTitle = 'Reset Password';
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
label{display:block;font-size:.85rem;color:var(--muted);margin-bottom:.4rem;font-weight:500}
input{width:100%;padding:1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1.05rem;outline:none}
input:focus{border-color:var(--primary)}
.otp-input{text-align:center;letter-spacing:5px;font-size:1.5rem}
.btn-main{width:100%;padding:1rem;border:none;border-radius:10px;background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;font-size:1rem;font-weight:700;cursor:pointer;margin-top:1rem}
.alert{padding:1rem;border-radius:10px;margin-bottom:1rem;font-size:.9rem;text-align:center}
.alert-danger{background:rgba(255,23,68,.1);border:1px solid rgba(255,23,68,.3);color:var(--danger)}
.link{text-align:center;margin-top:1.5rem;font-size:.9rem;color:var(--muted)}.link a{color:var(--primary);text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="box">
  <div class="logo"><img src="/assets/logo.svg" alt="IGS" style="height:60px;margin-bottom:1rem;display:block;margin-left:auto;margin-right:auto"> Indo <span>Global</span></div>
  <h2>Set New Password</h2>
  <p class="sub">Enter the 6-digit code sent to your email to verify your identity.</p>
  
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>6-Digit Reset Code</label>
      <input type="text" name="otp" required maxlength="6" pattern="\d{6}" placeholder="------" autocomplete="one-time-code" class="otp-input">
    </div>
    <div class="form-group">
      <label>New Password</label>
      <input type="password" name="new_password" required placeholder="••••••••">
    </div>
    <div class="form-group">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required placeholder="••••••••">
    </div>
    <button type="submit" class="btn-main">Update Password</button>
  </form>
  
  <div class="link">Return to <a href="/app/auth/login.php">Sign In</a></div>
</div>
</body>
</html>
