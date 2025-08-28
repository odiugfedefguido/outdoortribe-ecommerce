<?php
// public/orders/thank_you.php
require_once __DIR__ . '/../bootstrap.php'; // sessione + $BASE + $conn

$userId  = current_user_id();
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
  header("Location: {$BASE}/public/orders/my_orders.php");
  exit;
}

$stmt = $conn->prepare("SELECT id, status, grand_total, currency, customer_name, created_at
                        FROM `order`
                        WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$o = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$o) {
  header("Location: {$BASE}/public/orders/my_orders.php");
  exit;
}

// Prodotti dell'ordine (per tabellina riepilogo)
$items = [];
$stmt = $conn->prepare("SELECT oi.product_id, oi.qty, oi.unit_price, oi.currency, p.title
                        FROM order_item oi
                        LEFT JOIN product p ON p.id = oi.product_id
                        WHERE oi.order_id=?");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$res = $stmt->get_result();
if ($res) $items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cur = htmlspecialchars($o['currency'] ?? 'EUR');
$tot = number_format((float)$o['grand_total'], 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Grazie per l'ordine</title>

  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/mountain.svg">

  <!-- CSS globali coerenti con le altre pagine -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">

  <style>
    /* Layout pagina */
    section.page{ margin:70px auto 24px; max-width:1100px; width:100%; padding:0 16px; }
    .card{
      background:#fff; border:1px solid #edf1ef; border-radius:16px;
      box-shadow:0 6px 20px rgba(0,0,0,.06); padding:18px 20px;
    }
    .ok h2{ margin:0 0 .35rem 0; }
    .muted{ color:#6b7a86; }

    /* Tabella prodotti */
    .table{ width:100%; border-collapse:collapse; margin-top:.75rem; }
    .table th, .table td{ padding:10px 12px; border-bottom:1px solid #edf1ef; }
    .table thead th{ background:#f6f8f7; color:#22332d; text-align:left; }
    .right{ text-align:right; }

    /* Bottoni alti contrasto (coerenti con carrello/notifiche) */
    .btn{
      appearance:none; border:0; border-radius:12px;
      padding:10px 14px; font-weight:800; line-height:1; cursor:pointer;
      display:inline-flex; align-items:center; justify-content:center;
      box-shadow:0 1px 0 rgba(0,0,0,.04), 0 6px 16px rgba(0,0,0,.06);
      transition:transform .05s ease, filter .15s ease;
      user-select:none; text-decoration:none;
      min-width: 160px;
    }
    .btn:active{ transform:translateY(1px); }
    .btn:focus-visible{ outline:3px solid #99c2ff; outline-offset:2px; }
    .btn-primary{ background:#0b4ea9; color:#fff; }
    .btn-primary:hover{ filter:brightness(.96); }
    .btn-ghost{ background:#ffffff; color:#1b2a24; border:1px solid rgba(0,0,0,.12); }
    .btn-ghost:hover{ filter:brightness(.98); }

    .actions{ display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem; }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="page">
  <h1>Grazie!</h1>

  <div class="card ok">
    <h2>Ordine #<?= (int)$o['id'] ?></h2>
    <p class="muted">Creato il <?= htmlspecialchars((string)$o['created_at']) ?></p>
    <p>Stato: <strong><?= htmlspecialchars($o['status']) ?></strong></p>
    <p>Totale: <strong><?= $tot ?> <?= $cur ?></strong></p>

    <?php if (!empty($items)): ?>
      <h3 style="margin-top:1rem;">Articoli acquistati</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Prodotto</th>
            <th class="right">Qtà</th>
            <th class="right">Prezzo</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['title'] ?? ('#'.$it['product_id'])) ?></td>
              <td class="right"><?= (int)$it['qty'] ?></td>
              <td class="right">
                <?= number_format(((float)$it['unit_price']*(int)$it['qty']), 2, ',', '.') ?>
                <?= htmlspecialchars($it['currency'] ?? $cur) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <div class="actions">
      <a class="btn btn-primary" href="<?= $BASE ?>/public/orders/my_orders.php">Vedi i miei ordini</a>
      <a class="btn btn-ghost" href="<?= $BASE ?>/public/products/list.php">Torna al catalogo</a>
    </div>
  </div>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
