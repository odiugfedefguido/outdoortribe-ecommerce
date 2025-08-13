<?php
session_start();
//require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../config_path.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// carrello con join prodotti (prezzo corrente a vista; al checkout fisserai il prezzo)
$sql = "SELECT ci.product_id, ci.qty, p.title, p.price, p.currency, p.stock
        FROM cart_item ci
        JOIN product p ON p.id = ci.product_id
        WHERE ci.user_id = ?
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
  <meta charset="utf-8">
  <title>Carrello</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
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
            <!-- Form aggiornamento quantità (no annidamento) -->
            <form method="post" action="<?= $BASE ?>/public/cart/update.php" class="actions">
              <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <input class="qty-input" type="number" name="qty"
                     min="0" max="<?= (int)$it['stock'] ?>" value="<?= (int)$it['qty'] ?>">
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

    <!-- Form dedicato per procedere al checkout -->
    <p class="right" style="margin-top:1rem;">
      <form method="post" action="<?= $BASE ?>/public/orders/checkout.php" style="display:inline;">
        <button type="submit">Procedi al checkout</button>
      </form>
    </p>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
