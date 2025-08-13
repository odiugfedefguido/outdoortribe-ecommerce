<?php
// public/cart/update.php
session_start();
require_once __DIR__ . '/../../server/connection.php';
if (!isset($_SESSION['user_id'])) { header('Location: /ecommerce_from_outdoortribe/public/auth/login.php'); exit; }

$itemId = intval($_POST['item_id'] ?? 0);
$qty    = max(1, intval($_POST['qty'] ?? 1));
$stmt = $conn->prepare("UPDATE cart_item SET qty=? WHERE id=?");
$stmt->bind_param('ii', $qty, $itemId);
$stmt->execute();
$stmt->close();
header('Location: /ecommerce_from_outdoortribe/public/cart/view.php');
