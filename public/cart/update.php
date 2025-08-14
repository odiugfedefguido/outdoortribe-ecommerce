<?php
// public/cart/update.php
require_once __DIR__ . '/../bootstrap.php';  // sessione + $BASE + $conn

// Consenti solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: {$BASE}/public/cart/view.php");
  exit;
}

$userId    = current_user_id();
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(0, (int)($_POST['qty'] ?? 0)); // 0 = rimuovi

if ($userId <= 0 || $productId <= 0) {
  header("Location: {$BASE}/public/cart/view.php");
  exit;
}

if ($qty === 0) {
  // rimuovi riga
  $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
  $stmt->bind_param('ii', $userId, $productId);
  $stmt->execute();
  $stmt->close();
} else {
  // limita allo stock e verifica prodotto attivo
  $stmt = $conn->prepare("SELECT stock, is_active FROM product WHERE id=?");
  $stmt->bind_param('i', $productId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $stock = (int)($row['stock'] ?? 0);
  $active = (int)($row['is_active'] ?? 0);

  if ($active !== 1 || $stock <= 0) {
    // prodotto non disponibile: rimuovi dal carrello
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    $stmt->close();
  } else {
    $qty = min($qty, $stock);
    if ($qty <= 0) {
      $stmt = $conn->prepare("DELETE FROM cart_item WHERE user_id=? AND product_id=?");
      $stmt->bind_param('ii', $userId, $productId);
      $stmt->execute();
      $stmt->close();
    } else {
      $stmt = $conn->prepare("UPDATE cart_item SET qty=? WHERE user_id=? AND product_id=?");
      $stmt->bind_param('iii', $qty, $userId, $productId);
      $stmt->execute();
      $stmt->close();
    }
  }
}

header("Location: {$BASE}/public/cart/view.php");
exit;
