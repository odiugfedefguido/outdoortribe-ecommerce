<?php
// public/cart/add.php
session_start();
require_once __DIR__ . '/../config_path.php';
//require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId    = (int)($_SESSION['user_id'] ?? 0);
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 0)); // evita 0 o negativi

if ($userId <= 0 || $productId <= 0) {
  header("Location: {$BASE}/public/products/list.php");
  exit;
}

// verifica prodotto e stock
$stmt = $conn->prepare("SELECT id, stock, is_active FROM product WHERE id=?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p || (int)$p['is_active'] !== 1) {
  header("Location: {$BASE}/public/products/list.php?err=notfound");
  exit;
}

$stock = (int)$p['stock'];
if ($stock <= 0) {
  header("Location: {$BASE}/public/products/details.php?id={$productId}&err=nostock");
  exit;
}
if ($qty > $stock) {
  $qty = $stock; // limita alla disponibilità
}

// upsert cart_item
$sql = "INSERT INTO cart_item (user_id, product_id, qty)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE qty = LEAST(qty + VALUES(qty), ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiii', $userId, $productId, $qty, $stock);
$stmt->execute();
$stmt->close();

// redirect al carrello
header("Location: {$BASE}/public/cart/view.php");
exit;
