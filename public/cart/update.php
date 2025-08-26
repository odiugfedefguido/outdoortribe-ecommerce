<?php
// public/cart/update.php
require_once __DIR__ . '/../bootstrap.php';  // sessione + $BASE + $conn

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: {$BASE}/public/cart/view.php");
  exit;
}

$userId    = current_user_id();
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(0, (int)($_POST['qty'] ?? 0)); // 0 = rimuovi

if ($userId <= 0 || $productId <= 0) {
  header("Location: {$BASE}/public/cart/view.php");
  exit;
}

if ($qty === 0) {
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
  $stmt->bind_param('ii', $userId, $productId);
  $stmt->execute();
  $stmt->close();
  header("Location: {$BASE}/public/cart/view.php");
  exit;
}

// Verifica prodotto e stock
$stmt = $conn->prepare("SELECT stock, is_active FROM product WHERE id=?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p || (int)$p['is_active'] !== 1 || (int)$p['stock'] <= 0) {
  // Se il prodotto non è più disponibile, rimuovi dal carrello
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
  $stmt->bind_param('ii', $userId, $productId);
  $stmt->execute();
  $stmt->close();
  header("Location: {$BASE}/public/cart/view.php?err=unavailable");
  exit;
}

// Cap a stock
if ($qty > (int)$p['stock']) $qty = (int)$p['stock'];

// Aggiorna o inserisci
$stmt = $conn->prepare("SELECT qty FROM cart_item WHERE user_id=? AND product_id=?");
$stmt->bind_param('ii', $userId, $productId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
  $stmt = $conn->prepare("UPDATE cart_item SET qty=? WHERE user_id=? AND product_id=?");
  $stmt->bind_param('iii', $qty, $userId, $productId);
} else {
  $stmt = $conn->prepare("INSERT INTO cart_item (user_id, product_id, qty) VALUES (?,?,?)");
  $stmt->bind_param('iii', $userId, $productId, $qty);
}
$stmt->execute();
$stmt->close();

header("Location: {$BASE}/public/cart/view.php");
exit;
