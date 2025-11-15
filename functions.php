<?php
// functions.php

require_once __DIR__ . '/config.php';

// Try to load Google API client (if installed via Composer)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// ---------- SIMPLE ESCAPE ----------
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ---------- IMAGE RESIZE HELPER (1280 x 720 FOR HERO) ----------
/**
 * Resize + center-crop an uploaded image to EXACT 1280x720 (16:9).
 * Uses GD. If GD is missing or format unsupported, returns false.
 *
 * You should only call this for the HERO image in admin.php.
 */
function resize_to_1280x720(string $tmpPath, string $destPath): bool
{
    if (!function_exists('getimagesize')) {
        return false; // GD not available
    }

    $info = @getimagesize($tmpPath);
    if ($info === false) {
        return false;
    }

    [$srcW, $srcH] = $info;
    $mime         = $info['mime'] ?? '';

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $srcImg = @imagecreatefromjpeg($tmpPath);
            $ext    = 'jpg';
            break;
        case 'image/png':
            $srcImg = @imagecreatefrompng($tmpPath);
            $ext    = 'png';
            break;
        case 'image/gif':
            $srcImg = @imagecreatefromgif($tmpPath);
            $ext    = 'gif';
            break;
        default:
            return false; // unsupported type
    }

    if (!$srcImg) {
        return false;
    }

    $targetW = 1280;
    $targetH = 720;
    $targetRatio = $targetW / $targetH;
    $srcRatio    = $srcW / $srcH;

    // Crop to center so we fill 16:9 exactly
    if ($srcRatio > $targetRatio) {
        // source is wider => crop left/right
        $newW = (int)round($srcH * $targetRatio);
        $newH = $srcH;
        $srcX = (int)(($srcW - $newW) / 2);
        $srcY = 0;
    } else {
        // source is taller => crop top/bottom
        $newW = $srcW;
        $newH = (int)round($srcW / $targetRatio);
        $srcX = 0;
        $srcY = (int)(($srcH - $newH) / 2);
    }

    $dstImg = imagecreatetruecolor($targetW, $targetH);

    // For PNG/GIF keep transparency
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $trans = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
        imagefilledrectangle($dstImg, 0, 0, $targetW, $targetH, $trans);
    }

    imagecopyresampled(
        $dstImg,
        $srcImg,
        0,
        0,
        $srcX,
        $srcY,
        $targetW,
        $targetH,
        $newW,
        $newH
    );

    // Make sure upload dir exists
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Save
    $ok = false;
    switch ($ext) {
        case 'jpg':
            $ok = imagejpeg($dstImg, $destPath, 85);
            break;
        case 'png':
            $ok = imagepng($dstImg, $destPath);
            break;
        case 'gif':
            $ok = imagegif($dstImg, $destPath);
            break;
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);

    return $ok;
}

