<?php
// public/orders/thank_you.php
require_once __DIR__ . '/../bootstrap.php'; // sessione + $BASE + $conn

$userId  = current_user_id();
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
  header("Location: {$BASE}/public/orders/my_orders.php");
  exit;
}

$stmt = $conn->prepare("SELECT id, status, grand_total, currency, customer_name
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
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Grazie per l'ordine</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="page">
  <h1>Grazie!</h1>
  <div class="ok">
    <p><strong>Ordine #<?= (int)$o['id'] ?></strong> registrato con stato <strong><?= htmlspecialchars($o['status']) ?></strong>.</p>
    <p>Totale: <strong><?= number_format((float)$o['grand_total'], 2, ',', '.') ?> <?= htmlspecialchars($o['currency'] ?? 'EUR') ?></strong></p>
  </div>

  <div class="actions" style="margin-top:1rem;">
    <a class="btn-secondary" href="<?= $BASE ?>/public/orders/my_orders.php">Vedi i miei ordini</a>
    <a href="<?= $BASE ?>/public/products/list.php">Torna al catalogo</a>
  </div>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
