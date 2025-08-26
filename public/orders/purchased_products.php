<?php
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
<!DOCTYPE html><html lang="it"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prodotti acquistati</title>
<link rel="stylesheet" href="<?= $BASE ?>/assets/styles.css">
<style>.badge-esaurito{background:#222;color:#fff;border-radius:8px;padding:2px 8px;font-size:12px;}
table{width:100%;border-collapse:collapse;margin-top:10px;}th,td{border-bottom:1px solid #eee;padding:8px;text-align:left;}</style>
</head><body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<section class="container">
  <h1>Prodotti acquistati</h1>
  <?php if (!$rows): ?>
    <p>Non hai ancora acquistato prodotti.</p>
  <?php else: ?>
    <table><thead><tr><th>Prodotto</th><th>Quantità acquistata</th><th>Stock attuale</th><th>Stato</th><th>Ultimo acquisto</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= $BASE ?>/public/products/details.php?id=<?= (int)$r['product_id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
        <td><?= (int)$r['qty_bought'] ?></td>
        <td><?= (int)$r['stock'] ?></td>
        <td><?php if ((int)$r['stock'] <= 0): ?><span class="badge-esaurito">Esaurito</span><?php else: ?>Disponibile<?php endif; ?></td>
        <td><?= htmlspecialchars($r['last_purchase']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php endif; ?>
</section>
<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body></html>