// ---------- GENERIC JSON HELPERS ----------
function load_json(string $filename, $default) {
    $path = DATA_DIR . '/' . $filename;
    if (!file_exists($path)) {
        return $default;
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : $default;
}

function save_json(string $filename, $data): void {
    $path = DATA_DIR . '/' . $filename;

    // Ensure data directory exists
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ---------- BIO ----------
function load_bio(): array {
    $default = [
        'name'       => 'Aesthetically A',
        'bio'        => '',
        'location'   => '',
        'hero_image' => ''
    ];
    return load_json('bio.json', $default);
}

function save_bio(array $bio): void {
    save_json('bio.json', $bio);
}

// ---------- GALLERY ----------
function load_gallery(): array {
    return load_json('gallery.json', []);
}

function add_gallery_item(string $imagePath): void {
    $gallery   = load_gallery();
    $gallery[] = ['image' => $imagePath];
    save_json('gallery.json', $gallery);
}

// ---------- SERVICES ----------
function load_services(): array {
    return load_json('services.json', []);
}

function save_services(array $services): void {
    save_json('services.json', $services);
}

// ---------- APPAREL ----------
function load_apparel(): array {
    return load_json('apparel.json', []);
}

function save_apparel(array $apparel): void {
    save_json('apparel.json', $apparel);
}

// ---------- PRODUCTS ----------
function load_products(): array {
    return load_json('products.json', []);
}

function save_products(array $products): void {
    save_json('products.json', $products);
}

// ---------- BOOKINGS ----------
function load_bookings(): array {
    return load_json('bookings.json', []);
}

function save_bookings(array $bookings): void {
    save_json('bookings.json', $bookings);
}

// ---------- AVAILABILITY (per day schedule) ----------
/*
   Index = day of week (0 = Sunday ... 6 = Saturday)
   'open'  => bool
   'start' => "HH:MM" 24h
   'end'   => "HH:MM"
*/
function load_availability(): array {
    $default = [
        0 => ['label' => 'Sunday',    'open' => true,  'start' => '00:00', 'end' => '24:00'], // all day
        1 => ['label' => 'Monday',    'open' => true,  'start' => '06:00', 'end' => '15:00'], // 6–3
        2 => ['label' => 'Tuesday',   'open' => true,  'start' => '06:00', 'end' => '14:00'], // 6–2
        3 => ['label' => 'Wednesday', 'open' => true,  'start' => '06:00', 'end' => '15:00'], // 6–3
        4 => ['label' => 'Thursday',  'open' => true,  'start' => '06:00', 'end' => '15:00'], // 6–3
        5 => ['label' => 'Friday',    'open' => true,  'start' => '06:00', 'end' => '13:00'], // 6–1
        6 => ['label' => 'Saturday',  'open' => true,  'start' => '06:00', 'end' => '13:00'], // 6–1
    ];
    return load_json('availability.json', $default);
}

function save_availability(array $availability): void {
    save_json('availability.json', $availability);
}

// ---------- EMAIL (STYLIST) ----------
function send_booking_email(array $booking): void {
    $to      = STYLIST_EMAIL;
    $subject = "New Booking: " . $booking['service_name'];

    $event_link = build_google_calendar_link($booking); // for quick add

    $message = "New booking received:\n\n"
        . "Name: {$booking['client_name']}\n"
        . "Email: {$booking['client_email']}\n"
        . "Phone: {$booking['client_phone']}\n"
        . "Service: {$booking['service_name']}\n"
        . "Date: {$booking['date']} at {$booking['time']}\n"
        . "Notes: {$booking['notes']}\n"
        . "Deposit: {$booking['deposit']}\n"
        . "PayPal Order ID: {$booking['paypalOrderId']}\n\n"
        . "Add this appointment to Google Calendar:\n"
        . $event_link . "\n";

    // NOTE: On shared hosting you may need proper SMTP; basic mail() may not work everywhere.
    @mail($to, $subject, $message, "From: no-reply@{$_SERVER['SERVER_NAME']}");
}

// ---------- GOOGLE CALENDAR LINK (CLIENT-FRIENDLY) ----------
function build_google_calendar_link(array $booking): string {
    if (empty($booking['date']) || empty($booking['time'])) {
        return 'https://calendar.google.com/calendar/';
    }

    $start = new DateTime($booking['date'] . ' ' . $booking['time'], new DateTimeZone(GOOGLE_TIMEZONE));
    $end   = clone $start;
    $end->modify('+90 minutes'); // default duration

    $startStr = $start->format('Ymd\THis');
    $endStr   = $end->format('Ymd\THis');

    $text  = urlencode($booking['service_name'] . ' — ' . $booking['client_name']);
    $dates = $startStr . '/' . $endStr;

    $details = "Client: {$booking['client_name']}\n"
             . "Email: {$booking['client_email']}\n"
             . "Phone: {$booking['client_phone']}\n"
             . "Service: {$booking['service_name']}\n"
             . "Notes: {$booking['notes']}";

    $details = urlencode($details);
    $tz      = urlencode(GOOGLE_TIMEZONE);

    return "https://calendar.google.com/calendar/render?action=TEMPLATE"
         . "&text={$text}"
         . "&dates={$dates}"
         . "&details={$details}"
         . "&ctz={$tz}";
}

// ---------- GOOGLE CLIENT + AUTO-ADD EVENT (OPTIONAL) ----------
function getGoogleClientForCalendar() {
    // Only if Google client library + credentials exist
    if (!file_exists(GOOGLE_CREDENTIALS_PATH) || !class_exists('Google_Client')) {
        return null;
    }

    $client = new \Google_Client();
    $client->setApplicationName('Aesthetically A Booking');
    $client->setScopes(\Google_Service_Calendar::CALENDAR);
    $client->setAuthConfig(GOOGLE_CREDENTIALS_PATH);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    if (file_exists(GOOGLE_TOKEN_PATH)) {
        $accessToken = json_decode(file_get_contents(GOOGLE_TOKEN_PATH), true);
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents(GOOGLE_TOKEN_PATH, json_encode($client->getAccessToken()));
            } else {
                // No refresh token, need reconnect
                @unlink(GOOGLE_TOKEN_PATH);
            }
        }
    }

    return $client;
}

/**
 * Add booking directly into stylist's Google Calendar.
 * Returns true on success, false if not configured or failed.
 */
function add_booking_to_google_calendar(array $booking): bool {
    try {
        $client = getGoogleClientForCalendar();
        if (!$client || !file_exists(GOOGLE_TOKEN_PATH)) {
            return false;
        }

        $service = new \Google_Service_Calendar($client);

        $start = new DateTime($booking['date'] . ' ' . $booking['time'], new DateTimeZone(GOOGLE_TIMEZONE));
        $end   = clone $start;
        $end->modify('+90 minutes');

        $event = new \Google_Service_Calendar_Event([
            'summary'     => $booking['service_name'] . ' — ' . $booking['client_name'],
            'description' => "Client: {$booking['client_name']}\n"
                           . "Email: {$booking['client_email']}\n"
                           . "Phone: {$booking['client_phone']}\n"
                           . "Service: {$booking['service_name']}\n"
                           . "Notes: {$booking['notes']}\n"
                           . "Deposit: {$booking['deposit']}\n"
                           . "PayPal Order: {$booking['paypalOrderId']}",
            'start'       => [
                'dateTime' => $start->format(DateTime::RFC3339),
                'timeZone' => GOOGLE_TIMEZONE,
            ],
            'end'         => [
                'dateTime' => $end->format(DateTime::RFC3339),
                'timeZone' => GOOGLE_TIMEZONE,
            ],
        ]);

        $service->events->insert(GOOGLE_CALENDAR_ID, $event);
        return true;
    } catch (\Exception $e) {
        // On live hosting you could log this to a file
        return false;
    }
}
