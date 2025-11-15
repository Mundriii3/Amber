<?php
// config.php

// Base path of the site (directory this file is in)
define('BASE_PATH', __DIR__);

// Where JSON data files are stored
define('DATA_DIR', BASE_PATH . '/data');

// Where uploaded images are stored
define('UPLOAD_DIR', BASE_PATH . '/uploads');

// Make sure directories exist (create if missing)
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ---- Site meta ----
define('SITE_NAME', 'Aesthetically A');

// Email where new booking notifications go
define('STYLIST_EMAIL', 'aestheticallyallc@gmail.com');  // 🔁 change this

// Simple admin password for /admin.php
define('ADMIN_PASSWORD', 'idohair2025');                     // 🔁 change this

// ---- PayPal ----
// Client ID from your PayPal app (live or sandbox)
define('PAYPAL_CLIENT_ID', 'AcL2YKnVpAmaqr_tolC6xstVWli6qhRjWU-AmEk32ZjEj_0oBtp2hCzIg5CRvcLVGOkcWRcRO77EJmIs'); // 🔁 change this
define('PAYPAL_CURRENCY', 'USD');

// ---- Google Calendar (optional auto-add) ----
// Used for time zones & calendar event URL generation
define('GOOGLE_TIMEZONE', 'America/Chicago');

// If you later use Google API to auto-create events,
// this is the calendar ID (e.g. 'primary' or full calendar email).
define('GOOGLE_CALENDAR_ID', 'primary');

// Google OAuth credential + token paths (only needed if you wire up API)
define('GOOGLE_CREDENTIALS_PATH', BASE_PATH . '/google-credentials.json');
define('GOOGLE_TOKEN_PATH',       BASE_PATH . '/google-token.json');

