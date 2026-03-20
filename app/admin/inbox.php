<?php
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_id'])) {
    $id = (int)$_POST['reply_id'];
    $reply_msg = trim($_POST['reply_message'] ?? '');
    
    $stmt = db()->prepare("SELECT * FROM support_messages WHERE id = ?");
    $stmt->execute([$id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        $error = "Ticket not found.";
    } elseif (empty($reply_msg)) {
        $error = "Reply message cannot be empty.";
    } else {
        // Send email to user
        $headers = "From: support@indoglobalservices.in\r\n";
        $subject = "RE: " . $ticket['subject'];
        $body = "Dear User,\n\n" . $reply_msg . "\n\n--\nSupport Team\nIndo Global Services";
        
        mail($ticket['sender_email'], $subject, $body, $headers);
        
        // Mark closed
        db()->prepare("UPDATE support_messages SET status = 'closed' WHERE id = ?")->execute([$id]);
        $success = "Reply sent securely via support@indoglobalservices.in and ticket closed.";
    }
}

if (isset($_GET['close'])) {
    db()->prepare("UPDATE support_messages SET status = 'closed' WHERE id = ?")->execute([(int)$_GET['close']]);
    $success = "Ticket marked as closed.";
}

if (isset($_GET['delete'])) {
    db()->prepare("DELETE FROM support_messages WHERE id = ?")->execute([(int)$_GET['delete']]);
    $success = "Ticket deleted.";
}

// Fetch Open
$open = db()->query("SELECT s.*, u.name as user_name FROM support_messages s LEFT JOIN users u ON s.user_id = u.id WHERE s.status = 'open' ORDER BY s.created_at ASC")->fetchAll();
// Fetch Closed
$closed = db()->query("SELECT s.*, u.name as user_name FROM support_messages s LEFT JOIN users u ON s.user_id = u.id WHERE s.status = 'closed' ORDER BY s.created_at DESC LIMIT 50")->fetchAll();

$pageTitle = 'Support Inbox';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Support Inbox</h1></div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:2rem">
  <h3 style="margin-bottom:1.5rem">Open Tickets <span style="background:var(--danger);color:#fff;padding:.1rem .6rem;border-radius:10px;font-size:.8rem;vertical-align:middle;margin-left:5px"><?= count($open) ?></span></h3>
  <?php if (!count($open)): ?>
    <p style="color:var(--muted);font-size:.9rem">No open tickets at the moment.</p>
  <?php else: ?>
    <?php foreach ($open as $t): ?>
      <div style="background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:10px;padding:1.5rem;margin-bottom:1rem">
        <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
          <strong style="color:var(--primary);font-size:1.1rem"><?= htmlspecialchars($t['subject']) ?></strong>
          <span style="color:var(--muted);font-size:.8rem"><?= date('M j, Y H:i', strtotime($t['created_at'])) ?></span>
        </div>
        <div style="margin-bottom:1rem;color:var(--muted);font-size:.85rem">
            From: <b><?= htmlspecialchars($t['user_name'] ?: 'Unknown') ?></b> (<?= htmlspecialchars($t['sender_email']) ?>)
        </div>
        <p style="background:var(--bg);padding:1rem;border-radius:8px;font-size:.95rem;line-height:1.5;margin-bottom:1.5rem"><?= nl2br(htmlspecialchars($t['message'])) ?></p>
        
        <form method="POST" style="background:rgba(0,229,255,.05);padding:1rem;border-radius:8px;border:1px solid rgba(0,229,255,.1)">
          <input type="hidden" name="reply_id" value="<?= $t['id'] ?>">
          <strong style="display:block;margin-bottom:.5rem;font-size:.9rem"><i class="fas fa-reply"></i> Reply directly via support@indoglobalservices.in</strong>
          <textarea name="reply_message" rows="3" required placeholder="Type your response here..." style="width:100%;padding:.8rem;background:rgba(0,0,0,.2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.95rem;outline:none;resize:vertical;margin-bottom:.8rem"></textarea>
          <div style="display:flex;gap:1rem">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Send Reply & Close</button>
            <a href="?close=<?= $t['id'] ?>" class="btn btn-danger btn-sm" style="text-decoration:none;background:rgba(255,255,255,.1);color:var(--muted);border-color:transparent">Mark Closed (No Reply)</a>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="card">
  <h3 style="margin-bottom:1.5rem;color:var(--muted)">Recently Closed</h3>
  <?php if (!count($closed)): ?>
    <p style="color:var(--muted);font-size:.9rem">No closed tickets.</p>
  <?php else: ?>
    <table style="width:100%;text-align:left;border-collapse:collapse">
      <tr style="border-bottom:1px solid var(--border)"><th style="padding:.8rem">Subject</th><th style="padding:.8rem">User</th><th style="padding:.8rem">Date</th><th style="padding:.8rem">Action</th></tr>
      <?php foreach ($closed as $t): ?>
        <tr style="border-bottom:1px solid rgba(255,255,255,.03)">
          <td style="padding:.8rem"><?= htmlspecialchars($t['subject']) ?></td>
          <td style="padding:.8rem"><?= htmlspecialchars($t['user_name']) ?></td>
          <td style="padding:.8rem;color:var(--muted);font-size:.85rem"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
          <td style="padding:.8rem"><a href="?delete=<?= $t['id'] ?>" onclick="return confirm('Delete entirely?')" style="color:var(--danger);text-decoration:none;font-size:.85rem"><i class="fas fa-trash"></i> Delete</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
