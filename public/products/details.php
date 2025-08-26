<?php
// public/products/details.php
require_once __DIR__ . '/../bootstrap.php';   // login obbligatorio + $BASE + $conn
require_once __DIR__ . '/../img_path.php';    // helper immagini

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: {$BASE}/public/products/list.php?err=notfound");
  exit;
}

$stmt = $conn->prepare(
  "SELECT id, title, description, price, currency, stock, image_filename, is_active
   FROM product
   WHERE id=? AND is_active=1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) {
  header("Location: {$BASE}/public/products/list.php?err=notfound");
  exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($prod['title']) ?></title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
    <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .product-page{display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start; margin-top:1rem;}
    .product-media img{max-width:100%; border-radius:12px; display:block; background:#f4f4f4;}
    .product-info h1{margin:.2rem 0 1rem;}
    .price{font-weight:600; font-size:1.25rem; margin:.5rem 0;}
    form{margin-top:1rem; display:flex; gap:.5rem; align-items:center;}
    form input[type=number]{width:80px; padding:6px 8px;}
    form button{padding:8px 14px}
    @media (max-width: 900px){ .product-page{grid-template-columns:1fr;} }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <div class="product-page">
    <div class="product-media">
      <img src="<?= htmlspecialchars(product_image_url($prod)) ?>" alt="img <?= (int)$prod['id'] ?>" />
    </div>
    <div class="product-info">
      <h1><?= htmlspecialchars($prod['title']) ?></h1>
      <div class="price">
        <?= number_format((float)$prod['price'], 2, ',', '.') . ' ' . htmlspecialchars($prod['currency'] ?? 'EUR') ?>
      </div>
      <div>
        <?= nl2br(htmlspecialchars($prod['description'] ?? '')) ?>
      </div>

      <?php if ((int)$prod['stock'] > 0): ?>
        <form method="post" action="<?= $BASE ?>/public/cart/add.php">
          <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
          <label>Qtà</label>
          <input type="number" name="qty" value="1" min="1" max="<?= (int)$prod['stock'] ?>">
          <button type="submit">Aggiungi al carrello</button>
        </form>
      <?php else: ?>
        <p><strong>Esaurito</strong></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
