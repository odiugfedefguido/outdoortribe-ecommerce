<?php
/* 
 * File: public/products/details.php
 * Scopo: Dettaglio prodotto.
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
?>
<h2>Dettaglio prodotto</h2>
<!-- TODO: mostra info di un prodotto (GET id) e pulsante 'Aggiungi al carrello' -->

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
<?php
/* 
 * File: public/products/details.php
 * Scopo: Dettaglio prodotto + form aggiunta al carrello.
 * Stato: IMPLEMENTATO (minimo).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';

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
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/components/components.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/header/header.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<h2><?= htmlspecialchars($prod['title']) ?></h2>
<div style="display:flex;gap:24px;">
  <div style="flex:1;">
    <?php if (!empty($imgs)): ?>
      <img src="<?= htmlspecialchars($imgs[0]) ?>" alt="" style="width:100%;max-width:460px;">
      <div style="display:flex;gap:8px;margin-top:8px;">
        <?php foreach ($imgs as $u): ?>
          <img src="<?= htmlspecialchars($u) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border:1px solid #ddd;">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div style="flex:1;">
    <div style="font-size:20px;font-weight:bold;margin-bottom:8px;">
      <?= number_format($prod['price'],2,',','.') . ' ' . htmlspecialchars($prod['currency']) ?>
    </div>
    <p><?= nl2br(htmlspecialchars($prod['description'])) ?></p>
    <form action="/ecommerce_from_outdoortribe/public/cart/add.php" method="post" style="margin-top:16px;">
      <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
      <label>Quantità:
        <input type="number" name="qty" value="1" min="1" max="<?= max(1, (int)$prod['stock']) ?>">
      </label>
      <button type="submit" <?= $prod['stock']<=0 ? 'disabled':'' ?>>Aggiungi al carrello</button>
    </form>
  </div>
</div>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
