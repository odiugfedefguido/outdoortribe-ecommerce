<?php
session_start();
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../../server/connection.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$productId = (int)($_POST['product_id'] ?? 0);
if ($userId > 0 && $productId > 0) {
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
  $stmt->bind_param('ii', $userId, $productId);
  $stmt->execute();
  $stmt->close();
}
header("Location: /public/cart/view.php");
