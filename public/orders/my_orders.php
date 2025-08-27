<?php
// public/orders/my_orders.php
require_once __DIR__ . '/../bootstrap.php';

/* ---------- Utils ---------- */
function bt(string $name): string { return '`' . str_replace('`','``',$name) . '`'; }
function pick_table(mysqli $conn, array $candidates): string {
  $names = implode("','", array_map([$conn,'real_escape_string'], $candidates));
  $sql = "SELECT table_name FROM information_schema.tables
          WHERE table_schema = DATABASE() AND table_name IN ('$names') LIMIT 1";
  if ($r = $conn->query($sql)) {
    $row = $r->fetch_assoc();
    if ($row && in_array($row['table_name'], $candidates, true)) return $row['table_name'];
  }
  return $candidates[0];
}
function col_exists(mysqli $conn, string $table, string $col): bool {
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($col);
  $sql = "SELECT 1 FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name='$t' AND column_name='$c' LIMIT 1";
  $r = $conn->query($sql);
  return $r && $r->num_rows > 0;
}
function pick_col_expr(mysqli $conn, string $table, array $candidates, string $alias, string $defaultExpr): string {
  foreach ($candidates as $c) {
    if (col_exists($conn, $table, $c)) return bt($c) . " AS " . bt($alias);
  }
  return "$defaultExpr AS " . bt($alias);
}

/* ---------- Tabelle reali ---------- */
$ordersTbl = pick_table($conn, ['orders','order']);
$itemsTbl  = pick_table($conn, ['order_items','order_item']);
$O = bt($ordersTbl);
$I = bt($itemsTbl);

/* ---------- SELECT ordini con sole colonne esistenti ---------- */
$statusExpr   = pick_col_expr($conn, $ordersTbl, ['status'],                         'status',     "'placed'");
$subtotalExpr = pick_col_expr($conn, $ordersTbl, ['subtotal','subtotal_price'],      'subtotal',   "0");
$shipExpr     = pick_col_expr($conn, $ordersTbl, ['shipping','shipping_price','shipping_cost'], 'shipping', "0");
$taxExpr      = pick_col_expr($conn, $ordersTbl, ['tax','tax_price','vat'],          'tax',        "0");
$totalExpr    = pick_col_expr($conn, $ordersTbl, ['total','total_price','grand_total'], 'total',   "0");
$createdExpr  = pick_col_expr($conn, $ordersTbl, ['created_at','placed_at'],         'created_at', "NOW()");

$sqlOrders = "
  SELECT
    id,
    $statusExpr,
    $subtotalExpr,
    $shipExpr,
    $taxExpr,
    $totalExpr,
    $createdExpr
  FROM $O
  WHERE user_id=?
  ORDER BY id DESC
";

$uid = current_user_id();
$stmt = $conn->prepare($sqlOrders);
$stmt->bind_param('i', $uid);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ---------- Righe ordine ---------- */
function fetch_items(mysqli $conn, string $itemsTbl, int $orderId): array {
  $titleExpr = pick_col_expr($conn, $itemsTbl, ['title','product_title','title_snapshot','name'], 'title', "''");
  $qtyExpr   = pick_col_expr($conn, $itemsTbl, ['qty','quantity','qty_ordered'],                 'qty',   "0");
  $priceExpr = pick_col_expr($conn, $itemsTbl, ['price','unit_price','price_snapshot','unitPrice'],'price', "0");
  $I = bt($itemsTbl);
  $sql = "SELECT $titleExpr, $qtyExpr, $priceExpr FROM $I WHERE order_id=? ORDER BY id ASC";
  $st = $conn->prepare($sql);
  $st->bind_param('i', $orderId);
  $st->execute();
  $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  $st->close();
  return $rows;
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>I miei ordini</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/orders.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css"><!-- per la freccia -->

  <!-- Forza griglia responsive anche se altri CSS provano a sovrascrivere -->
  <style>
    section.page{ margin:70px auto 24px; max-width:1280px; width:100%; padding:0 16px; }
    .orders-grid{
      display:grid !important;
      grid-template-columns:repeat(auto-fill,minmax(320px,1fr)) !important;
      gap:18px !important; align-items:stretch;
    }
    .orders-grid .order-card{ width:100% !important; max-width:none !important; margin:0 !important; }
    @media (max-width:560px){ .orders-grid{ grid-template-columns:1fr !important; } }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>  <!-- <<< FRECCIA INDietro QUI -->

<section class="page">
  <h1>I miei ordini</h1>

  <?php if (empty($orders)): ?>
    <p>Non hai ancora effettuato ordini.</p>
  <?php else: ?>
    <div class="orders orders-grid">
      <?php foreach ($orders as $o): ?>
        <?php
          $oid      = (int)$o['id'];
          $status   = strtolower(trim((string)$o['status']));
          $subtotal = (float)$o['subtotal'];
          $ship     = (float)$o['shipping'];
          $tax      = (float)$o['tax'];
          $total    = (float)$o['total'];
          $created  = (string)$o['created_at'];
          $items    = fetch_items($conn, $itemsTbl, $oid);

          if (($subtotal <= 0 || $total <= 0) && $items) {
            $subtotal = 0.0;
            foreach ($items as $it) { $subtotal += (float)$it['price'] * (int)$it['qty']; }
            if ($total <= 0) $total = $subtotal + $ship + $tax;
          }
          $badgeClass = in_array($status, ['placed','paid','shipped','canceled'], true) ? $status : 'placed';
        ?>
        <article class="order-card">
          <div class="order-head">
            <div class="order-title">
              Ordine #<?= $oid ?> · <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
            </div>
            <div class="order-total"><?= number_format($total, 2, ',', '.') ?> EUR</div>
          </div>

          <div class="order-meta">
            del <?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($created))) ?>
          </div>

          <div class="order-body">
            <table class="table">
              <thead>
                <tr>
                  <th>Prodotto</th>
                  <th class="right">Qtà</th>
                  <th class="right">Prezzo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><?= htmlspecialchars($it['title']) ?></td>
                    <td class="right"><?= (int)$it['qty'] ?></td>
                    <td class="right"><?= number_format((float)$it['price'], 2, ',', '.') ?> EUR</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td>Subtotale</td>
                  <td></td>
                  <td class="right"><?= number_format($subtotal, 2, ',', '.') ?> EUR</td>
                </tr>
                <tr>
                  <td>Spedizione</td>
                  <td></td>
                  <td class="right"><?= number_format($ship, 2, ',', '.') ?> EUR</td>
                </tr>
                <tr>
                  <td>IVA</td>
                  <td></td>
                  <td class="right"><?= number_format($tax, 2, ',', '.') ?> EUR</td>
                </tr>
                <tr>
                  <td><strong>Totale</strong></td>
                  <td></td>
                  <td class="right"><strong><?= number_format($total, 2, ',', '.') ?> EUR</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
