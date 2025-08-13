<?php
session_start();
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$productId = (int)($_POST['product_id'] ?? 0);
$qty = max(0, (int)($_POST['qty'] ?? 0));
if ($userId <= 0 || $productId <= 0) { header("Location: /public/cart/view.php"); exit; }

if ($qty === 0) {
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
  $stmt->bind_param('ii', $userId, $productId);
  $stmt->execute();
  $stmt->close();
} else {
  // limita allo stock
  $stmt = $conn->prepare("SELECT stock FROM product WHERE id=?");
  $stmt->bind_param('i', $productId);
  $stmt->execute();
  $stock = ($stmt->get_result()->fetch_assoc()['stock'] ?? 0);
  $stmt->close();

  $qty = min($qty, (int)$stock);
  if ($qty <= 0) {
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute(); $stmt->close();
  } else {
    $stmt = $conn->prepare("UPDATE cart_item SET qty=? WHERE user_id=? AND product_id=?");
    $stmt->bind_param('iii', $qty, $userId, $productId);
    $stmt->execute(); $stmt->close();
  }
}

header("Location: /public/cart/view.php");
