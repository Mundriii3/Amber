<?php
// index.php
require_once __DIR__ . '/functions.php';

$bio     = load_bio();
$gallery = load_gallery();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo e(SITE_NAME); ?> — Home</title>
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

  <!-- HERO IMAGE RIGHT UNDER NAV -->
  <?php if (!empty($bio['hero_image'])): ?>
    <section class="card hero-card">
      <img src="<?php echo e($bio['hero_image']); ?>" alt="Aesthetically A" class="hero-image">
    </section>
  <?php endif; ?>

  <!-- BIO + LOCATION -->
  <section class="card">
    <h2>About <?php echo e($bio['name']); ?></h2>
    <?php if (!empty($bio['location'])): ?>
      <p><strong>Based in:</strong> <?php echo e($bio['location']); ?></p>
    <?php endif; ?>
    <?php if (!empty($bio['bio'])): ?>
      <p><?php echo nl2br(e($bio['bio'])); ?></p>
    <?php else: ?>
      <p class="small">Add your bio in the Admin area to show it here.</p>
    <?php endif; ?>
  </section>

  <!-- GALLERY -->
  <section class="card">
    <h2>Portfolio — Photos & Videos</h2>
    <?php if ($gallery): ?>
      <div class="gallery-grid">
        <?php foreach ($gallery as $g): ?>
          <div class="gallery-item">
            <img src="<?php echo e($g['image']); ?>" alt="">
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="small">No portfolio items yet. Upload photos in the Admin area.</p>
    <?php endif; ?>
  </section>

</main>

<!-- Tiny debug marker so you know you’re editing the right copy on the server -->
<p style="position:fixed;bottom:5px;right:10px;font-size:10px;color:#ff00c8;z-index:9999;">
  Build: <?php echo date('Y-m-d H:i:s'); ?>
</p>

</body>
</html>
