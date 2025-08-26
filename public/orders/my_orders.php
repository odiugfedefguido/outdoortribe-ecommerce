<?php
// public/orders/my_orders.php
require_once __DIR__ . '/../bootstrap.php'; // sessione + $BASE + $conn

$userId = current_user_id();

// Ordini dell'utente (senza total_amount/currency: li calcoliamo dagli items)
$stmt = $conn->prepare("SELECT id, status, created_at
                        FROM `order`
                        WHERE user_id=?
                        ORDER BY id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Items per ordine (mappa order_id => items) + totali e currency calcolati
$orderItems   = [];
$orderTotals  = []; // order_id => somma(qty * unit_price)
$orderCurrency= []; // order_id => currency (dalla prima riga prodotto)

if ($orders) {
  $ids = array_map(fn($o) => (int)$o['id'], $orders);
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids));

  $sql = "SELECT oi.order_id, oi.product_id, oi.qty, oi.unit_price, p.title, p.currency
          FROM order_item oi
          JOIN product p ON p.id = oi.product_id
          WHERE oi.order_id IN ($placeholders)
          ORDER BY oi.order_id DESC, oi.id ASC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$ids);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($r = $res->fetch_assoc()) {
    $oid = (int)$r['order_id'];
    $orderItems[$oid][] = $r;

    // calcolo totale ordine
    $line = (float)$r['unit_price'] * (int)$r['qty'];
    if (!isset($orderTotals[$oid])) $orderTotals[$oid] = 0.0;
    $orderTotals[$oid] += $line;

    // prendo la currency dalla prima riga utile
    if (!isset($orderCurrency[$oid]) || $orderCurrency[$oid] === '') {
      $orderCurrency[$oid] = $r['currency'] ?: 'EUR';
    }
  }
  $stmt->close();
}

$lastOrderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>I miei ordini</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .order{border:1px solid #ddd;border-radius:10px;margin:12px 0;padding:12px;background:#fff}
    .order h2{margin:0 0 8px 0}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #eee}
    .tot{font-weight:bold;text-align:right}
    .ok{background:#e6ffed;border:1px solid #b7f5c6;padding:8px;border-radius:8px;margin:12px 0}
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <h1>I miei ordini</h1>

  <?php if ($lastOrderId): ?>
    <div class="ok">Ordine #<?= (int)$lastOrderId ?> creato correttamente.</div>
  <?php endif; ?>

  <?php if (!$orders): ?>
    <p>Non hai ancora effettuato ordini. <a href="<?= $BASE ?>/public/products/list.php">Vai al catalogo</a></p>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
      <?php
        $oid = (int)$o['id'];
        $rows = $orderItems[$oid] ?? [];
        $cur  = $orderCurrency[$oid] ?? 'EUR';
        $tot  = $orderTotals[$oid] ?? 0.0;
      ?>
      <article class="order">
        <h2>
          Ordine #<?= $oid ?>
          · Stato: <?= htmlspecialchars($o['status']) ?>
          · del <?= htmlspecialchars($o['created_at']) ?>
        </h2>
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
            <?php foreach ($rows as $r): ?>
              <?php $line = (float)$r['unit_price'] * (int)$r['qty']; ?>
              <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= number_format((float)$r['unit_price'], 2, ',', '.') ?> <?= htmlspecialchars($cur) ?></td>
                <td><?= (int)$r['qty'] ?></td>
                <td><?= number_format($line, 2, ',', '.') ?> <?= htmlspecialchars($cur) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="tot">Totale ordine</td>
              <td class="tot"><?= number_format($tot, 2, ',', '.') ?> <?= htmlspecialchars($cur) ?></td>
            </tr>
          </tfoot>
        </table>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
