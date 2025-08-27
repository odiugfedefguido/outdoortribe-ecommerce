<?php
require_once __DIR__ . '/../bootstrap.php';

$uid  = current_user_id();
$role = current_user_role();

if ($uid <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }
if ($role !== 'admin') { http_response_code(403); echo 'Accesso negato'; exit; }

$stmt = $conn->prepare("
  SELECT id, title, price, currency, stock
  FROM product
  WHERE seller_id=?
  ORDER BY id DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>I miei prodotti</title>

  <!-- CSS corretti come nelle altre pagine -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">

  <style>
    /* qualche rifinitura per la tabella */
    section.page{ margin:70px auto 24px; max-width:1280px; padding:0 16px; }
    table.products{ width:100%; border-collapse:collapse; margin-top:12px; }
    table.products th, table.products td{ padding:10px 12px; border-bottom:1px solid #edf1ef; text-align:left; }
    table.products thead th{ font-weight:700; background:#f6f8f7; color:#22332d; }
    .status-pill{ display:inline-block; padding:2px 10px; border-radius:9999px; font-size:.8rem; font-weight:700; }
    .esaurito{ background:#222; color:#fff; }
    .disp{ background:#eafcf0; color:#0a7a2a; }
    .right{ text-align:right; }
  </style>
</head>
<body>
  <?php include __DIR__ . "/../../templates/header/header.html"; ?>
  <?php include __DIR__ . "/../../templates/components/back.php"; ?>

  <section class="page">
    <h1>I miei prodotti</h1>

    <?php if (!$rows): ?>
      <p>Non hai ancora caricato prodotti.</p>
    <?php else: ?>
      <table class="products">
        <thead>
          <tr>
            <th style="width:80px;">ID</th>
            <th>Titolo</th>
            <th style="width:160px;">Prezzo</th>
            <th style="width:120px;">Stock</th>
            <th style="width:140px;">Stato</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= htmlspecialchars($p['title']) ?></td>
              <td class="right">
                <?= number_format((float)$p['price'], 2, ',', '.') . ' ' . htmlspecialchars($p['currency'] ?: 'EUR') ?>
              </td>
              <td class="right"><?= (int)$p['stock'] ?></td>
              <td>
                <?php if ((int)$p['stock'] <= 0): ?>
                  <span class="status-pill esaurito">Esaurito</span>
                <?php else: ?>
                  <span class="status-pill disp">Disponibile</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
