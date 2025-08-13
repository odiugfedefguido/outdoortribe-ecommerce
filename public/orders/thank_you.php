<?php
// public/orders/thank_you.php
session_start();
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) { header("Location: /public/orders/my_orders.php"); exit; }

$stmt = $conn->prepare("SELECT id, status, grand_total, currency, customer_name FROM `order` WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$o = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$o) { header("Location: /public/orders/my_orders.php"); exit; }
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Grazie per l'ordine</title>
  <link rel="stylesheet" href="/public/styles/main.css">
  <style>
    .ok{background:#e6ffed;border:1px solid #b7f5c6;padding:12px;border-radius:10px;margin:16px 0}
    .actions{display:flex;gap:8px}
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <h1>Grazie per l’ordine!</h1>
  <div class="ok">
    <p><strong>Ordine #<?= (int)$o['id'] ?></strong> per <?= htmlspecialchars($o['customer_name'] ?? '') ?> registrato con stato <strong><?= htmlspecialchars($o['status']) ?></strong>.</p>
    <p>Totale: <strong><?= number_format((float)$o['grand_total'], 2, ',', '.') ?> <?= htmlspecialchars($o['currency']) ?></strong></p>
  </div>

  <div class="actions">
    <a href="/public/orders/my_orders.php">Vedi i miei ordini</a>
    <a href="/public/products/list.php">Torna al catalogo</a>
  </div>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
