<?php
// save_booking.php
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Read JSON from PayPal JS fetch
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

// Get service name (double-check against services list if present)
$services = load_services();
$idx      = isset($data['serviceIndex']) ? (int)$data['serviceIndex'] : -1;

$service_name = $data['service_name'] ?? (
    (isset($services[$idx]['name']) ? $services[$idx]['name'] : 'Service')
);

// Build booking record
$booking = [
    'id'            => uniqid('bk_', true),
    'service_name'  => $service_name,
    'date'          => $data['date']         ?? '',
    'time'          => $data['time']         ?? '',
    'client_name'   => $data['client_name']  ?? '',
    'client_email'  => $data['client_email'] ?? '',
    'client_phone'  => $data['client_phone'] ?? '',
    'notes'         => $data['notes']        ?? '',
    'deposit'       => $data['deposit']      ?? '',
    'paypalOrderId' => $data['paypalOrderId'] ?? '',
    'created_at'    => date('c')
];

// Save to JSON “database”
$bookings   = load_bookings();
$bookings[] = $booking;
save_bookings($bookings);

// Email stylist (PHP mail – will really work once on live hosting with SMTP)
send_booking_email($booking);

// Auto-add to stylist Google Calendar (if configured)
// This will just quietly return false if Google is not set up yet.
add_booking_to_google_calendar($booking);

// Build Google Calendar "Add Event" link (for client / confirm page)
$gcal_link = 'https://calendar.google.com/calendar/u/3/r?cid=YmY4ODg4NjYyNjY1MDhhZmVmMjhjNzk5OTdjZWNlMTEyMDE1OTg3YTgyMjVlOGVkODM2MTMwNzNhYTJmMGE4NkBncm91cC5jYWxlbmRhci5nb29nbGUuY29t'($booking);

echo json_encode([
    'success'   => true,
    'gcal_link' => $gcal_link,
    'id'        => $booking['id']
]);
exit;


