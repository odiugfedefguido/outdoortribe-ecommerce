<?php
session_start();
require_once __DIR__ . '/../config_path.php';
//require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../img_path.php';

// Lista prodotti attivi
$sql = "SELECT p.id, p.title, p.price, p.currency, p.stock, p.image_filename
        FROM product p WHERE p.is_active=1 ORDER BY p.id DESC";
$res = $conn->query($sql);
$prods = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Catalogo prodotti</title>
  <link rel="stylesheet" href="/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <h1>Prodotti</h1>
  <?php if (!$prods): ?>
    <p>Nessun prodotto disponibile.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($prods as $p): ?>
        <a class="card" href="/public/products/details.php?id=<?= (int)$p['id'] ?>">
          <img src="<?= htmlspecialchars(product_image_url($p)) ?>" alt="img <?= (int)$p['id'] ?>" />
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
