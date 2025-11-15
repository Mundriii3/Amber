<?php
require_once __DIR__ . '/functions.php';

$products = load_products();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo e(SITE_NAME); ?> — Products</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="styles.css">
  <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=<?php echo PAYPAL_CURRENCY; ?>"></script>
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
    <h2>Products</h2>
    <p class="small">Hair care & styling essentials curated by Aesthetically A.</p>

    <?php if (!$products): ?>
      <p>No products added yet. Check back soon.</p>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $index => $item): ?>
          <div class="product-card">
            <?php if (!empty($item['image'])): ?>
              <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
            <?php endif; ?>

            <h3><?php echo e($item['name']); ?></h3>
            <?php if (!empty($item['description'])): ?>
              <p class="small"><?php echo nl2br(e($item['description'])); ?></p>
            <?php endif; ?>

            <p class="price">$<?php echo e(number_format($item['price'], 2)); ?></p>

            <div id="paypal-product-<?php echo $index; ?>"></div>

            <script>
            (function() {
              const price = "<?php echo number_format($item['price'], 2, '.', ''); ?>";
              const name  = "<?php echo e($item['name']); ?>";

              paypal.Buttons({
                createOrder: function(data, actions) {
                  return actions.order.create({
                    purchase_units: [{
                      amount: { value: price },
                      description: "Product: " + name
                    }]
                  });
                },
                onApprove: function(data, actions) {
                  return actions.order.capture().then(function(details) {
                    alert("Thank you for your purchase of " + name + "! Check your PayPal email for your receipt.");
                  });
                }
              }).render('#paypal-product-<?php echo $index; ?>');
            })();
            </script>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
