<?php
// public/cart/remove.php
session_start();
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../../admin/functions.php';
require_once __DIR__ . '/../config_path.php';

if (!isset($_SESSION['user_id'])) { header('Location: /ecommerce_from_outdoortribe/public/auth/login.php'); exit; }

$itemId = intval($_POST['item_id'] ?? 0);
$stmt = $conn->prepare("DELETE FROM cart_item WHERE id=?");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$stmt->close();
header('Location: /ecommerce_from_outdoortribe/public/cart/view.php');
