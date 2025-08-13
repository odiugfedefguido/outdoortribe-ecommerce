<?php
session_start();
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$productId = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 0);
if ($userId <= 0 || $productId <= 0 || $qty <= 0) {
  header("Location: /public/products/list.php"); exit;
}

// verifica prodotto e stock
$stmt = $conn->prepare("SELECT id, stock, is_active FROM product WHERE id=?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p || (int)$p['is_active'] !== 1) {
  header("Location: /public/products/list.php?err=notfound"); exit;
}
if ($qty > (int)$p['stock']) {
  $qty = (int)$p['stock'];
  if ($qty <= 0) { header("Location: /public/products/details.php?id=$productId&err=nostock"); exit; }
}

// upsert cart_item
$sql = "INSERT INTO cart_item (user_id, product_id, qty)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE qty = LEAST(qty + VALUES(qty), ?)";
$stmt = $conn->prepare($sql);
$maxQty = $p['stock'];
$stmt->bind_param('iiii', $userId, $productId, $qty, $maxQty);
$stmt->execute();
$stmt->close();

header("Location: /public/cart/view.php");
