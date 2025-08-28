<?php
// public/orders/checkout.php
require_once __DIR__ . '/../bootstrap.php'; // sessione + $BASE + $conn

$userId = current_user_id();

// CSRF (se non esiste)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
$shipping = 0.0; $vat = 0.0; $grand = $subtotal + $shipping + $vat;
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/mountain.svg">

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout</title>

  <!-- stessi CSS delle altre pagine -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">

  <!-- Stile locale per layout e form -->
  <style>
    section.page{ margin:70px auto 24px; max-width:1280px; padding:0 16px; }

    .checkout-grid{
      display:grid;
      grid-template-columns:2fr 1fr;
      gap:20px;
      align-items:start;
    }
    @media (max-width: 900px){
      .checkout-grid{ grid-template-columns:1fr; }
    }

    fieldset{ border:1px solid #e6e6e6; border-radius:12px; padding:12px 14px; }
    legend{ padding:0 6px; font-weight:700; color:#22332d; }
    label{ display:block; margin:.5rem 0 .25rem; font-weight:600; }
    input, select{
      width:100%; height:42px; padding:0 10px;
      border:1px solid #ccc; border-radius:10px; font-size:16px;
      outline:none;
    }
    button[type=submit]{
      margin-top:.75rem; padding:10px 16px; border-radius:10px; border:0;
      background:#029664; color:#fff; font-weight:700; cursor:pointer;
    }
    button[type=submit]:hover{ filter:brightness(0.95); }

    /* tabella riepilogo (riuso classi carrello) */
    .cart-table{ width:100%; border-collapse:collapse; }
    .cart-table th, .cart-table td{ border-bottom:1px solid #eee; padding:10px 12px; }
    .cart-table thead th{ background:#f6f8f7; font-weight:700; color:#22332d; }
    .right{ text-align:right; }
  </style>
</head>
<body>
  <?php include __DIR__ . "/../../templates/header/header.html"; ?>
  <?php include __DIR__ . "/../../templates/components/back.php"; ?>

  <section class="page">
    <h1>Checkout</h1>

    <?php if (empty($items)): ?>
      <p>Il carrello è vuoto.</p>
      <p><a href="<?= $BASE ?>/public/">Torna ai prodotti</a></p>
    <?php else: ?>
      <div class="checkout-grid">
        <div>
          <form method="post" action="<?= $BASE ?>/public/orders/place_order.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <fieldset style="margin-bottom:1rem;">
              <legend>Indirizzo spedizione</legend>
              <label>Nome completo</label>
              <input name="ship_name" required>
              <label>Indirizzo</label>
              <input name="ship_addr" required>
              <label>Città</label>
              <input name="ship_city" required>
              <label>CAP</label>
              <input name="ship_zip" required>
            </fieldset>

            <fieldset style="margin-bottom:1rem;">
              <legend>Pagamento</legend>
              <select name="payment_method" id="pm">
                <option value="cod">Contanti alla consegna</option>
                <option value="card">Carta</option>
              </select>
              <div id="cardFields" style="display:none; margin-top:.5rem;">
                <label>Numero carta</label>
                <input name="card_number" pattern="[0-9 ]{12,19}">
                <label>Scadenza (MM/YY)</label>
                <input name="card_exp" pattern="[0-9]{2}/[0-9]{2}">
                <label>CVC</label>
                <input name="card_cvc" pattern="[0-9]{3,4}">
              </div>
            </fieldset>

            <button type="submit">Conferma ordine</button>
          </form>
        </div>

        <aside>
          <h3>Riepilogo</h3>
          <table class="cart-table">
            <thead>
              <tr><th>Prodotto</th><th class="right">Qtà</th><th class="right">Prezzo</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['title']) ?></td>
                <td class="right"><?= (int)$it['qty'] ?></td>
                <td class="right">
                  <?= number_format((float)$it['price']*(int)$it['qty'], 2, ',', '.') ?>
                  <?= htmlspecialchars($it['currency'] ?? 'EUR') ?>
                </td>
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
    if (pm) { pm.addEventListener('change', toggleCard); toggleCard(); }
  </script>

  <?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
