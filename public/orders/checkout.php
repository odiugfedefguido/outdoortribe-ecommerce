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
    .error{ color:#a10a0a; font-size:.9rem; margin:.25rem 0 .5rem; display:none; }
    .invalid{ border-color:#a10a0a !important; background:#ffecec; }

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
          <!-- novalidate per gestire messaggi custom via JS -->
          <form id="checkoutForm" method="post" action="<?= $BASE ?>/public/orders/place_order.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <fieldset style="margin-bottom:1rem;">
              <legend>Indirizzo spedizione</legend>

              <label>Nome completo</label>
              <input id="ship_name" name="ship_name" required
                     placeholder="Es. Mario Rossi"
                     pattern="[A-Za-zÀ-ÖØ-öø-ÿ' ]{2,60}">
              <div class="error" id="err_ship_name">Inserisci un nome valido (solo lettere, spazi, apostrofi, 2–60 caratteri).</div>

              <label>Indirizzo</label>
              <input id="ship_addr" name="ship_addr" required placeholder="Via, numero civico">
              <div class="error" id="err_ship_addr">Indirizzo troppo corto.</div>

              <label>Città</label>
              <input id="ship_city" name="ship_city" required
                     pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,60}">
              <div class="error" id="err_ship_city">Inserisci una città valida (solo lettere/spazi/trattini).</div>

              <label>CAP</label>
              <input id="ship_zip" name="ship_zip" required inputmode="numeric" pattern="\d{5}" maxlength="5" placeholder="Es. 00100">
              <div class="error" id="err_ship_zip">CAP non valido (5 cifre).</div>

              <label>Telefono</label>
              <input id="ship_phone" name="ship_phone" placeholder="Es. 333 1234567" inputmode="tel">
              <div class="error" id="err_ship_phone">Telefono non valido (7–15 cifre; niente lettere).</div>
            </fieldset>

            <fieldset style="margin-bottom:1rem;">
              <legend>Pagamento</legend>
              <select name="payment_method" id="pm">
                <option value="cod">Contanti alla consegna</option>
                <option value="card">Carta</option>
              </select>
              <div id="cardFields" style="display:none; margin-top:.5rem;">
                <label>Numero carta</label>
                <input id="card_number" name="card_number" inputmode="numeric" placeholder="1234 5678 9012 3456">
                <div class="error" id="err_card_number">Numero carta non valido.</div>

                <label>Scadenza (MM/YY)</label>
                <input id="card_exp" name="card_exp" placeholder="MM/YY">
                <div class="error" id="err_card_exp">Scadenza non valida o già passata.</div>

                <label>CVC</label>
                <input id="card_cvc" name="card_cvc" inputmode="numeric" placeholder="3 o 4 cifre" maxlength="4">
                <div class="error" id="err_card_cvc">CVC non valido.</div>
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

    // helpers error UI
    function setErr(el, msgId){
      el.classList.add('invalid');
      const e = document.getElementById(msgId);
      if (e) e.style.display = 'block';
    }
    function clrErr(el, msgId){
      el.classList.remove('invalid');
      const e = document.getElementById(msgId);
      if (e) e.style.display = 'none';
    }

    // Luhn check per carta
    function luhn(num){
      let sum = 0, alt = false;
      for (let i = num.length - 1; i >= 0; i--) {
        let n = parseInt(num[i], 10);
        if (alt) { n *= 2; if (n > 9) n -= 9; }
        sum += n; alt = !alt;
      }
      return sum % 10 === 0;
    }

    document.getElementById('checkoutForm')?.addEventListener('submit', function(ev){
      let ok = true;

      const name = document.getElementById('ship_name');
      const addr = document.getElementById('ship_addr');
      const city = document.getElementById('ship_city');
      const zip  = document.getElementById('ship_zip');
      const phone= document.getElementById('ship_phone');

      // Nome: solo lettere, spazi, apostrofi
      const reName = /^[A-Za-zÀ-ÖØ-öø-ÿ' ]{2,60}$/;
      if (!reName.test(name.value.trim())) { setErr(name,'err_ship_name'); ok=false; } else { clrErr(name,'err_ship_name'); }

      // Indirizzo: minimo 6 char
      if (addr.value.trim().length < 6) { setErr(addr,'err_ship_addr'); ok=false; } else { clrErr(addr,'err_ship_addr'); }

      // Città
      const reCity = /^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,60}$/;
      if (!reCity.test(city.value.trim())) { setErr(city,'err_ship_city'); ok=false; } else { clrErr(city,'err_ship_city'); }

      // CAP: 5 cifre
      if (!/^\d{5}$/.test(zip.value.trim())) { setErr(zip,'err_ship_zip'); ok=false; } else { clrErr(zip,'err_ship_zip'); }

      // Telefono: niente lettere; 7–15 cifre contando solo i numeri
      const hasLetters = /[A-Za-z]/.test(phone.value);
      const digits = (phone.value.match(/\d/g) || []).length;
      if (phone.value && (hasLetters || digits < 7 || digits > 15)) {
        setErr(phone,'err_ship_phone'); ok=false;
      } else { clrErr(phone,'err_ship_phone'); }

      // Pagamento carta
      if (pm.value === 'card') {
        const cn = document.getElementById('card_number');
        const exp= document.getElementById('card_exp');
        const cvc= document.getElementById('card_cvc');

        const raw = cn.value.replace(/\s+/g,'');
        if (!/^\d{12,19}$/.test(raw) || !luhn(raw)) { setErr(cn,'err_card_number'); ok=false; } else { clrErr(cn,'err_card_number'); }

        // scadenza MM/YY futura
        const m = /^(\d{2})\/(\d{2})$/.exec(exp.value.trim());
        if (!m) { setErr(exp,'err_card_exp'); ok=false; }
        else {
          let mm = parseInt(m[1],10), yy = parseInt(m[2],10);
          if (mm < 1 || mm > 12) { setErr(exp,'err_card_exp'); ok=false; }
          else {
            // fine mese 20yy
            const now = new Date();
            const year = 2000 + yy;
            const lastDay = new Date(year, mm, 0); // ultimo giorno mese mm
            const endOfMonth = new Date(year, mm-1, lastDay.getDate(), 23,59,59,999);
            if (endOfMonth < now) { setErr(exp,'err_card_exp'); ok=false; } else { clrErr(exp,'err_card_exp'); }
          }
        }

        if (!/^\d{3,4}$/.test(cvc.value.trim())) { setErr(cvc,'err_card_cvc'); ok=false; } else { clrErr(cvc,'err_card_cvc'); }
      }

      if (!ok) ev.preventDefault();
    });
  </script>

  <?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
