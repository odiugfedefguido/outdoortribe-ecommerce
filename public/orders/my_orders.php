<?php
// public/orders/my_orders.php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../img_path.php';

$userId = current_user_id();
if ($userId <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }

/* --- ORDINI DELL'UTENTE --- */
$stmt = $conn->prepare("SELECT id, status, total_amount, currency, shipping_cost, vat_amount, grand_total, created_at
                        FROM `order`
                        WHERE user_id=?
                        ORDER BY id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Precarica items per tutti gli ordini
$orderIds = array_map(fn($o) => (int)$o['id'], $orders);
$itemsByOrder = [];
if (!empty($orderIds)) {
  $in = implode(',', array_fill(0, count($orderIds), '?'));
  $types = str_repeat('i', count($orderIds));
  $sql = "SELECT oi.order_id, oi.product_id, oi.qty, oi.unit_price, oi.currency, p.title, p.image_filename
          FROM order_item oi
          LEFT JOIN product p ON p.id = oi.product_id
          WHERE oi.order_id IN ($in)
          ORDER BY oi.order_id DESC, oi.id ASC";
  $stmt = $conn->prepare($sql);
  $bind = array_merge([$types], $orderIds);
  foreach ($bind as $k => $v) { $bind[$k] = &$bind[$k]; }
  call_user_func_array([$stmt, 'bind_param'], $bind);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $itemsByOrder[(int)$row['order_id']][] = $row;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>I miei ordini</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
    <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="page">
  <h1>I miei ordini</h1>

  <?php if (empty($orders)): ?>
    <p>Nessun ordine effettuato.</p>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
      <article class="order-card" style="border:1px solid #ddd; border-radius:10px; padding:1rem; margin-bottom:1rem;">
        <header style="display:flex; justify-content:space-between; align-items:center;">
          <div><strong>Ordine #<?= (int)$o['id'] ?></strong> • <?= htmlspecialchars($o['status']) ?></div>
          <div><strong><?= number_format((float)$o['grand_total'],2,',','.') . ' ' . htmlspecialchars($o['currency'] ?? 'EUR') ?></strong></div>
        </header>
        <div style="font-size:.9rem; color:#666; margin:.25rem 0 1rem;">del <?= htmlspecialchars($o['created_at']) ?></div>
        <table class="cart-table">
          <thead><tr><th>Prodotto</th><th class="right">Qtà</th><th class="right">Prezzo</th></tr></thead>
          <tbody>
          <?php foreach ($itemsByOrder[(int)$o['id']] ?? [] as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['title'] ?? ('Prodotto #' . (int)$it['product_id'])) ?></td>
              <td class="right"><?= (int)$it['qty'] ?></td>
              <td class="right"><?= number_format((float)$it['unit_price']*(int)$it['qty'], 2, ',', '.') . ' ' . htmlspecialchars($it['currency'] ?? ($o['currency'] ?? 'EUR')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td>Subtotale</td><td></td><td class="right"><?= number_format((float)$o['total_amount'],2,',','.') . ' ' . htmlspecialchars($o['currency'] ?? 'EUR') ?></td></tr>
            <tr><td>Spedizione</td><td></td><td class="right"><?= number_format((float)$o['shipping_cost'],2,',','.') . ' ' . htmlspecialchars($o['currency'] ?? 'EUR') ?></td></tr>
            <tr><td>IVA</td><td></td><td class="right"><?= number_format((float)$o['vat_amount'],2,',','.') . ' ' . htmlspecialchars($o['currency'] ?? 'EUR') ?></td></tr>
            <tr><td><strong>Totale</strong></td><td></td><td class="right"><strong><?= number_format((float)$o['grand_total'],2,',','.') . ' ' . htmlspecialchars($o['currency'] ?? 'EUR') ?></strong></td></tr>
          </tfoot>
        </table>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
