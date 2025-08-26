<?php
// public/cart/add.php
require_once __DIR__ . '/../bootstrap.php';  // login obbligatorio + $BASE + $conn

// Consenti solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: {$BASE}/public/products/list.php");
  exit;
}

$userId    = current_user_id();
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 0)); // evita 0 o negativi

if ($userId <= 0 || $productId <= 0) {
  header("Location: {$BASE}/public/products/list.php");
  exit;
}

// Verifica prodotto e stock
$stmt = $conn->prepare("SELECT id, stock, is_active FROM product WHERE id=? LIMIT 1");
$stmt->bind_param('i', $productId);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p || (int)$p['is_active'] !== 1 || (int)$p['stock'] <= 0) {
  header("Location: {$BASE}/public/products/details.php?id={$productId}&err=unavailable");
  exit;
}

$stock = (int)$p['stock'];

// Leggi qty già presente nel carrello
$stmt = $conn->prepare("SELECT qty FROM cart_item WHERE user_id=? AND product_id=?");
$stmt->bind_param('ii', $userId, $productId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$newQty = $qty;
if ($row) {
  $newQty = (int)$row['qty'] + $qty;
}
if ($newQty > $stock) $newQty = $stock;
if ($newQty <= 0) $newQty = 1;

// Upsert manuale
if ($row) {
  $stmt = $conn->prepare("UPDATE cart_item SET qty=? WHERE user_id=? AND product_id=?");
  $stmt->bind_param('iii', $newQty, $userId, $productId);
} else {
  $stmt = $conn->prepare("INSERT INTO cart_item (user_id, product_id, qty) VALUES (?,?,?)");
  $stmt->bind_param('iii', $userId, $productId, $newQty);
}
$stmt->execute();
$stmt->close();

// Redirect al carrello
header("Location: {$BASE}/public/cart/view.php");
exit;
