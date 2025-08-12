<?php
/* 
 * File: public/cart/add.php
 * Scopo: Aggiunge un prodotto al carrello (crea carrello se manca).
 * Stato: IMPLEMENTATO.
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../../admin/functions.php'; // se hai checkLogin()

// Verifica login utente (puoi adattare a come gestisci la sessione)
if (!isset($_SESSION['user_id'])) {
  header('Location: /ecommerce_from_outdoortribe/public/auth/login.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];
$productId = max(1, intval($_POST['product_id'] ?? 0));
$qty = max(1, intval($_POST['qty'] ?? 1));

// 1) Recupera prezzo attuale
$stmt = $conn->prepare("SELECT price FROM product WHERE id=? AND is_active=1");
$stmt->bind_param('i', $productId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { header('Location: /ecommerce_from_outdoortribe/public/products/list.php'); exit; }
$unitPrice = (float)$row['price'];

// 2) Trova/crea carrello
$cartId = null;
$stmt = $conn->prepare("SELECT id FROM cart WHERE user_id=?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($res) {
  $cartId = (int)$res['id'];
} else {
  $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $cartId = $stmt->insert_id;
  $stmt->close();
}

// 3) Inserisci/aggiorna riga
$stmt = $conn->prepare("SELECT id, qty FROM cart_item WHERE cart_id=? AND product_id=?");
$stmt->bind_param('ii', $cartId, $productId);
$stmt->execute();
$ci = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($ci) {
  $newQty = $ci['qty'] + $qty;
  $stmt = $conn->prepare("UPDATE cart_item SET qty=?, unit_price=? WHERE id=?");
  $stmt->bind_param('idi', $newQty, $unitPrice, $ci['id']);
  $stmt->execute();
  $stmt->close();
} else {
  $stmt = $conn->prepare("INSERT INTO cart_item (cart_id, product_id, qty, unit_price) VALUES (?,?,?,?)");
  $stmt->bind_param('iiid', $cartId, $productId, $qty, $unitPrice);
  $stmt->execute();
  $stmt->close();
}

header('Location: /ecommerce_from_outdoortribe/public/cart/view.php');
