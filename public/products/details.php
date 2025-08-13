<?php
session_start();
require_once __DIR__ . '/../config_path.php';
//require_once __DIR__ . '/../auth_guard.php';              // ← riattiva se vuoi l’accesso solo loggati
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../img_path.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Prodotto non trovato'); }

$stmt = $conn->prepare(
  "SELECT id, title, description, price, currency, stock, image_filename, is_active
   FROM product WHERE id=? AND is_active=1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$prod) { http_response_code(404); exit('Prodotto non trovato'); }
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($prod['title']) ?></title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .product-page{display:grid;gap:16px;grid-template-columns:1fr}
    @media(min-width:900px){.product-page{grid-template-columns:1fr 1fr}}
    .product-media img{width:100%;height:auto;max-height:480px;object-fit:contain;background:#f7f7f7;border-radius:10px}
    .price{font-size:1.25rem;font-weight:700;margin:8px 0}
    .stock{color:#555;margin-bottom:8px}
    form button{padding:8px 14px}
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
      <div class="stock">Disponibilità: <?= (int)$prod['stock'] ?></div>
      <p class="desc"><?= nl2br(htmlspecialchars($prod['description'] ?? '')) ?></p>

      <form method="post" action="<?= $BASE ?>/public/cart/add.php">
        <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
        <label>Quantità:
          <input type="number" name="qty" min="1" max="<?= max(1,(int)$prod['stock']) ?>" value="1" required>
        </label>
        <button type="submit">Aggiungi al carrello</button>
      </form>

      <p style="margin-top:12px;">
        <a href="<?= $BASE ?>/public/products/list.php">← Torna al catalogo</a>
      </p>
    </div>
  </div>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
