<?php
/* 
 * File: public/cart/view.php
 * Scopo: Visualizza carrello dell'utente con righe e totale.
 * Stato: IMPLEMENTATO (minimo).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /ecommerce_from_outdoortribe/public/auth/login.php');
  exit;
}
$userId = (int)$_SESSION['user_id'];

// Carrello + righe
$stmt = $conn->prepare("
  SELECT c.id as cart_id
  FROM cart c WHERE c.user_id=?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();
$stmt->close();

$items = [];
$total = 0.0;
$currency = 'EUR';

if ($cart) {
  $cartId = (int)$cart['cart_id'];
  $stmt = $conn->prepare("
    SELECT ci.id, ci.product_id, ci.qty, ci.unit_price, p.title,
           (SELECT url FROM product_image pi WHERE pi.product_id=p.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS img
    FROM cart_item ci
    JOIN product p ON p.id=ci.product_id
    WHERE ci.cart_id=?
    ORDER BY ci.id DESC
  ");
  $stmt->bind_param('i', $cartId);
  $stmt->execute();
  $rs = $stmt->get_result();
  while ($row = $rs->fetch_assoc()) {
    $row['line_total'] = $row['qty'] * $row['unit_price'];
    $items[] = $row;
    $total += $row['line_total'];
  }
  $stmt->close();
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"/>
  <title>Il tuo carrello</title>
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/components/components.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/header/header.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<h2>Carrello</h2>

<?php if (empty($items)): ?>
  <p>Il carrello è vuoto.</p>
<?php else: ?>
  <table border="1" cellpadding="8" cellspacing="0">
    <tr>
      <th>Prodotto</th><th>Prezzo</th><th>Q.tà</th><th>Totale riga</th><th></th>
    </tr>
    <?php foreach ($items as $it): ?>
      <tr>
        <td>
          <?php if ($it['img']): ?>
            <img src="<?= htmlspecialchars($it['img']) ?>" style="width:60px;height:60px;object-fit:cover;vertical-align:middle;">
          <?php endif; ?>
          <?= htmlspecialchars($it['title']) ?>
        </td>
        <td><?= number_format($it['unit_price'],2,',','.') . ' ' . $currency ?></td>
        <td>
          <form method="post" action="/ecommerce_from_outdoortribe/public/cart/update.php" style="display:inline;">
            <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
            <input type="number" name="qty" value="<?= $it['qty'] ?>" min="1" style="width:70px;">
            <button type="submit">Aggiorna</button>
          </form>
        </td>
        <td><?= number_format($it['line_total'],2,',','.') . ' ' . $currency ?></td>
        <td>
          <form method="post" action="/ecommerce_from_outdoortribe/public/cart/remove.php" style="display:inline;">
            <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
            <button type="submit">Rimuovi</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <tr>
      <td colspan="3" style="text-align:right;font-weight:bold;">Totale:</td>
      <td colspan="2" style="font-weight:bold;"><?= number_format($total,2,',','.') . ' ' . $currency ?></td>
    </tr>
  </table>

  <p style="margin-top:16px;">
    <a href="/ecommerce_from_outdoortribe/public/orders/checkout.php">Procedi al checkout</a>
  </p>
<?php endif; ?>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
