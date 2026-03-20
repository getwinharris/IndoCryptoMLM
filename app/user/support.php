<?php
require_once __DIR__ . '/../config/session.php';
requireLogin(); if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        $error = "Subject and message are required.";
    } else {
        db()->prepare("INSERT INTO support_messages (user_id, sender_email, subject, message) VALUES (?, ?, ?, ?)")
            ->execute([$user['id'], $user['email'], $subject, $message]);
        $success = "Your message has been sent to our support team. We will reply to your email shortly.";
    }
}

$pageTitle = 'Support Center';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Contact Support</h1></div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card" style="max-width:600px">
  <h3 style="margin-bottom:1.5rem">Send a Message</h3>
  <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.5rem">If you need help with your account, investments, or KYC verification, please send us a message below. Our team (support@indoglobalservices.in) will reach out to you via email.</p>
  
  <form method="POST">
    <div style="margin-bottom:1rem">
      <label style="display:block;margin-bottom:.4rem;color:var(--muted);font-size:.85rem">Subject</label>
      <input type="text" name="subject" required placeholder="E.g. Deposit Issue" style="width:100%;padding:1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1rem;outline:none">
    </div>
    <div style="margin-bottom:1.5rem">
      <label style="display:block;margin-bottom:.4rem;color:var(--muted);font-size:.85rem">Message</label>
      <textarea name="message" required rows="5" placeholder="Describe your issue in detail..." style="width:100%;padding:1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1rem;outline:none;resize:vertical"></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send to Support</button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
