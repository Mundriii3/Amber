<?php
// admin.php

// Store sessions inside project so XAMPP has permission
$sessionDir = __DIR__ . '/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0755, true);
}
session_save_path($sessionDir);

session_start();
require_once __DIR__ . '/functions.php';

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$message  = '';

// ---------- LOGIN ----------
if (isset($_POST['password']) && !$loggedIn) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $loggedIn = true;
    } else {
        $message = 'Incorrect password.';
    }
}

if (!$loggedIn):
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login — <?php echo e(SITE_NAME); ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="site-header">
  <h1><?php echo e(SITE_NAME); ?> Admin</h1>
</header>

<main class="wrap">
  <section class="card">
    <h2>Login</h2>
    <?php if ($message): ?>
      <div class="notice error"><?php echo e($message); ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="field">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
      </div>
      <button type="submit">Log in</button>
    </form>
  </section>
</main>
</body>
</html>
<?php
exit;
endif;

// ---------- LOAD CURRENT DATA ----------
$bio          = load_bio();
$gallery      = load_gallery();
$services     = load_services();
$apparel      = load_apparel();
$products     = load_products();
$availability = load_availability();
$bookings     = load_bookings();

// ---------- HANDLE POST ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save Bio + Location + Hero Image
    // Save Bio + Location + Hero Image
