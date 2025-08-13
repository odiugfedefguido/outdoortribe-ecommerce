<?php
/*
 * File: public/products/details.php
 * Scopo: Dettaglio prodotto + form aggiunta al carrello.
 * Stato: IMPLEMENTATO.
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../../admin/functions.php';
require_once __DIR__ . '/../config_path.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Prodotto non trovato'); }

$stmt = $conn->prepare("
  SELECT p.id, p.title, p.description, p.price, p.currency, p.stock
  FROM product p WHERE p.id=? AND p.is_active=1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$prod) { http_response_code(404); exit('Prodotto non trovato'); }

$imgs = [];
$res = $conn->prepare("SELECT url FROM product_image WHERE product_id=? ORDER BY sort_order ASC, id ASC");
$res->bind_param('i', $id);
$res->execute();
$r = $res->get_result();
while ($row = $r->fetch_assoc()) $imgs[] = $row['url'];
$res->close();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"/>
  <title><?= htmlspecialchars($prod['title']) ?></title>
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .p-wrap{display:flex;gap:24px;flex-wrap:wrap}
    .p-left{flex:1;min-width:280px}
    .p-right{flex:1;min-width:280px}
    .thumbs{display:flex;gap:8px;margin-top:8px}
    .thumbs img{width:80px;height:80px;object-fit:cover;border:1px solid #ddd}
    .price{font-size:20px;font-weight:700;margin:8px 0}
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<h2><?= htmlspecialchars($prod['title']) ?></h2>
<div class="p-wrap">
  <div class="p-left">
    <?php if (!empty($imgs)): ?>
      <img src="<?= htmlspecialchars($imgs[0]) ?>" alt="" style="width:100%;max-width:460px;border:1px solid #eee;">
      <?php if (count($imgs) > 1): ?>
        <div class="thumbs">
          <?php foreach ($imgs as $u): ?>
            <img src="<?= htmlspecialchars($u) ?>" alt="">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <img src="<?= $BASE ?>/public/images/placeholder-product.png" alt="" style="width:100%;max-width:460px;border:1px solid #eee;">
    <?php endif; ?>
  </div>

  <div class="p-right">
    <div class="price">
      <?= number_format((float)$prod['price'],2,',','.') . ' ' . htmlspecialchars($prod['currency'] ?? 'EUR') ?>
    </div>
    <p><?= nl2br(htmlspecialchars($prod['description'] ?? '')) ?></p>

    <form action="<?= $BASE ?>/public/cart/add.php" method="post" style="margin-top:16px;">
      <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
      <label>Quantità:
        <input type="number" name="qty" value="1" min="1" max="<?= max(1,(int)$prod['stock']) ?>">
      </label>
      <button type="submit" <?= $prod['stock']<=0 ? 'disabled':'' ?>>Aggiungi al carrello</button>
    </form>
  </div>
</div>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
