<?php
// public/orders/place_order.php
session_start();
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// 1) Carico gli articoli del carrello in modo consistente
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
    header("Location: /public/cart/view.php?err=empty");
    exit;
  }

  // 2) Normalizzo quantità rispetto allo stock e a prodotti disattivi
  $normalized = [];
  $currency = 'EUR';
  foreach ($items as $it) {
    if ((int)$it['is_active'] !== 1) {
      continue; // salta prodotti non attivi
    }
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
    ];
  }

  if (!$normalized) {
    // carrello senza quantità valide
    // lo pulisco e rimando al carrello
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: /public/cart/view.php?err=nostock");
    exit;
  }

  // 3) Calcolo il totale
  $total = 0.0;
  foreach ($normalized as $n) {
    $total += $n['price'] * $n['qty'];
  }

  // 4) Creo l’ordine
  $stmt = $conn->prepare("INSERT INTO `order` (user_id, status, total_amount, currency) VALUES (?, 'pending', ?, ?)");
  $stmt->bind_param('ids', $userId, $total, $currency);
  $stmt->execute();
  $orderId = (int)$stmt->insert_id;
  $stmt->close();

  // 5) Riga per riga: salvo i prezzi correnti come unit_price e scalo stock
  $stmtItem = $conn->prepare("INSERT INTO order_item (order_id, product_id, qty, unit_price) VALUES (?, ?, ?, ?)");
  $stmtStock = $conn->prepare("UPDATE product SET stock = stock - ? WHERE id = ? AND stock >= ?");

  foreach ($normalized as $n) {
    $pid = $n['product_id'];
    $qty = $n['qty'];
    $price = $n['price'];

    // inserisco riga ordine
    $stmtItem->bind_param('iiid', $orderId, $pid, $qty, $price);
    $stmtItem->execute();

    // scalo stock
    $stmtStock->bind_param('iii', $qty, $pid, $qty);
    $stmtStock->execute();
    if ($stmtStock->affected_rows === 0) {
      // fallback: se per race condition non riesco a scalare, errore
      throw new Exception("Stock insufficiente per prodotto $pid");
    }
  }
  $stmtItem->close();
  $stmtStock->close();

  // 6) Svuoto carrello utente
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=?");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();

  header("Location: /public/orders/my_orders.php?order_id=" . $orderId);
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  // In produzione loggherei l’errore; qui ridireziono al carrello con messaggio semplice
  header("Location: /public/cart/view.php?err=checkout");
  exit;
}
