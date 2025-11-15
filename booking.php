<?php
// booking.php
require_once __DIR__ . '/functions.php';

$services     = load_services();
$availability = load_availability();
$error        = isset($_GET['error']);

// Compute earliest / latest time for the week view
$slotMinTime = '06:00:00';
$slotMaxTime = '24:00:00';

$minHour = 24;
$maxHour = 0;
foreach ($availability as $day) {
    if (!$day['open']) continue;
    list($sh) = explode(':', $day['start']);
    list($eh) = explode(':', $day['end']);
    $sh = (int)$sh;
    $eh = (int)$eh;
    if ($sh < $minHour) $minHour = $sh;
    if ($eh > $maxHour) $maxHour = $eh;
}
if ($minHour < 24) {
    $slotMinTime = str_pad($minHour, 2, '0', STR_PAD_LEFT) . ':00:00';
}
if ($maxHour > 0) {
    $slotMaxTime = str_pad($maxHour, 2, '0', STR_PAD_LEFT) . ':00:00';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo e(SITE_NAME); ?> — Booking</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="styles.css">

  <!-- FullCalendar CSS -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />

  <!-- PayPal SDK -->
  <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=<?php echo PAYPAL_CURRENCY; ?>"></script>

  <!-- FullCalendar JS -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

  <style>
    #calendar {
      max-width: 100%;
      margin: 1rem auto 1.5rem;
      background: #000000;
      border-radius: 20px;
      padding: 0.75rem;
      border: 1px solid #ff00c8;
      box-shadow: 0 0 15px rgba(255, 0, 200, 0.35);
      min-height: 500px;
    }
    .fc {
      color: #ffffff;
    }
    .fc .fc-toolbar-title,
    .fc .fc-col-header-cell-cushion,
    .fc .fc-daygrid-day-number {
      color: #ffffff;
    }
    .fc .fc-button-primary {
      background: #ff00c8;
      border-color: #ff00c8;
    }
    .fc .fc-button-primary:hover {
      background: #ffffff;
      color: #000000;
      border-color: #ff00c8;
    }
    .fc .fc-timegrid-slot {
      height: 3rem;
    }
  </style>
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
    <h2>Book an Appointment</h2>
    <p class="small">
      1️⃣ Tap a 2-hour slot in the calendar (only available hours show).<br>
      2️⃣ Confirm the date & time in the form.<br>
      3️⃣ Pay your non-refundable deposit via PayPal to lock in your spot.
    </p>

    <?php if ($error): ?>
      <div class="notice error">
        Something went wrong saving your booking.  
        If your PayPal payment went through, please contact <?php echo e(STYLIST_EMAIL); ?>.
      </div>
    <?php endif; ?>

    <?php if (!$services): ?>
      <p>No services have been added yet. Check back soon.</p>
    <?php else: ?>

      <!-- Calendar -->
      <div id="calendar"></div>

      <!-- Booking form -->
      <form id="booking-form" onsubmit="return false;">
        <!-- SERVICE -->
        <div class="field">
          <label for="service">Service</label>
          <select id="service" name="service_id" required>
            <option value="">Select a service</option>
            <?php foreach ($services as $index => $s): ?>
              <option
                value="<?php echo $index; ?>"
                data-name="<?php echo e($s['name']); ?>"
                data-deposit="<?php echo e($s['deposit']); ?>"
              >
                <?php echo e($s['name']); ?> — $<?php echo e(number_format($s['price'], 2)); ?>
                (Deposit: $<?php echo e(number_format($s['deposit'], 2)); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- DATE / TIME -->
        <div class="field">
          <label for="date">Preferred Date</label>
          <input type="date" id="date" name="date" required>
          <p class="small">This fills automatically when you click a slot in the calendar.</p>
        </div>

        <div class="field">
          <label for="time">Preferred Time</label>
          <input type="time" id="time" name="time" required>
        </div>

        <!-- CLIENT INFO -->
        <div class="field">
          <label for="name">Your Name</label>
          <input type="text" id="name" name="client_name" required>
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="client_email" required>
        </div>

        <div class="field">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="client_phone" required>
        </div>

        <div class="field">
          <label for="notes">Notes (optional)</label>
          <textarea id="notes" name="notes" rows="3"></textarea>
        </div>

        <!-- DEPOSIT -->
        <div class="field">
          <strong>Deposit Due: $<span id="deposit-display">0.00</span></strong>
          <input type="hidden" id="deposit" name="deposit" value="0">
        </div>

        <p class="small">
          By booking you agree that the deposit is non-refundable and goes toward your total service price.
        </p>

        <hr>

        <h3>Step 3: Pay deposit with PayPal</h3>
        <p class="small">
          Click the PayPal button below to submit your deposit and finalize your booking.
        </p>

        <div id="paypal-button-container"></div>

        <p class="small" id="paypal-fallback" style="display:none;color:#fca5a5;">
          If you don't see the PayPal button, refresh the page or turn off ad-blockers and try again.
        </p>
      </form>
    <?php endif; ?>
  </section>
</main>

<script>
const AVAILABILITY = <?php echo json_encode($availability); ?>;
const SLOT_DURATION_HOURS = 2;

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const dateInput  = document.getElementById('date');
  const timeInput  = document.getElementById('time');

  function isWithinHours(start) {
    const dow = start.getDay(); // 0 = Sunday
    const day = AVAILABILITY[dow];
    if (!day || !day.open) return false;

    const hour = start.getHours() + start.getMinutes() / 60;

    const [sh, sm] = day.start.split(':').map(Number);
    const [eh, em] = day.end.split(':').map(Number);

    const open  = sh + (sm / 60);
    const close = eh + (em / 60);

    // require full 2-hour slot before closing
    return hour >= open && hour <= (close - SLOT_DURATION_HOURS);
  }

  function isTwoHoursFromEvents(start, calendar) {
    const events  = calendar.getEvents();
    const twoH    = SLOT_DURATION_HOURS * 60 * 60 * 1000;
    const startMs = start.getTime();
    const endMs   = startMs + twoH;

    for (const ev of events) {
      if (!ev.start || !ev.end) continue;
      const evStart = ev.start.getTime();
      const evEnd   = ev.end.getTime();

      // Safe only if this slot ends at least 2h before event
      // OR starts at least 2h after event.
      const safe = (endMs <= evStart - twoH) || (startMs >= evEnd + twoH);
      if (!safe) return false;
    }
    return true;
  }

  if (calendarEl) {
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'timeGridWeek',
      nowIndicator: true,
      selectable: true,
      selectMirror: true,
      allDaySlot: false,
      slotDuration: '02:00:00',
      slotMinTime: '<?php echo $slotMinTime; ?>',
      slotMaxTime: '<?php echo $slotMaxTime; ?>',
      businessHours: [
        <?php foreach ($availability as $dow => $day): ?>
          <?php if ($day['open']): ?>
        { daysOfWeek: [<?php echo $dow; ?>], startTime: '<?php echo $day['start']; ?>', endTime: '<?php echo $day['end']; ?>' },
          <?php endif; ?>
        <?php endforeach; ?>
      ],
      events: 'bookings_feed.php',   // show existing bookings
      eventDisplay: 'background',
      eventColor: '#ff00c855',
      selectAllow: function(info) {
        const start = info.start;
        if (!isWithinHours(start)) return false;
        return isTwoHoursFromEvents(start, calendar);
      },
      select: function(info) {
        const start = info.start;

        const year  = start.getFullYear();
        const month = String(start.getMonth() + 1).padStart(2, '0');
        const day   = String(start.getDate()).padStart(2, '0');
        const hours = String(start.getHours()).padStart(2, '0');
        const mins  = String(start.getMinutes()).padStart(2, '0');

        if (dateInput) dateInput.value = `${year}-${month}-${day}`;
        if (timeInput) timeInput.value = `${hours}:${mins}`;
      }
    });

    calendar.render();
  }

  // ==== DEPOSIT + PAYPAL LOGIC ====
  const serviceSelect   = document.getElementById('service');
  const depositField    = document.getElementById('deposit');
  const depositDisplay  = document.getElementById('deposit-display');
  const paypalFallback  = document.getElementById('paypal-fallback');

  if (serviceSelect) {
    serviceSelect.addEventListener('change', () => {
      const opt = serviceSelect.options[serviceSelect.selectedIndex];
      const deposit = opt && opt.dataset.deposit ? opt.dataset.deposit : "0";
      depositField.value = deposit;
      depositDisplay.textContent = parseFloat(deposit).toFixed(2);
    });
  }

  let bookingData = {};

  function collectBookingData() {
    const form = document.getElementById('booking-form');
    const opt  = serviceSelect.options[serviceSelect.selectedIndex];

    bookingData = {
      serviceIndex: form.service_id.value,
      service_name: opt ? opt.dataset.name : '',
      date:         form.date.value,
      time:         form.time.value,
      client_name:  form.client_name.value,
      client_email: form.client_email.value,
      client_phone: form.client_phone.value,
      notes:        form.notes.value,
      deposit:      form.deposit.value
    };
  }

  if (typeof paypal === 'undefined') {
    if (paypalFallback) paypalFallback.style.display = 'block';
  } else {
    paypal.Buttons({
      onClick: function(data, actions) {
        const form = document.getElementById('booking-form');

        if (!form.service_id.value ||
            !form.date.value ||
            !form.time.value ||
            !form.client_name.value ||
            !form.client_email.value ||
            !form.client_phone.value) {
          alert("Please fill out all required fields before paying the deposit.");
          return actions.reject();
        }

        if (parseFloat(form.deposit.value || "0") <= 0) {
          alert("Please select a service with a deposit.");
          return actions.reject();
        }

        collectBookingData();
        return actions.resolve();
      },

      createOrder: function(data, actions) {
        const amount = parseFloat(document.getElementById('deposit').value || "0").toFixed(2);
        return actions.order.create({
          purchase_units: [{
            amount: { value: amount },
            description: 'Deposit for ' + bookingData.service_name
          }]
        });
      },

      onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
          bookingData.paypalOrderId = data.orderID;

          // Save booking on the server
          fetch('save_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bookingData)
          })
          .then(res => res.json())
          .then(res => {
            if (res.success) {
              window.location.href = 'confirm.php?id=' + encodeURIComponent(res.id);
            } else {
              console.error(res);
              window.location.href = 'booking.php?error=1';
            }
          })
          .catch(err => {
            console.error(err);
            window.location.href = 'booking.php?error=1';
          });
        });
      }
    }).render('#paypal-button-container');
  }
});
</script>
</body>
</html>