if (isset($_POST['action']) && $_POST['action'] === 'save_bio') {
    $bio['name']     = $_POST['name']     ?? $bio['name'];
    $bio['bio']      = $_POST['bio']      ?? $bio['bio'];
    $bio['location'] = $_POST['location'] ?? $bio['location'];

    if (!empty($_FILES['hero_image']['name'])) {
        $fileName = time() . '_' . basename($_FILES['hero_image']['name']);
        $target   = UPLOAD_DIR . '/' . $fileName;
        $tmp      = $_FILES['hero_image']['tmp_name'];

        // Hero only → 1280x720; if resize fails, fall back to normal upload
        if (resize_to_1280x720($tmp, $target) || move_uploaded_file($tmp, $target)) {
            $bio['hero_image'] = 'uploads/' . $fileName;
        }
    }

    save_bio($bio);
    $message = 'Bio updated.';
}

    // Add gallery image
    if (isset($_POST['action']) && $_POST['action'] === 'add_gallery') {
        if (!empty($_FILES['gallery_image']['name'])) {
            $fileName = time() . '_' . basename($_FILES['gallery_image']['name']);
            $target   = UPLOAD_DIR . '/' . $fileName;
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target)) {
                add_gallery_item('uploads/' . $fileName);
                $gallery = load_gallery();
                $message = 'Gallery image added.';
            }
        }
    }

    // Delete gallery image
    if (isset($_POST['delete_gallery'])) {
        $idx = (int)$_POST['delete_gallery'];
        if (isset($gallery[$idx])) {
            $imgPath = __DIR__ . '/' . $gallery[$idx]['image'];
            if (is_file($imgPath)) {
                @unlink($imgPath);
            }
            array_splice($gallery, $idx, 1);
            save_json('gallery.json', $gallery);
            $message = 'Gallery image deleted.';
        }
    }

    // Save availability (schedule)
    if (isset($_POST['action']) && $_POST['action'] === 'save_availability') {
        for ($i = 0; $i <= 6; $i++) {
            $availability[$i]['open']  = isset($_POST["open_$i"]);
            $availability[$i]['start'] = $_POST["start_$i"] ?? $availability[$i]['start'];
            $availability[$i]['end']   = $_POST["end_$i"]   ?? $availability[$i]['end'];
        }
        save_availability($availability);
        $message = 'Schedule updated.';
    }

    // Add service
    if (isset($_POST['action']) && $_POST['action'] === 'add_service') {
        $services[] = [
            'name'     => $_POST['service_name']    ?? '',
            'price'    => (float)($_POST['service_price']   ?? 0),
            'deposit'  => (float)($_POST['service_deposit'] ?? 0),
            'duration' => $_POST['service_duration'] ?? ''
        ];
        save_services($services);
        $message = 'Service added.';
    }

    // Delete service
    if (isset($_POST['delete_service'])) {
        $idx = (int)$_POST['delete_service'];
        if (isset($services[$idx])) {
            array_splice($services, $idx, 1);
            save_services($services);
            $message = 'Service deleted.';
        }
    }

    // Add apparel
    if (isset($_POST['action']) && $_POST['action'] === 'add_apparel') {
        $imagePath = '';
        if (!empty($_FILES['apparel_image']['name'])) {
            $fileName = time() . '_' . basename($_FILES['apparel_image']['name']);
            $target   = UPLOAD_DIR . '/' . $fileName;
            if (move_uploaded_file($_FILES['apparel_image']['tmp_name'], $target)) {
                $imagePath = 'uploads/' . $fileName;
            }
        }
        $apparel[] = [
            'name'        => $_POST['apparel_name']        ?? '',
            'description' => $_POST['apparel_description'] ?? '',
            'price'       => (float)($_POST['apparel_price'] ?? 0),
            'image'       => $imagePath
        ];
        save_apparel($apparel);
        $message = 'Apparel item added.';
    }

    // Delete apparel
    if (isset($_POST['delete_apparel'])) {
        $idx = (int)$_POST['delete_apparel'];
        if (isset($apparel[$idx])) {
            $imgPath = !empty($apparel[$idx]['image']) ? __DIR__ . '/' . $apparel[$idx]['image'] : null;
            if ($imgPath && is_file($imgPath)) {
                @unlink($imgPath);
            }
            array_splice($apparel, $idx, 1);
            save_apparel($apparel);
            $message = 'Apparel item deleted.';
        }
    }

    // Add product
    if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
        $imagePath = '';
        if (!empty($_FILES['product_image']['name'])) {
            $fileName = time() . '_' . basename($_FILES['product_image']['name']);
            $target   = UPLOAD_DIR . '/' . $fileName;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target)) {
                $imagePath = 'uploads/' . $fileName;
            }
        }
        $products[] = [
            'name'        => $_POST['product_name']        ?? '',
            'description' => $_POST['product_description'] ?? '',
            'price'       => (float)($_POST['product_price'] ?? 0),
            'image'       => $imagePath
        ];
        save_products($products);
        $message = 'Product added.';
    }

    // Delete product
    if (isset($_POST['delete_product'])) {
        $idx = (int)$_POST['delete_product'];
        if (isset($products[$idx])) {
            $imgPath = !empty($products[$idx]['image']) ? __DIR__ . '/' . $products[$idx]['image'] : null;
            if ($imgPath && is_file($imgPath)) {
                @unlink($imgPath);
            }
            array_splice($products, $idx, 1);
            save_products($products);
            $message = 'Product deleted.';
        }
    }

    // Delete booking (client cancellation)
    if (isset($_POST['delete_booking'])) {
        $idx = (int)$_POST['delete_booking'];
        if (isset($bookings[$idx])) {
            array_splice($bookings, $idx, 1);
            save_bookings($bookings);
            $message = 'Booking removed (client cancelled).';
        }
    }

    // Reload fresh data
    $bio          = load_bio();
    $gallery      = load_gallery();
    $services     = load_services();
    $apparel      = load_apparel();
    $products     = load_products();
    $availability = load_availability();
    $bookings     = load_bookings();
}

// ---------- SORT BOOKINGS BY DATE/TIME ----------
$sortedBookings = $bookings;
usort($sortedBookings, function($a, $b) {
    $ad = ($a['date'] ?? '') . ' ' . ($a['time'] ?? '');
    $bd = ($b['date'] ?? '') . ' ' . ($b['time'] ?? '');
    return strcmp($ad, $bd);
});
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — <?php echo e(SITE_NAME); ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="site-header">
  <h1><?php echo e(SITE_NAME); ?> Admin</h1>
  <nav>
    <a href="index.php">View Site</a>
    <a href="booking.php">Booking Page</a>
  </nav>
