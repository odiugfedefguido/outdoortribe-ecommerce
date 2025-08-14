<?php
// public/products/list.php
require_once __DIR__ . '/../bootstrap.php';   // login obbligatorio + $BASE + $conn
require_once __DIR__ . '/../img_path.php';    // helper immagini

// Lista prodotti attivi
$sql = "SELECT p.id, p.title, p.price, p.currency, p.stock, p.image_filename
        FROM product p
        WHERE p.is_active=1
        ORDER BY p.id DESC";
$res = $conn->query($sql);
$prods = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Catalogo prodotti</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .grid {
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
    .card {
      display: block;
      text-decoration: none;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #fff;
      padding: 10px;
      color: inherit;
      transition: box-shadow .15s ease;
    }
    .card:hover { box-shadow: 0 6px 18px rgba(0,0,0,.06); }
    .card img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 6px;
      background: #f7f7f7;
    }
    .prod-title { font-weight: 600; margin-top: 8px; }
    .prod-price { font-weight: 700; margin-top: 4px; }
  </style>
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
        <a class="card" href="<?= $BASE ?>/public/products/details.php?id=<?= (int)$p['id'] ?>">
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
