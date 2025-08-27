<?php
// public/orders/place_order.php
require_once __DIR__ . '/../bootstrap.php'; // sessione + $BASE + $conn
require_once __DIR__ . '/../../server/notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: {$BASE}/public/cart/view.php");
  exit;
}

$userId = current_user_id();

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
  header("Location: {$BASE}/public/cart/view.php?err=csrf");
  exit;
}

$conn->begin_transaction();

try {
  // Leggi carrello e blocca righe prodotto
  $sql = "SELECT ci.product_id, ci.qty, p.title, p.price, p.currency, p.stock
          FROM cart_item ci
          JOIN product p ON p.id = ci.product_id
          WHERE ci.user_id = ?
          FOR UPDATE";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  if (empty($items)) {
    $conn->rollback();
    header("Location: {$BASE}/public/cart/view.php?err=empty");
    exit;
  }

  // Verifica stock
  foreach ($items as $it) {
    if ((int)$it['qty'] <= 0 || (int)$it['stock'] < (int)$it['qty']) {
      $conn->rollback();
      header("Location: {$BASE}/public/cart/view.php?err=nostock");
      exit;
    }
  }

  // Calcoli
  $subtotal = 0.0; $currency = 'EUR';
  foreach ($items as $it) {
    $subtotal += ((float)$it['price']) * (int)$it['qty'];
    if (!empty($it['currency'])) $currency = $it['currency'];
  }
  $shipping = 0.0; $vat = 0.0; $grand = $subtotal + $shipping + $vat;

  // Crea ordine
  $stmt = $conn->prepare("INSERT INTO `order` (user_id, status, total_amount, currency, shipping_cost, vat_amount, grand_total, created_at) VALUES (?, 'placed', ?, ?, ?, ?, ?, NOW())");
  $stmt->bind_param('isdddd', $userId, $subtotal, $currency, $shipping, $vat, $grand);
  $stmt->execute();
  $orderId = (int)$stmt->insert_id;
  $stmt->close();

  // Items
  $stmtItem = $conn->prepare("INSERT INTO order_item (order_id, product_id, qty, unit_price, currency) VALUES (?,?,?,?,?)");
  foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $qty = (int)$it['qty'];
    $price = (float)$it['price'];
    $cur = (string)($it['currency'] ?: $currency);
    $stmtItem->bind_param('iiids', $orderId, $pid, $qty, $price, $cur);
    $stmtItem->execute();

    // Scala stock
    $stmt2 = $conn->prepare("UPDATE product SET stock = stock - ? WHERE id = ?");
    $stmt2->bind_param('ii', $qty, $pid);
    $stmt2->execute();
    $stmt2->close();
  }
  $stmtItem->close();

  // Svuota carrello
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=?");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $stmt->close();

    // Notifiche: vendite ai seller + esauriti a seller e utenti con carrello
  notify_sellers_items_sold_for_order($conn, $orderId, $BASE);
  notify_when_sold_out_for_order($conn, $orderId, $BASE);

  $conn->commit();

  header("Location: {$BASE}/public/orders/thank_you.php?order_id=" . $orderId);
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  header("Location: {$BASE}/public/cart/view.php?err=checkout");
  exit;
}
