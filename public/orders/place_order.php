<?php
// public/orders/place_order.php
session_start();
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
  header("Location: /public/cart/view.php?err=csrf"); exit;
}
// usa one-time token
unset($_SESSION['csrf_token']);

// Input base
$F = fn($k)=> trim((string)($_POST[$k] ?? ''));
$customer_name  = $F('customer_name');
$customer_email = $F('customer_email');
$customer_phone = $F('customer_phone');
$ship_address   = $F('ship_address');
$ship_city      = $F('ship_city');
$ship_zip       = $F('ship_zip');
$ship_country   = $F('ship_country');
$notes          = substr($F('notes'), 0, 500);
$payment_method = ($_POST['payment_method'] ?? 'cod') === 'card' ? 'card' : 'cod';

// Validazioni minime
if ($customer_name==='' || $customer_email==='' || $customer_phone==='' || $ship_address==='' || $ship_city==='' || $ship_zip==='' || $ship_country==='') {
  header("Location: /public/orders/checkout.php?err=val"); exit;
}

// Carrello (blocco per consistenza)
$sql = "SELECT ci.product_id, ci.qty, p.title, p.price, p.currency, p.stock, p.is_active
        FROM cart_item ci
        JOIN product p ON p.id = ci.product_id
        WHERE ci.user_id = ?
        FOR UPDATE";
$conn->begin_transaction();

try {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  if (!$items) {
    $conn->rollback();
    header("Location: /public/cart/view.php?err=empty"); exit;
  }

  // Normalizza qty e filtra
  $normalized = [];
  $currency = 'EUR';
  foreach ($items as $it) {
    if ((int)$it['is_active'] !== 1) continue;
    $qty = (int)$it['qty'];
    $stock = (int)$it['stock'];
    if ($stock <= 0) continue;
    if ($qty > $stock) $qty = $stock;
    if ($qty <= 0) continue;

    $currency = $it['currency'] ?: $currency;
    $normalized[] = [
      'product_id' => (int)$it['product_id'],
      'qty'        => $qty,
      'price'      => (float)$it['price'],
      'title'      => $it['title'],
    ];
  }

  if (!$normalized) {
    // ripulisce e torna al carrello
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=?");
    $stmt->bind_param('i', $userId);
    $stmt->execute(); $stmt->close();
    $conn->commit();
    header("Location: /public/cart/view.php?err=nostock"); exit;
  }

  // Calcoli economici (coerenti con checkout)
  $subtotal = 0.0;
  foreach ($normalized as $n) { $subtotal += $n['price'] * $n['qty']; }
  $shippingCost = ($subtotal >= 99.00) ? 0.00 : 6.90;
  $vatRate = 22.00;
  $vatAmount = round($subtotal * ($vatRate/100), 2);
  $grandTotal = $subtotal + $shippingCost;

  // (Simulazione pagamento carta)
  if ($payment_method === 'card') {
    // Qui potresti integrare un gateway; per ora simuliamo OK
    $card_number = preg_replace('/\s+/', '', (string)($_POST['card_number'] ?? ''));
    $card_exp    = (string)($_POST['card_exp'] ?? '');
    $card_cvv    = (string)($_POST['card_cvv'] ?? '');
    if (strlen($card_number) < 12 || strlen($card_exp) < 4 || strlen($card_cvv) < 3) {
      $conn->rollback();
      header("Location: /public/orders/checkout.php?err=card"); exit;
    }
  }

  // Crea ordine
  $stmt = $conn->prepare(
    "INSERT INTO `order`
     (user_id, status, total_amount, currency, shipping_cost, vat_rate, vat_amount, grand_total,
      customer_name, customer_email, customer_phone, ship_address, ship_city, ship_zip, ship_country, notes, payment_method)
     VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
  );
  $stmt->bind_param(
    'idssdddssssssssss',
    $userId, $subtotal, $currency, $shippingCost, $vatRate, $vatAmount, $grandTotal,
    $customer_name, $customer_email, $customer_phone, $ship_address, $ship_city, $ship_zip, $ship_country, $notes, $payment_method
  );
  $stmt->execute();
  $orderId = (int)$stmt->insert_id;
  $stmt->close();

  // Righe + scalatura stock
  $stmtItem = $conn->prepare("INSERT INTO order_item (order_id, product_id, qty, unit_price) VALUES (?, ?, ?, ?)");
  $stmtStock = $conn->prepare("UPDATE product SET stock = stock - ? WHERE id = ? AND stock >= ?");

  foreach ($normalized as $n) {
    $pid = $n['product_id'];
    $qty = $n['qty'];
    $price = $n['price'];

    $stmtItem->bind_param('iiid', $orderId, $pid, $qty, $price);
    $stmtItem->execute();

    $stmtStock->bind_param('iii', $qty, $pid, $qty);
    $stmtStock->execute();
    if ($stmtStock->affected_rows === 0) {
      throw new Exception("Stock insufficiente per prodotto $pid");
    }
  }
  $stmtItem->close();
  $stmtStock->close();

  // Svuota carrello
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=?");
  $stmt->bind_param('i', $userId);
  $stmt->execute(); $stmt->close();

  // Imposta stato pagato se carta
  if ($payment_method === 'card') {
    $stmt = $conn->prepare("UPDATE `order` SET status='paid' WHERE id=?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute(); $stmt->close();
  }

  $conn->commit();

  header("Location: /public/orders/thank_you.php?order_id=" . $orderId);
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  header("Location: /public/cart/view.php?err=checkout"); exit;
}
