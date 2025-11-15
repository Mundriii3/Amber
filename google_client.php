<?php
// google_client.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php'; // from Composer

function getGoogleClientForCalendar() {
    $client = new Google_Client();
    $client->setApplicationName('Aesthetically A Booking');
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setAuthConfig(GOOGLE_CREDENTIALS_PATH);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously stored token
    if (file_exists(GOOGLE_TOKEN_PATH)) {
        $accessToken = json_decode(file_get_contents(GOOGLE_TOKEN_PATH), true);
        $client->setAccessToken($accessToken);

        // If token is expired, refresh it
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents(GOOGLE_TOKEN_PATH, json_encode($client->getAccessToken()));
            } else {
                // No refresh token, need to re-connect
                unlink(GOOGLE_TOKEN_PATH);
            }
        }
    }

    return $client;
}
