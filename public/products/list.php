<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../img_path.php';

/* Tutti i prodotti attivi e con stock > 0, nessuna ricerca qui */
$sql = "SELECT p.id, p.title, p.price, p.currency, p.stock, p.image_filename
        FROM product p
        WHERE p.is_active=1 AND p.stock > 0
        ORDER BY p.id DESC";
$res   = $conn->query($sql);
$prods = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/mountain.svg">

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catalogo prodotti</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/catalog.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="page">
  <h1>Catalogo</h1>

  <?php if (empty($prods)): ?>
    <p>Nessun prodotto disponibile.</p>
  <?php else: ?>
    <div class="grid-prod">
      <?php foreach ($prods as $p): ?>
        <a class="prod-card" href="<?= $BASE ?>/public/products/details.php?id=<?= (int)$p['id'] ?>">
          <img src="<?= htmlspecialchars(product_image_url($p)) ?>" alt="img <?= (int)$p['id'] ?>">
          <div class="prod-title"><?= htmlspecialchars($p['title']) ?></div>
          <div class="prod-price">
            <?= number_format((float)$p['price'], 2, ',', '.') . ' ' . htmlspecialchars($p['currency'] ?? 'EUR') ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
