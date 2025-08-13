<?php
// public/orders/my_orders.php
session_start();
//require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Ordini utente
$stmt = $conn->prepare("SELECT id, status, total_amount, currency, created_at FROM `order` WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Items per ordine (mappa order_id => items)
$orderItems = [];
if ($orders) {
  $ids = array_map(fn($o)=> (int)$o['id'], $orders);
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids));

  $sql = "SELECT oi.order_id, oi.product_id, oi.qty, oi.unit_price, p.title
          FROM order_item oi
          JOIN product p ON p.id = oi.product_id
          WHERE oi.order_id IN ($placeholders)
          ORDER BY oi.order_id DESC, oi.id ASC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$ids);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) {
    $orderItems[(int)$r['order_id']][] = $r;
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
  <link rel="stylesheet" href="/public/styles/main.css">
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
    <div class="ok">Ordine #<?= $lastOrderId ?> creato correttamente.</div>
  <?php endif; ?>

  <?php if (!$orders): ?>
    <p>Non hai ancora effettuato ordini. <a href="/public/products/list.php">Vai al catalogo</a></p>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
      <article class="order">
        <h2>Ordine #<?= (int)$o['id'] ?> · Stato: <?= htmlspecialchars($o['status']) ?> · del <?= htmlspecialchars($o['created_at']) ?></h2>
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
            <?php 
              $rows = $orderItems[(int)$o['id']] ?? [];
              foreach ($rows as $r):
                $line = (float)$r['unit_price'] * (int)$r['qty'];
            ?>
              <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= number_format((float)$r['unit_price'], 2, ',', '.') ?> <?= htmlspecialchars($o['currency']) ?></td>
                <td><?= (int)$r['qty'] ?></td>
                <td><?= number_format($line, 2, ',', '.') ?> <?= htmlspecialchars($o['currency']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="tot">Totale ordine</td>
              <td class="tot"><?= number_format((float)$o['total_amount'], 2, ',', '.') ?> <?= htmlspecialchars($o['currency']) ?></td>
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
