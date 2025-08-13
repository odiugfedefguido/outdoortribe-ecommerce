<?php
// public/orders/checkout.php
session_start();
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Prendo gli articoli nel carrello con prezzo corrente
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

if (!$items) {
  header("Location: /public/cart/view.php?err=empty");
  exit;
}

$subtotal = 0.0;
foreach ($items as $it) {
  $subtotal += (float)$it['price'] * (int)$it['qty'];
}
$currency = $items[0]['currency'] ?? 'EUR';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Checkout</title>
  <link rel="stylesheet" href="/public/styles/main.css">
  <style>
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #eee}
    .totale{text-align:right;font-weight:bold}
    .actions{display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem}
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <h1>Conferma ordine</h1>

  <table>
    <thead>
      <tr>
        <th>Prodotto</th>
        <th style="width:120px;">Prezzo</th>
        <th style="width:120px;">Q.tà</th>
        <th style="width:140px;">Totale riga</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): 
        $line = (float)$it['price'] * (int)$it['qty'];
      ?>
        <tr>
          <td><?= htmlspecialchars($it['title']) ?></td>
          <td><?= number_format((float)$it['price'], 2, ',', '.') ?> <?= htmlspecialchars($it['currency'] ?? 'EUR') ?></td>
          <td><?= (int)$it['qty'] ?></td>
          <td><?= number_format($line, 2, ',', '.') ?> <?= htmlspecialchars($it['currency'] ?? 'EUR') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" class="totale">Subtotale</td>
        <td class="totale"><?= number_format($subtotal, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="actions">
    <a href="/public/cart/view.php">Torna al carrello</a>
    <form method="post" action="/public/orders/place_order.php">
      <button type="submit">Conferma e invia ordine</button>
    </form>
  </div>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
