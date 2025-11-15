<?php
// bookings_feed.php
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$bookings = load_bookings();
$events   = [];

foreach ($bookings as $b) {
    if (empty($b['date']) || empty($b['time'])) {
        continue;
    }

    try {
        $start = new DateTime($b['date'] . ' ' . $b['time'], new DateTimeZone(GOOGLE_TIMEZONE));
        $end   = clone $start;
        $end->modify('+2 hours'); // block length in calendar

        $events[] = [
            'title'  => $b['service_name'] . ' — ' . $b['client_name'],
            'start'  => $start->format(DateTime::RFC3339),
            'end'    => $end->format(DateTime::RFC3339),
            'display'=> 'background'
        ];
    } catch (Exception $e) {
        continue;
    }
}

echo json_encode($events);