</header>

<main class="wrap">
  <?php if ($message): ?>
    <div class="notice success"><?php echo e($message); ?></div>
  <?php endif; ?>

  <!-- BIO / LOCATION / HERO -->
  <section class="card">
    <h2>Home — Bio & Location</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_bio">
      <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="<?php echo e($bio['name']); ?>">
      </div>
      <div class="field">
        <label>Bio</label>
        <textarea name="bio" rows="4"><?php echo e($bio['bio']); ?></textarea>
      </div>
      <div class="field">
        <label>Location</label>
        <input type="text" name="location" value="<?php echo e($bio['location']); ?>">
      </div>
      <div class="field">
        <label>Hero Image</label>
        <input type="file" name="hero_image" accept="image/*">
        <?php if (!empty($bio['hero_image'])): ?>
          <p class="small">Current: <?php echo e($bio['hero_image']); ?></p>
        <?php endif; ?>
      </div>
      <button type="submit">Save</button>
    </form>
  </section>

  <!-- GALLERY -->
  <section class="card">
    <h2>Portfolio Gallery</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_gallery">
      <div class="field">
        <label>Add Image</label>
        <input type="file" name="gallery_image" accept="image/*">
      </div>
      <button type="submit">Upload</button>
    </form>

    <?php if ($gallery): ?>
      <p class="small" style="margin-top:1rem;">Existing images:</p>
      <div class="gallery-grid">
        <?php foreach ($gallery as $idx => $g): ?>
          <div>
            <img src="<?php echo e($g['image']); ?>" alt="">
            <form method="post" onsubmit="return confirm('Delete this image?');">
              <input type="hidden" name="delete_gallery" value="<?php echo $idx; ?>">
              <button type="submit" class="danger-btn">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- SCHEDULE / AVAILABILITY -->
  <section class="card">
    <h2>Schedule & Availability</h2>
    <p class="small">
      Set which days you work and your open / close times.  
      The booking calendar shows 2-hour blocks inside these hours and blocks out existing bookings.
    </p>
    <form method="post">
      <input type="hidden" name="action" value="save_availability">

      <table class="small" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;">Day</th>
            <th>Open?</th>
            <th>Start Time</th>
            <th>End Time</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($availability as $i => $day): ?>
            <tr>
              <td><?php echo e($day['label']); ?></td>
              <td style="text-align:center;">
                <input type="checkbox" name="open_<?php echo $i; ?>" <?php if ($day['open']) echo 'checked'; ?>>
              </td>
              <td>
                <input type="time" name="start_<?php echo $i; ?>" value="<?php echo e($day['start']); ?>">
              </td>
              <td>
                <input type="time" name="end_<?php echo $i; ?>" value="<?php echo e($day['end']); ?>">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <button type="submit" style="margin-top:1rem;">Save Schedule</button>
    </form>
  </section>

  <!-- UPCOMING BOOKINGS / CLIENTS -->
  <section class="card">
    <h2>Bookings / Clients</h2>
    <?php if (!$sortedBookings): ?>
      <p class="small">No bookings yet.</p>
    <?php else: ?>
      <p class="small">
        These are saved bookings from the site.  
        Deleting a booking removes it from the calendar and your internal list (use this for cancellations).
      </p>
      <table class="small" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;">Date</th>
            <th style="text-align:left;">Time</th>
            <th style="text-align:left;">Client</th>
            <th style="text-align:left;">Service</th>
            <th style="text-align:left;">Contact</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($sortedBookings as $idx => $b): ?>
          <tr>
            <td><?php echo e($b['date'] ?? ''); ?></td>
            <td><?php echo e($b['time'] ?? ''); ?></td>
            <td><?php echo e($b['client_name'] ?? ''); ?></td>
            <td><?php echo e($b['service_name'] ?? ''); ?></td>
            <td>
              <?php if (!empty($b['client_email'])): ?>
                <?php echo e($b['client_email']); ?><br>
              <?php endif; ?>
              <?php if (!empty($b['client_phone'])): ?>
                <?php echo e($b['client_phone']); ?>
              <?php endif; ?>
            </td>
            <td style="text-align:right;">
              <form method="post" onsubmit="return confirm('Remove this booking (cancel client)?');">
                <input type="hidden" name="delete_booking" value="<?php echo array_search($b, $bookings, true); ?>">
                <button type="submit" class="danger-btn">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <!-- SERVICES -->
  <section class="card">
    <h2>Services (Booking)</h2>
    <form method="post">
      <input type="hidden" name="action" value="add_service">
      <div class="field">
        <label>Service Name</label>
        <input type="text" name="service_name" required>
      </div>
      <div class="field">
        <label>Price (full price)</label>
        <input type="number" step="0.01" name="service_price" required>
      </div>
      <div class="field">
        <label>Deposit Amount</label>
        <input type="number" step="0.01" name="service_deposit" required>
      </div>
      <div class="field">
        <label>Duration (optional, e.g. "2h")</label>
        <input type="text" name="service_duration">
      </div>
      <button type="submit">Add Service</button>
    </form>

    <?php if ($services): ?>
      <ul class="small" style="margin-top:1rem;list-style:none;padding-left:0;">
        <?php foreach ($services as $idx => $s): ?>
          <li style="margin-bottom:0.35rem;">
            <strong><?php echo e($s['name']); ?></strong>
            — $<?php echo e(number_format($s['price'], 2)); ?>
            (Deposit $<?php echo e(number_format($s['deposit'], 2)); ?>)
            <?php if (!empty($s['duration'])): ?>
              — <span><?php echo e($s['duration']); ?></span>
            <?php endif; ?>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this service?');">
              <input type="hidden" name="delete_service" value="<?php echo $idx; ?>">
              <button type="submit" class="danger-btn">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- APPAREL -->
  <section class="card">
    <h2>Apparel</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_apparel">
      <div class="field">
        <label>Item Name</label>
        <input type="text" name="apparel_name" required>
      </div>
      <div class="field">
        <label>Description</label>
        <textarea name="apparel_description" rows="3"></textarea>
      </div>
      <div class="field">
        <label>Price</label>
        <input type="number" step="0.01" name="apparel_price" required>
      </div>
      <div class="field">
        <label>Image</label>
        <input type="file" name="apparel_image" accept="image/*">
      </div>
      <button type="submit">Add Apparel Item</button>
    </form>

    <?php if ($apparel): ?>
      <ul class="small" style="margin-top:1rem;list-style:none;padding-left:0;">
        <?php foreach ($apparel as $idx => $a): ?>
          <li style="margin-bottom:0.35rem;">
            <strong><?php echo e($a['name']); ?></strong>
            — $<?php echo e(number_format($a['price'], 2)); ?>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this item?');">
              <input type="hidden" name="delete_apparel" value="<?php echo $idx; ?>">
              <button type="submit" class="danger-btn">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- PRODUCTS -->
  <section class="card">
    <h2>Products</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_product">
      <div class="field">
        <label>Product Name</label>
        <input type="text" name="product_name" required>
      </div>
      <div class="field">
        <label>Description</label>
        <textarea name="product_description" rows="3"></textarea>
      </div>
      <div class="field">
        <label>Price</label>
        <input type="number" step="0.01" name="product_price" required>
      </div>
      <div class="field">
        <label>Image</label>
        <input type="file" name="product_image" accept="image/*">
      </div>
      <button type="submit">Add Product</button>
    </form>

    <?php if ($products): ?>
      <ul class="small" style="margin-top:1rem;list-style:none;padding-left:0;">
        <?php foreach ($products as $idx => $p): ?>
          <li style="margin-bottom:0.35rem;">
            <strong><?php echo e($p['name']); ?></strong>
            — $<?php echo e(number_format($p['price'], 2)); ?>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this product?');">
              <input type="hidden" name="delete_product" value="<?php echo $idx; ?>">
              <button type="submit" class="danger-btn">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</main>
</body>
</html>

