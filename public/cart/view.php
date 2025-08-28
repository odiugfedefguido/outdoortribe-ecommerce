<?php
// public/cart/view.php
require_once __DIR__ . '/../bootstrap.php';  // login obbligatorio + $BASE + $conn

$userId = current_user_id();

// 1) PULIZIA: rimuovi dal MIO carrello prodotti non più acquistabili
$stmt = $conn->prepare(
  "DELETE ci FROM cart_item ci
   JOIN product p ON p.id = ci.product_id
   WHERE ci.user_id=? AND (p.is_active<>1 OR p.stock<=0)"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();

// 2) Carrello “pulito”: mostra solo prodotti attivi e con stock > 0
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
foreach ($items as $it) {
  $subtotal += (float)$it['price'] * (int)$it['qty'];
}
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
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .qty-input { width: 80px; }
    .actions { display: flex; gap: .5rem; align-items: center; }
    .totale { text-align: right; font-weight: bold; }
    .right { text-align: right; }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="container">
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
          <th style="width:170px;">Quantità</th>
          <th style="width:120px;">Totale</th>
          <th style="width:140px;">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it):
          $line = (float)$it['price'] * (int)$it['qty'];
        ?>
        <tr>
          <td><?= htmlspecialchars($it['title']) ?></td>
          <td class="right">
            <?= number_format((float)$it['price'], 2, ',', '.') ?>
            <?= htmlspecialchars($it['currency'] ?? 'EUR') ?>
          </td>
          <td class="right"><?= (int)$it['stock'] ?></td>
          <td>
            <!-- Form aggiornamento quantità -->
            <form method="post" action="<?= $BASE ?>/public/cart/update.php" class="actions">
              <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <input class="qty-input" type="number" name="qty"
                     min="1" max="<?= (int)$it['stock'] ?>" value="<?= (int)$it['qty'] ?>">
              <button type="submit">Aggiorna</button>
            </form>
          </td>
          <td class="right">
            <?= number_format($line, 2, ',', '.') ?>
            <?= htmlspecialchars($it['currency'] ?? 'EUR') ?>
          </td>
          <td>
            <!-- Form rimozione riga -->
            <form method="post" action="<?= $BASE ?>/public/cart/remove.php">
              <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <button type="submit">Rimuovi</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="totale">Subtotale</td>
          <td class="totale">
            <?= number_format($subtotal, 2, ',', '.') ?> EUR
          </td>
          <td></td>
        </tr>
      </tfoot>
    </table>

    <!-- Pulsante per procedere al checkout -->
    <div class="right" style="margin-top:1rem;">
      <form method="post" action="<?= $BASE ?>/public/orders/checkout.php" style="display:inline;">
        <button type="submit">Procedi al checkout</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
