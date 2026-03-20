<?php
$pageTitle = "Refund Policy - Indo Global Services";
if (!defined('APP_DIR')) {
    require_once __DIR__ . '/app/config/config.php';
    include __DIR__ . '/app/includes/header.php';
}
?>

<div class="topbar">
    <h1>Refund Policy</h1>
</div>

<div class="card" style="line-height: 1.6; color: var(--text);">
    <p>Last Updated: March 20, 2026</p>

    <h3>1. Wallet Deposits</h3>
    <p>Deposits made to your Indo Global Services wallet via Stripe are intended for investment purposes. You may withdraw your unused wallet balance at any time, subject to a 5% system processing fee.</p>

    <h3>2. Active Investments</h3>
    <p>Once funds are committed to an investment plan, they are locked for the duration of the plan (400 days). **Active investments are non-refundable and cannot be cancelled once initiated.**</p>

    <h3>3. Daily ROI and Commissions</h3>
    <p>Daily ROI and referral commissions are credited to your wallet balance and can be withdrawn according to our standard withdrawal terms.</p>

    <h3>4. Exceptional Circumstances</h3>
    <p>In the event of a technical error where funds were deducted incorrectly, please contact support@indoglobalservices.in within 24 hours for a resolution.</p>

    <h3>5. Dispute Resolution</h3>
    <p>We encourage users to contact us directly to resolve any issues. Initiating a chargeback without prior communication may result in immediate account suspension.</p>
</div>

<?php if (!defined('APP_DIR')) include __DIR__ . '/app/includes/footer.php'; ?>
