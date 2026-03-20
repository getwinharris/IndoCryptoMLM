<?php
$pageTitle = "Privacy Policy - Indo Global Services";
// If being included in another file, don't re-include header/footer
if (!defined('APP_DIR')) {
    require_once __DIR__ . '/app/config/config.php';
    include __DIR__ . '/app/includes/header.php';
}
?>

<div class="topbar">
    <h1>Privacy Policy</h1>
</div>

<div class="card" style="line-height: 1.6; color: var(--text);">
    <p>Last Updated: March 20, 2026</p>

    <h3>1. Information We Collect</h3>
    <p>We collect personal information that you provide to us, including but not limited to your name, email address, payment details (processed securely via Stripe), and identification documents for KYC (Know Your Customer) compliance.</p>

    <h3>2. How We Use Your Information</h3>
    <p>Your information is used to provide and improve our services, process transactions, communicate with you, and ensure compliance with anti-money laundering (AML) regulations.</p>

    <h3>3. Data Sharing and Stripe</h3>
    <p>We use Stripe for payment, analytics, and other business services. Stripe collects identifying information about the devices that connect to its services. Stripe uses this information to operate and improve the services it provides to us, including for fraud detection. You can learn more about Stripe and read its privacy policy at <a href="https://stripe.com/privacy" target="_blank" style="color:var(--primary)">https://stripe.com/privacy</a>.</p>

    <h3>4. Cookies</h3>
    <p>We use cookies to maintain your session and improve your user experience. You can disable cookies in your browser settings, though some features of the platform may not function correctly.</p>

    <h3>5. Security</h3>
    <p>We implement industry-standard security measures to protect your data. However, no method of transmission over the internet is 100% secure.</p>

    <h3>6. Your Rights</h3>
    <p>You have the right to access, correct, or delete your personal data. Please contact us at support@indoglobalservices.in for any data-related requests.</p>
</div>

<?php if (!defined('APP_DIR')) include __DIR__ . '/app/includes/footer.php'; ?>
