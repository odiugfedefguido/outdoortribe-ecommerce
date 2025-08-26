<?php
// public/orders/purchased_products.php
require_once __DIR__ . '/../bootstrap.php';
$uid = current_user_id();
if ($uid <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }

$sql = "SELECT p.id AS product_id, p.title, p.currency, p.stock,
               SUM(oi.qty) AS qty_bought,
               MAX(o.created_at) AS last_purchase
        FROM `order` o
        JOIN order_item oi ON oi.order_id = o.id
        JOIN product p ON p.id = oi.product_id
        WHERE o.user_id = ?
        GROUP BY p.id, p.title, p.currency, p.stock
        ORDER BY last_purchase DESC, p.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Prodotti acquistati</title>

  <!-- CSS corretti -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">

  <style>
    .badge-esaurito{background:#222;color:#fff;border-radius:8px;padding:2px 8px;font-size:12px;}
    table{width:100%;border-collapse:collapse;margin-top:10px;}
    th,td{border-bottom:1px solid #eee;padding:8px;text-align:left;}
    .qty{font-weight:700}
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <h1>Prodotti acquistati</h1>

  <?php if (!$rows): ?>
    <p>Non hai ancora acquistato prodotti.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Prodotto</th>
          <th>Quantità acquistata</th>
          <th>Stock</th>
          <th>Ultimo acquisto</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td class="qty"><?= (int)$r['qty_bought'] ?></td>
            <td>
              <?php if ((int)$r['stock'] > 0): ?>
                Disponibile (<?= (int)$r['stock'] ?>)
              <?php else: ?>
                <span class="badge-esaurito">Esaurito</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['last_purchase']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
