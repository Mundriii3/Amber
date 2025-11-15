<?php
// google-connect.php
require_once __DIR__ . '/google_client.php';

$client = getGoogleClientForCalendar();

// If Google callback with "code"
if (isset($_GET['code'])) {
    $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $accessToken = $client->getAccessToken();

    if (!empty($accessToken)) {
        file_put_contents(GOOGLE_TOKEN_PATH, json_encode($accessToken));
        echo "<h2>Google Calendar connected ✅</h2>";
        echo "<p>You can close this window and start accepting bookings.</p>";
    } else {
        echo "<h2>Failed to get access token.</h2>";
    }
    exit;
}

// If we already have a token, just say it's connected
if (file_exists(GOOGLE_TOKEN_PATH)) {
    echo "<h2>Google Calendar is already connected ✅</h2>";
    exit;
}

// No token and no code → start OAuth flow
$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
