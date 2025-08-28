<?php
// public/cart/view.php
require_once __DIR__ . '/../bootstrap.php';  // login obbligatorio + $BASE + $conn

$userId = current_user_id();

$stmt = $conn->prepare(
  "DELETE ci FROM cart_item ci
   JOIN product p ON p.id = ci.product_id
   WHERE ci.user_id=? AND (p.is_active<>1 OR p.stock<=0)"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();

$sql = "SELECT ci.product_id, ci.qty, p.title, p.price, p.currency, p.stock
        FROM cart_item ci
        JOIN product p ON p.id = ci.product_id
        WHERE ci.user_id = ? AND p.is_active=1 AND p.stock > 0
        ORDER BY ci.product_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$subtotal = 0.0;
foreach ($items as $it) { $subtotal += (float)$it['price'] * (int)$it['qty']; }
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/mountain.svg">
  <meta charset="utf-8">
  <title>Carrello</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .cart-page table { width: 100%; border-collapse: collapse; }
    .cart-page th, .cart-page td { padding: 10px 12px; border-bottom: 1px solid #edf1ef; vertical-align: middle; }
    .cart-page thead th { background:#f6f8f7; color:#22332d; font-weight:700; }
    .cart-page .qty-input { width: 90px; height:42px; padding:0 10px; border:1px solid #cfd8d3; border-radius:10px; }
    .cart-page .actions { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
    .cart-page .totale { text-align: right; font-weight: bold; }
    .cart-page .right { text-align: right; }
    .btn{appearance:none;border:0;border-radius:12px;padding:10px 14px;font-weight:800;line-height:1;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 1px 0 rgba(0,0,0,.04),0 6px 16px rgba(0,0,0,.06);transition:transform .05s ease,filter .15s ease;user-select:none;text-decoration:none;min-width:140px;}
    .btn:active{transform:translateY(1px);} .btn:focus-visible{outline:3px solid #99c2ff;outline-offset:2px;}
    .btn-primary{background:#0b4ea9;color:#fff;} .btn-primary:hover{filter:brightness(.96);}
    .btn-danger{background:#a10a0a;color:#fff;} .btn-danger:hover{filter:brightness(.96);}
    .btn-ghost{background:#fff;color:#1b2a24;border:1px solid rgba(0,0,0,.12);} .btn-ghost:hover{filter:brightness(.98);}
    .btn[disabled]{opacity:.55;cursor:not-allowed;box-shadow:none;}
    .cart-page .checkout-bar{ display:flex; justify-content:flex-end; margin-top:1rem; }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="container cart-page">
  <h1>Il tuo carrello</h1>

  <?php if (!$items): ?>
    <p>Il carrello è vuoto. <a href="<?= $BASE ?>/public/products/list.php">Torna al catalogo</a></p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Prodotto</th>
          <th style="width:120px;">Prezzo</th>
          <th style="width:120px;">Disp.</th>
          <th style="width:240px;">Quantità</th>
          <th style="width:120px;">Totale</th>
          <th style="width:320px;">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): $line = (float)$it['price'] * (int)$it['qty']; ?>
        <tr>
          <td><?= htmlspecialchars($it['title']) ?></td>
          <td class="right"><?= number_format((float)$it['price'], 2, ',', '.') ?> <?= htmlspecialchars($it['currency'] ?? 'EUR') ?></td>
          <td class="right"><?= (int)$it['stock'] ?></td>
          <td>
            <form method="post" action="<?= $BASE ?>/public/cart/update.php" class="actions">
              <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <input class="qty-input" type="number" name="qty" min="1" max="<?= (int)$it['stock'] ?>" value="<?= (int)$it['qty'] ?>">
              <button type="submit" class="btn btn-ghost">Aggiorna</button>
            </form>
          </td>
          <td class="right"><?= number_format($line, 2, ',', '.') ?> <?= htmlspecialchars($it['currency'] ?? 'EUR') ?></td>
          <td>
            <div class="actions">
              <form method="post" action="<?= $BASE ?>/public/orders/checkout.php">
                <input type="hidden" name="only_product_id" value="<?= (int)$it['product_id'] ?>">
                <button type="submit" class="btn btn-primary">Compra solo questo</button>
              </form>
              <form method="post" action="<?= $BASE ?>/public/cart/remove.php">
                <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
                <button type="submit" class="btn btn-danger">Rimuovi</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="totale">Subtotale</td>
          <td class="totale"><?= number_format($subtotal, 2, ',', '.') ?> EUR</td>
          <td></td>
        </tr>
      </tfoot>
    </table>

    <div class="checkout-bar">
      <form method="post" action="<?= $BASE ?>/public/orders/checkout.php" style="display:inline;">
        <button type="submit" class="btn btn-primary">Procedi al checkout (tutto)</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
