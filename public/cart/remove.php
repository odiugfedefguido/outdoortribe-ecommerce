<?php
// public/cart/remove.php
require_once __DIR__ . '/../bootstrap.php';  // sessione + $BASE + $conn

// Consenti solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: {$BASE}/public/cart/view.php");
  exit;
}

$userId    = current_user_id();
$productId = (int)($_POST['product_id'] ?? 0);

if ($userId > 0 && $productId > 0) {
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
  $stmt->bind_param('ii', $userId, $productId);
  $stmt->execute();
  $stmt->close();
}

header("Location: {$BASE}/public/cart/view.php");
exit;
