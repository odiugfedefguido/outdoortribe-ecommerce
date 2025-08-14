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

if (!$items) {
  header("Location: {$BASE}/public/cart/view.php?err=empty");
  exit;
}

// Dati profilo utente per prefill
$stmt = $conn->prepare("SELECT name, email, phone, ship_address, ship_city, ship_zip, ship_country
                        FROM `user` WHERE id=?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$pref_name   = $u['name']         ?? '';
$pref_email  = $u['email']        ?? '';
$pref_phone  = $u['phone']        ?? '';
$pref_addr   = $u['ship_address'] ?? '';
$pref_city   = $u['ship_city']    ?? '';
$pref_zip    = $u['ship_zip']     ?? '';
$pref_country= $u['ship_country'] ?? 'Italia';

$subtotal = 0.0;
foreach ($items as $it) { $subtotal += (float)$it['price'] * (int)$it['qty']; }
$currency     = $items[0]['currency'] ?? 'EUR';
$shippingCost = ($subtotal >= 99.00) ? 0.00 : 6.90;  // soglia spedizione gratuita
$vatRate   = 22.00;
$vatAmount = round($subtotal * ($vatRate/100), 2);   // indicativo
$grandTotal= $subtotal + $shippingCost;

// CSRF token
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Checkout</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .grid {display:grid; gap:16px; grid-template-columns: 1fr; }
    @media(min-width:920px){ .grid{ grid-template-columns: 2fr 1fr; } }
    .card{border:1px solid #ddd;border-radius:10px;padding:12px;background:#fff}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #eee}
    .tot{font-weight:bold;text-align:right}
    .row{display:flex; gap:8px}
    .row > div{flex:1}
    label{display:block;margin:6px 0 3px}
    input,select,textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px}
    .actions{display:flex;justify-content:flex-end;gap:8px;margin-top:12px}
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <h1>Checkout</h1>
  <div class="grid">
    <div class="card">
      <h2>Indirizzo di spedizione</h2>
      <form method="post" action="<?= $BASE ?>/public/orders/place_order.php" id="checkoutForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="row">
          <div>
            <label>Nome e cognome</label>
            <input name="customer_name" required maxlength="120" value="<?= htmlspecialchars($pref_name) ?>">
          </div>
          <div>
            <label>Telefono</label>
            <input name="customer_phone" required maxlength="40" value="<?= htmlspecialchars($pref_phone) ?>">
          </div>
        </div>

        <div class="row">
          <div>
            <label>Email</label>
            <input type="email" name="customer_email" required maxlength="190" value="<?= htmlspecialchars($pref_email) ?>">
          </div>
          <div>
            <label>CAP</label>
            <input name="ship_zip" required maxlength="20" value="<?= htmlspecialchars($pref_zip) ?>">
          </div>
        </div>

        <label>Indirizzo</label>
        <input name="ship_address" required maxlength="200" value="<?= htmlspecialchars($pref_addr) ?>">

        <div class="row">
          <div>
            <label>Città</label>
            <input name="ship_city" required maxlength="120" value="<?= htmlspecialchars($pref_city) ?>">
          </div>
          <div>
            <label>Nazione</label>
            <input name="ship_country" required maxlength="120" value="<?= htmlspecialchars($pref_country) ?>">
          </div>
        </div>

        <label>Note (opzionale)</label>
        <textarea name="notes" maxlength="500"></textarea>

        <h2 style="margin-top:12px;">Pagamento</h2>
        <label>Metodo</label>
        <select name="payment_method" id="pm">
          <option value="cod">Contrassegno</option>
          <option value="card">Carta (simulazione)</option>
        </select>

        <div id="cardFields" style="display:none; margin-top:8px;">
          <div class="row">
            <div>
              <label>Numero carta</label>
              <input name="card_number" maxlength="19" placeholder="4111 1111 1111 1111">
            </div>
            <div>
              <label>Scadenza (MM/AA)</label>
              <input name="card_exp" maxlength="5" placeholder="12/27">
            </div>
            <div>
              <label>CVV</label>
              <input name="card_cvv" maxlength="4" placeholder="123">
            </div>
          </div>
        </div>

        <div class="actions">
          <a class="btn-secondary" href="<?= $BASE ?>/public/cart/view.php">Torna al carrello</a>
          <button type="submit">Conferma ordine</button>
        </div>
      </form>
    </div>

    <aside class="card">
      <h2>Riepilogo</h2>
      <table>
        <thead>
          <tr><th>Prodotto</th><th style="width:90px;">Q.tà</th><th style="width:120px;">Totale</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it):
            $line = (float)$it['price'] * (int)$it['qty']; ?>
            <tr>
              <td><?= htmlspecialchars($it['title']) ?></td>
              <td><?= (int)$it['qty'] ?></td>
              <td><?= number_format($line, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" class="tot">Subtotale</td>
            <td class="tot"><?= number_format($subtotal, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
          </tr>
          <tr>
            <td colspan="2" class="tot">Spedizione</td>
            <td class="tot"><?= number_format($shippingCost, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
          </tr>
          <tr>
            <td colspan="2" class="tot">IVA (<?= number_format($vatRate,2) ?>%)</td>
            <td class="tot"><?= number_format($vatAmount, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
          </tr>
          <tr>
            <td colspan="2" class="tot">Totale</td>
            <td class="tot"><?= number_format($grandTotal, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
          </tr>
        </tfoot>
      </table>
    </aside>
  </div>
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
