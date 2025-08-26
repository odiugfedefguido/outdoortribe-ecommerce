<?php
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/email.php';
function notify_sold_out_for_order(mysqli $conn, int $orderId): void {
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
    $pid=(int)$r['product_id']; $ttl=(string)$r['title']; $stock=(int)$r['stock'];
    $sid=(int)($r['seller_id'] ?? 0); $sem=(string)($r['seller_email'] ?? ''); $sna=(string)($r['seller_name'] ?? '');
    if ($stock <= 0 && $sid > 0) {
      $msg = "Il tuo prodotto <strong>" . htmlspecialchars($ttl) . "</strong> è stato venduto completamente (stock: 0).";
      $type= "SOLD_OUT";
      $stmt2 = $conn->prepare("INSERT IGNORE INTO notification (user_id, product_id, type, message) VALUES (?,?,?,?)");
      $stmt2->bind_param('iiss', $sid, $pid, $type, $msg);
      $stmt2->execute();
      $stmt2->close();

      if ($sem !== '') {
        $subject = "Prodotto esaurito: " . $ttl;
        $body = "<p>Ciao " . htmlspecialchars($sna) . ",</p>"
              . "<p>il tuo prodotto <strong>" . htmlspecialchars($ttl) . "</strong> è ora <strong>esaurito</strong> (stock: 0).</p>"
              . "<p>Accedi all'area venditore per gestire il riassortimento.</p>"
              . "<p>— OutdoorTribe</p>";
        send_email_simple($sem, $subject, $body);
      }
    }
  }
}
