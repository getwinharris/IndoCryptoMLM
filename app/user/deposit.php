<?php
require_once __DIR__ . '/../config/session.php';
requireLogin(); if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }
$user = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount < 10) {
        $error = "Minimum deposit is $10.";
    } else {
        // Stripe checkout logic
        $stripeKey = setting('stripe_secret_key');
        if (!$stripeKey) {
            $error = "Stripe is not currently configured by the admin.";
        } else {
            // Require autoloader
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
                try {
                    \Stripe\Stripe::setApiKey($stripeKey);
                    $domain = SITE_URL;
                    $checkout_session = \Stripe\Checkout\Session::create([
                      'payment_method_types' => ['card'],
                      'line_items' => [[
                        'price_data' => [
                          'currency' => 'usd',
                          'unit_amount' => $amount * 100, // cents
                          'product_data' => [
                            'name' => 'Wallet Top-up',
                            'description' => 'Deposit funds to your Indo Global Services investment wallet.',
                          ],
                        ],
                        'quantity' => 1,
                      ]],
                      'mode' => 'payment',
                      'success_url' => $domain . '/app/user/dashboard.php?deposit=success',
                      'cancel_url' => $domain . '/app/user/deposit.php?deposit=cancelled',
                      'client_reference_id' => $user['id'],
                      'metadata' => [
                          'user_id' => $user['id'],
                          'type' => 'deposit'
                      ]
                    ]);
                    header("HTTP/1.1 303 See Other");
                    header("Location: " . $checkout_session->url);
                    exit;
                } catch (Exception $e) {
                    $error = "Payment gateway error: " . $e->getMessage();
                }
            } else {
                $error = "Stripe SDK not installed on the server.";
            }
        }
    }
}
$pageTitle = 'Deposit Funds';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>Top-up Wallet</h1></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (isset($_GET['deposit']) && $_GET['deposit'] === 'cancelled'): ?>
    <div class="alert alert-warning">Payment was cancelled. You can try again below.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <div class="card">
    <h3 style="margin-bottom:1.5rem">Deposit USD</h3>
    <form method="POST">
      <div style="margin-bottom:1.5rem">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Amount to Deposit (USD)</label>
        <input type="number" name="amount" min="10" step="1" required placeholder="e.g. 500" style="width:100%;padding:1rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:1.1rem;outline:none">
      </div>
      <div style="background:rgba(0,229,255,.05);border:1px solid rgba(0,229,255,.15);border-radius:10px;padding:1rem;margin-bottom:1.5rem;font-size:.9rem">
        <div style="display:flex;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid var(--border)"><span style="color:var(--muted)">Current Balance</span><span style="color:var(--primary);font-weight:700">$<?= number_format($user['wallet_balance'], 2) ?></span></div>
        <div style="display:flex;justify-content:space-between;padding:.3rem 0"><span style="color:var(--muted)">Payment Method</span><span style="color:#fff;font-weight:600"><i class="fab fa-cc-stripe"></i> Stripe Secure</span></div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-credit-card"></i> Proceed to Checkout</button>
    </form>
  </div>
  <div>
    <div class="card" style="margin-bottom:1rem;background:rgba(0,200,83,.05);border:1px solid rgba(0,200,83,.2)">
      <h4 style="margin-bottom:1rem;color:var(--success)"><i class="fas fa-shield-alt"></i> Secure Payments</h4>
      <p style="font-size:.9rem;color:var(--muted);line-height:1.6">All deposits are processed securely via Stripe. We do not store your credit card information. Funds are immediately credited to your wallet upon successful transaction.</p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
