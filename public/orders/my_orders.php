<?php
// public/orders/my_orders.php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../img_path.php';

$userId = current_user_id();
if ($userId <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }

/* --- ORDINI DELL'UTENTE --- */
$stmt = $conn->prepare("SELECT id, status, total_amount, currency, shipping_cost, vat_amount, grand_total, created_at
                        FROM `order`
                        WHERE user_id=?
                        ORDER BY id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* --- RIGHE ORDINE --- */
$orderItems = [];
if ($orders) {
  $ids = array_map(fn($o)=>(int)$o['id'],$orders);
  $ph = implode(',', array_fill(0,count($ids),'?'));
  $types = str_repeat('i', count($ids));

  $sql = "SELECT oi.order_id, oi.product_id, oi.qty, oi.unit_price, p.title, p.image_filename
          FROM order_item oi
          JOIN product p ON p.id = oi.product_id
          WHERE oi.order_id IN ($ph)
          ORDER BY oi.order_id DESC, oi.id ASC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$ids);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) {
    $orderItems[(int)$r['order_id']][] = $r;
  }
  $stmt->close();
}

/* --- AGGREGATO: PRODOTTI ACQUISTATI --- */
$stmt = $conn->prepare("SELECT p.id, p.title, p.currency,
                               SUM(oi.qty) AS qty_bought,
                               MAX(o.created_at) AS last_purchase
                        FROM order_item oi
                        JOIN `order` o ON o.id = oi.order_id
                        JOIN product p ON p.id = oi.product_id
                        WHERE o.user_id = ?
                        GROUP BY p.id, p.title, p.currency
                        ORDER BY last_purchase DESC, p.id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$purchased = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$lastOrderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>I miei ordini</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .tabs{display:flex; gap:8px; margin:12px 0 16px}
    .tab{padding:8px 12px; border:1px solid #1b5e20; border-radius:10px; cursor:pointer; font-weight:700}
    .tab.active{background:#1b5e20; color:#fff}
    .panel{display:none}
    .panel.active{display:block}
    .order{border:1px solid #ddd;border-radius:10px;margin:12px 0;padding:12px;background:#fff}
    .order h2{margin:0 0 8px 0}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #eee}
    .tot{text-align:right;font-weight:bold}
    .grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(280px,1fr))}
    .card{border:1px solid #e6e6e6;border-radius:10px;padding:12px;background:#fff}
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<section class="container">
  <h1>I miei ordini</h1>

  <?php if ($lastOrderId): ?>
    <div class="ok" style="background:#e6ffed;border:1px solid #b7f5c6;padding:8px;border-radius:8px;margin:12px 0">
      Ordine #<?= (int)$lastOrderId ?> creato correttamente.
    </div>
  <?php endif; ?>

  <div class="tabs">
    <button class="tab active" data-tab="ordini">Ordini</button>
    <button class="tab" data-tab="prodotti">Prodotti acquistati</button>
  </div>

  <!-- Pannello ORDINI -->
  <div id="panel-ordini" class="panel active">
    <?php if (!$orders): ?>
      <p>Non hai ancora effettuato ordini. <a href="<?= $BASE ?>/public/products/list.php">Vai al catalogo</a></p>
    <?php else: ?>
      <?php foreach ($orders as $o): 
        $oid = (int)$o['id'];
        $rows = $orderItems[$oid] ?? [];
        // Se grand_total non è valorizzato, ricalcolo
        $subtotal = 0.0;
        foreach ($rows as $r) { $subtotal += (float)$r['unit_price'] * (int)$r['qty']; }
        $currency = $o['currency'] ?: 'EUR';
        $tot = ($o['grand_total'] !== null && $o['grand_total'] > 0) ? (float)$o['grand_total'] : $subtotal + (float)($o['shipping_cost'] ?? 0) + (float)($o['vat_amount'] ?? 0);
      ?>
      <article class="order">
        <h2>Ordine #<?= $oid ?> · Stato: <?= htmlspecialchars($o['status']) ?> · del <?= htmlspecialchars($o['created_at']) ?></h2>
        <table>
          <thead>
            <tr>
              <th>Prodotto</th>
              <th style="width:120px;">Prezzo</th>
              <th style="width:100px;">Q.tà</th>
              <th style="width:140px;">Totale riga</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): $line=(float)$r['unit_price']*(int)$r['qty']; ?>
            <tr>
              <td><?= htmlspecialchars($r['title']) ?></td>
              <td><?= number_format((float)$r['unit_price'], 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
              <td><?= (int)$r['qty'] ?></td>
              <td><?= number_format($line, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="3" class="tot">Subtotale</td><td class="tot"><?= number_format($subtotal, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td></tr>
            <tr><td colspan="3" class="tot">Spedizione</td><td class="tot"><?= number_format((float)($o['shipping_cost'] ?? 0), 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td></tr>
            <tr><td colspan="3" class="tot">IVA</td><td class="tot"><?= number_format((float)($o['vat_amount'] ?? 0), 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td></tr>
            <tr><td colspan="3" class="tot">Totale ordine</td><td class="tot"><?= number_format($tot, 2, ',', '.') ?> <?= htmlspecialchars($currency) ?></td></tr>
          </tfoot>
        </table>
      </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pannello PRODOTTI ACQUISTATI -->
  <div id="panel-prodotti" class="panel">
    <?php if (!$purchased): ?>
      <p>Nessun prodotto acquistato.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($purchased as $r): ?>
          <div class="card">
            <div style="font-weight:700;"><?= htmlspecialchars($r['title']) ?></div>
            <div>Quantità acquistata: <strong><?= (int)$r['qty_bought'] ?></strong></div>
            <div>Ultimo acquisto: <?= htmlspecialchars($r['last_purchase']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
  document.querySelectorAll('.tab').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
      document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('panel-'+btn.dataset.tab).classList.add('active');
    });
  });
</script>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
