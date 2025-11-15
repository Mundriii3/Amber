<?php
// confirm.php
require_once __DIR__ . '/functions.php';

$id = $_GET['id'] ?? '';

$booking = null;
if ($id) {
    $bookings = load_bookings();
    foreach ($bookings as $b) {
        if (isset($b['id']) && $b['id'] === $id) {
            $booking = $b;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo e(SITE_NAME); ?> — Booking Confirmed</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="site-header">
  <h1><?php echo e(SITE_NAME); ?></h1>
  <nav>
    <a href="index.php">Home</a>
    <a href="booking.php">Booking</a>
    <a href="apparel.php">Apparel</a>
    <a href="products.php">Products</a>
    <a href="admin.php">Admin</a>
  </nav>
</header>

<main class="wrap">
  <section class="card">
    <?php if (!$booking): ?>
      <h2>Booking Confirmed</h2>
      <p>
        Your booking has been received. If you have any questions, please contact
        <?php echo e(STYLIST_EMAIL); ?>.
      </p>
    <?php else: ?>
      <h2>Thank you, <?php echo e($booking['client_name']); ?> ✨</h2>
      <p>Your booking is confirmed.</p>

      <p>
        <strong>Service:</strong> <?php echo e($booking['service_name']); ?><br>
        <strong>Date:</strong> <?php echo e($booking['date']); ?><br>
        <strong>Time:</strong> <?php echo e($booking['time']); ?><br>
        <strong>Deposit Paid:</strong>
        $<?php echo e(number_format((float)$booking['deposit'], 2)); ?><br>
      </p>

      <p class="small">
        A confirmation has been sent to the stylist at <?php echo e(STYLIST_EMAIL); ?>.
      </p>

      <?php
        // Build Google Calendar link for this booking
        $gcal_link = 'https://calendar.google.com/calendar/u/3?cid=YmY4ODg4NjYyNjY1MDhhZmVmMjhjNzk5OTdjZWNlMTEyMDE1OTg3YTgyMjVlOGVkODM2MTMwNzNhYTJmMGE4NkBncm91cC5jYWxlbmRhci5nb29nbGUuY29t'($booking);
      ?>

      <p style="margin-top:1rem;">
        <a href="<?php echo e($gcal_link); ?>" class="btn" target="_blank" rel="noopener">
          Add to Google Calendar
        </a>
      </p>

      <p class="small">
        If the Google Calendar page doesn’t open, you can copy and paste this link into your browser:<br>
        <span style="word-break:break-all;"><?php echo e($gcal_link); ?></span>
      </p>
    <?php endif; ?>

    <p style="margin-top:1.5rem;">
      <a href="index.php">&larr; Back to Home</a>
    </p>
  </section>
</main>
</body>
</html>
