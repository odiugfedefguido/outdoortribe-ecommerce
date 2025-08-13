<?php
/*
 * File: public/cart/remove.php
 * Scopo: Rimuove una riga dal carrello.
 * Stato: IMPLEMENTATO.
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../../admin/functions.php';
require_once __DIR__ . '/../config_path.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: ' . $BASE . '/public/auth/login.php');
  exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
if ($itemId > 0) {
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE id=?");
  $stmt->bind_param('i', $itemId);
  $stmt->execute();
  $stmt->close();
}

header('Location: ' . $BASE . '/public/cart/view.php');
