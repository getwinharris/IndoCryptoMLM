<?php
function googleAuthURL() {
    $client_id = setting('google_client_id');
    $redirect_uri = SITE_URL . '/app/auth/google.php';
    if (!$client_id) return '#';
    return "https://accounts.google.com/o/oauth2/v2/auth?scope=" . urlencode("email profile") . "&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&client_id=" . urlencode($client_id) . "&access_type=online";
}
