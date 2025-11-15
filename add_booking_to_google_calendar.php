// At top of functions.php:
require_once __DIR__ . '/google_client.php';
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

// ...

function add_booking_to_google_calendar(array $booking): bool
{
    try {
        $client = getGoogleClientForCalendar();

        // If there's no token yet, just skip (no crash)
        if (!file_exists(GOOGLE_TOKEN_PATH)) {
            return false;
        }

        $service = new Google_Service_Calendar($client);

        // Build start/end times (assuming 90-minute slot by default)
        $startDateTime = date('c', strtotime($booking['date'] . ' ' . $booking['time']));
        $endDateTime   = date('c', strtotime($booking['date'] . ' ' . $booking['time'] . ' +90 minutes'));

        $summary = $booking['service_name'] . ' — ' . $booking['client_name'];

        $description = "Client: {$booking['client_name']}\n"
            . "Email: {$booking['client_email']}\n"
            . "Phone: {$booking['client_phone']}\n"
            . "Service: {$booking['service_name']}\n"
            . "Notes: {$booking['notes']}\n"
            . "Deposit: {$booking['deposit']}\n"
            . "PayPal Order: {$booking['paypalOrderId']}";

        $event = new Google_Service_Calendar_Event([
            'summary'     => $summary,
            'description' => $description,
            'start'       => [
                'dateTime' => $startDateTime,
                'timeZone' => GOOGLE_TIMEZONE,
            ],
            'end'         => [
                'dateTime' => $endDateTime,
                'timeZone' => GOOGLE_TIMEZONE,
            ],
        ]);

        $service->events->insert(GOOGLE_CALENDAR_ID, $event);
        return true;
    } catch (Exception $e) {
        // You can log $e->getMessage() to a file if needed
        return false;
    }
}
