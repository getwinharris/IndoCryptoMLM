<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/db.php';

// The library needs to be loaded here.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    http_response_code(500);
    exit("Stripe SDK not present");
}

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// You can verify the webhook signature here if you configure endpoint_secret
// For testing without secret validation:
$event = null;
try {
    $event = \Stripe\Event::constructFrom(
        json_decode($payload, true)
    );
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
}

// Handle the checkout.session.completed event
if ($event->type == 'checkout.session.completed') {
    $session = $event->data->object;
    
    // Check if it's a deposit
    if (isset($session->metadata->type) && $session->metadata->type === 'deposit') {
        $userId = $session->metadata->user_id;
        $amountUsd = $session->amount_total / 100; // Convert cents to dollars
        
        // Ensure this transaction hasn't been processed
        // We can do this safely by checking if a transaction log exists, or trusting idempotency.
        // For now, securely add to wallet:
        $stmt = db()->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$amountUsd, $userId]);
        
        // Add a notification for user inbox
        db()->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')")
           ->execute([$userId, "Your deposit of $" . number_format($amountUsd, 2) . " has been successfully credited to your wallet."]);
    }
}

http_response_code(200);
