<?php
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/email.php';

/**
 * Notifica i venditori se un prodotto è andato a stock 0 dopo un ordine.
 * Implementazione semplice e fail-soft: se mancano colonne o tabelle, esce.
 */
function notify_sold_out_for_order(mysqli $conn, int $orderId): void {
  // Verifica tabelle richieste
  $ok1 = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='order_item'");
  $ok2 = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='product'");
  if (!$ok1 || !$ok2 || !$ok1->num_rows || !$ok2->num_rows) return;

  $sql = "SELECT oi.product_id, p.title, p.stock, p.seller_id, u.email AS seller_email, u.name AS seller_name
          FROM order_item oi
          JOIN product p ON p.id = oi.product_id
          LEFT JOIN user u ON u.id = p.seller_id
          WHERE oi.order_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  foreach ($rows as $r) {
    $stock = (int)($r['stock'] ?? -1);
    $email = (string)($r['seller_email'] ?? '');
    $name  = (string)($r['seller_name'] ?? '');
    $title = (string)($r['title'] ?? ('Prodotto #' . (int)$r['product_id']));
    if ($stock === 0 && $email !== '') {
      $subject = "Prodotto esaurito: {$title}";
      $body = "<p>Ciao " . htmlspecialchars($name) . ",</p>"
            . "<p>il tuo prodotto <strong>" . htmlspecialchars($title) . "</strong> è ora <strong>esaurito</strong> (stock: 0).</p>"
            . "<p>Accedi all'area venditore per gestire il riassortimento.</p>"
            . "<p>— OutdoorTribe</p>";
      @send_email_simple($email, $subject, $body);
    }
  }
}


/** Ensure notification table exists (id, user_id, product_id, type, message, link, is_read, created_at) */
function ensure_notification_table(mysqli $conn): void {
  $sql = "CREATE TABLE IF NOT EXISTS `notification` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NULL,
    `type` VARCHAR(50) NOT NULL,
    `message` VARCHAR(255) NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (`user_id`),
    INDEX (`is_read`),
    INDEX (`created_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  @$conn->query($sql);
}

/** Insert a single in-app notification (fail-soft) */
function notif_insert(mysqli $conn, int $userId, string $type, string $message, ?int $productId = null, ?string $link = null): void {
  // ensure table exists
  ensure_notification_table($conn);
  // check existence
  $ok = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='notification'");
  if (!$ok || !$ok->num_rows) return;
  $stmt = $conn->prepare("INSERT INTO notification (user_id, product_id, type, message, link, is_read) VALUES (?, ?, ?, ?, ?, 0)");
  $pid = $productId ?: null;
  $stmt->bind_param('iisss', $userId, $pid, $type, $message, $link);
  $stmt->execute();
  $stmt->close();
}

/** Notify each seller for each item sold in an order */
function notify_sellers_items_sold_for_order(mysqli $conn, int $orderId, string $base): void {
  $sql = "SELECT oi.product_id, oi.qty, p.title, p.seller_id
          FROM order_item oi
          JOIN product p ON p.id = oi.product_id
          WHERE oi.order_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  foreach ($rows as $r) {
    $sellerId = (int)($r['seller_id'] ?? 0);
    if ($sellerId > 0) {
      $pid = (int)$r['product_id'];
      $qty = (int)$r['qty'];
      $title = (string)($r['title'] ?? ('Prodotto #' . $pid));
      $msg = "Hai venduto {$qty}× {$title}";
      $link = rtrim($base, '/') . "/public/products/details.php?id=" . $pid;
      notif_insert($conn, $sellerId, 'product_sold', $msg, $pid, $link);
    }
  }
}

/** Notify sellers AND cart owners when any ordered product is now out of stock */
function notify_when_sold_out_for_order(mysqli $conn, int $orderId, string $base): void {
  // Sellers (email legacy + in-app)
  notify_sold_out_for_order($conn, $orderId); // keep existing email behavior

  // In-app sellers + cart owners
  $sql = "SELECT oi.product_id, p.title, p.stock, p.seller_id
          FROM order_item oi
          JOIN product p ON p.id = oi.product_id
          WHERE oi.order_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  foreach ($rows as $r) {
    $pid = (int)$r['product_id'];
    $title = (string)($r['title'] ?? ('Prodotto #' . $pid));
    $stock = (int)($r['stock'] ?? -1);
    $sellerId = (int)($r['seller_id'] ?? 0);
    $link = rtrim($base, '/') . "/public/products/details.php?id=" . $pid;

    if ($stock === 0) {
      if ($sellerId > 0) {
        notif_insert($conn, $sellerId, 'product_sold_out', "Prodotto esaurito: {$title}", $pid, $link);
      }
      // notify all users that have this product in cart
      $q = $conn->prepare("SELECT DISTINCT user_id FROM cart_item WHERE product_id=?");
      $q->bind_param('i', $pid);
      $q->execute();
      $rs = $q->get_result();
      $users = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
      $q->close();
      foreach ($users as $u) {
        $uid = (int)$u['user_id'];
        if ($uid > 0) {
          notif_insert($conn, $uid, 'cart_item_sold_out', "Un prodotto nel tuo carrello è esaurito: {$title}", $pid, $link);
        }
      }
    }
  }
}
