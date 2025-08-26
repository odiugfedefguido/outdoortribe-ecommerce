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
