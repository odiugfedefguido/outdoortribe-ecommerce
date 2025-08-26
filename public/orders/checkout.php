<?php
// public/orders/checkout.php
require_once __DIR__ . '/../bootstrap.php'; // sessione + $BASE + $conn

$userId = current_user_id();

// Carrello
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
$currency = 'EUR';
foreach ($items as $it) {
  $line = ((float)$it['price']) * (int)$it['qty'];
  $subtotal += $line;
  if (!empty($it['currency'])) $currency = $it['currency'];
}
$shipping = 0.0; // per ora
$vat = 0.0; // per ora
$grand = $subtotal + $shipping + $vat;
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
      <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="page">
  <h1>Checkout</h1>

  <?php if (empty($items)): ?>
    <p>Il carrello è vuoto.</p>
    <p><a href="<?= $BASE ?>/public/">Torna ai prodotti</a></p>
  <?php else: ?>
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
      <div>
        <form method="post" action="<?= $BASE ?>/public/orders/place_order.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

          <fieldset style="margin-bottom:1rem;">
            <legend>Indirizzo spedizione</legend>
            <label>Nome completo<br><input name="ship_name" required></label><br>
            <label>Indirizzo<br><input name="ship_addr" required></label><br>
            <label>Città<br><input name="ship_city" required></label><br>
            <label>CAP<br><input name="ship_zip" required></label><br>
          </fieldset>

          <fieldset style="margin-bottom:1rem;">
            <legend>Pagamento</legend>
            <select name="payment_method" id="pm">
              <option value="cod">Contanti alla consegna</option>
              <option value="card">Carta</option>
            </select>
            <div id="cardFields" style="display:none; margin-top:.5rem;">
              <label>Numero carta<br><input name="card_number" pattern="[0-9 ]{12,19}"></label><br>
              <label>Scadenza (MM/YY)<br><input name="card_exp" pattern="[0-9]{2}/[0-9]{2}"></label><br>
              <label>CVC<br><input name="card_cvc" pattern="[0-9]{3,4}"></label><br>
            </div>
          </fieldset>

          <button type="submit">Conferma ordine</button>
        </form>
      </div>
      <aside>
        <h3>Riepilogo</h3>
        <table class="cart-table">
          <thead><tr><th>Prodotto</th><th class="right">Qtà</th><th class="right">Prezzo</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['title']) ?></td>
              <td class="right"><?= (int)$it['qty'] ?></td>
              <td class="right"><?= number_format((float)$it['price']*(int)$it['qty'], 2, ',', '.') . ' ' . htmlspecialchars($it['currency'] ?? 'EUR') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td>Subtotale</td><td></td><td class="right"><?= number_format($subtotal,2,',','.') . ' ' . htmlspecialchars($currency) ?></td></tr>
            <tr><td>Spedizione</td><td></td><td class="right"><?= number_format($shipping,2,',','.') . ' ' . htmlspecialchars($currency) ?></td></tr>
            <tr><td>IVA</td><td></td><td class="right"><?= number_format($vat,2,',','.') . ' ' . htmlspecialchars($currency) ?></td></tr>
            <tr><td><strong>Totale</strong></td><td></td><td class="right"><strong><?= number_format($grand,2,',','.') . ' ' . htmlspecialchars($currency) ?></strong></td></tr>
          </tfoot>
        </table>
      </aside>
    </div>
  <?php endif; ?>
</section>

<script>
  const pm = document.getElementById('pm');
  const cardFields = document.getElementById('cardFields');
  function toggleCard(){ cardFields.style.display = pm.value === 'card' ? 'block' : 'none'; }
  pm.addEventListener('change', toggleCard); toggleCard();
</script>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
