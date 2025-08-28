<?php
// public/notifications/index.php
require_once __DIR__ . '/../bootstrap.php';
$userId = current_user_id();
if ($userId <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }

// Notifiche utente
$sql = "SELECT id, product_id, type, message, link, is_read, created_at
        FROM notification
        WHERE user_id=?
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) { die('Prepare failed: ' . $conn->error); }
$stmt->bind_param('i', $userId);
$stmt->execute();
$res  = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// opzionale: titolo umano
function humanize_type(string $t): string {
  $t = trim(strtolower(str_replace(['_','-'],' ', $t)));
  return $t ? ucfirst($t) : 'Notifica';
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Notifiche</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/mountain.svg">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/orders.css"><!-- riuso stile card ordini -->
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">
  <style>
    section.page{ margin:70px auto 24px; max-width:1280px; width:100%; padding:0 16px; }
    .orders-grid{ display:grid; grid-template-columns:1fr; gap:18px; }
    @media (min-width:720px){ .orders-grid{ grid-template-columns:1fr 1fr; } }
    .btn-link, .btn-mark-read, .btn-delete{
      display:inline-block; padding:6px 10px; border-radius:10px; border:1px solid rgba(0,0,0,.12);
      text-decoration:none; font-weight:600; background:#fff; cursor:pointer;
    }
    .btn-mark-read{ background:#ecf4ff; }
    .btn-delete{ background:#ffecec; }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="page">
  <h1>Notifiche</h1>

  <?php if (empty($rows)): ?>
    <p class="muted">Non hai notifiche.</p>
  <?php else: ?>
    <div class="orders orders-grid">
      <?php foreach ($rows as $n): ?>
        <?php
          $nid   = (int)$n['id'];
          $pid   = (int)($n['product_id'] ?? 0);
          $type  = (string)($n['type'] ?? '');
          $title = humanize_type($type);
          $msg   = (string)($n['message'] ?? '');
          $isr   = (int)($n['is_read'] ?? 0) === 1;
          $when  = (string)($n['created_at'] ?? '');
          $prodUrl = $pid > 0 ? ($BASE . "/public/products/details.php?id=" . $pid) : '';
        ?>
        <article class="order-card notif-card <?= $isr ? 'read' : 'unread' ?>">
          <div class="order-head">
            <div class="order-title">
              <?= htmlspecialchars($title) ?>
              <?php if (!$isr): ?><span class="badge">Non letta</span><?php endif; ?>
            </div>
            <div class="order-total"><?= htmlspecialchars($when) ?></div>
          </div>

          <div class="order-meta">
            <?php if ($pid > 0): ?>
              <span>Prodotto: </span>
              <a href="<?= htmlspecialchars($prodUrl) ?>" class="link">#<?= $pid ?></a>
            <?php endif; ?>
          </div>

          <div class="order-body">
            <table class="table">
              <thead>
                <tr>
                  <th>Messaggio</th>
                  <th class="right">Azione</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><?= htmlspecialchars($msg) ?></td>
                  <td class="right">
                    <div style="display:flex; gap:.5rem; justify-content:flex-end; flex-wrap:wrap;">
                      <?php if ($prodUrl): ?>
                        <a href="<?= htmlspecialchars($prodUrl) ?>" class="btn-link">Vedi prodotto</a>
                      <?php endif; ?>
                      <form method="post" action="<?= $BASE ?>/public/notifications/delete.php" style="display:inline">
                        <input type="hidden" name="id" value="<?= $nid ?>">
                        <button type="submit" class="btn-delete">Elimina</button>
                      </form>
                      <?php if (!$isr): ?>
                        <form method="post" action="<?= $BASE ?>/public/notifications/mark_read.php" style="display:inline">
                          <input type="hidden" name="id" value="<?= $nid ?>">
                          <button type="submit" class="btn-mark-read">Segna come letta</button>
                        </form>
                      <?php else: ?>
                        <small class="muted">Letta</small>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              </tbody>
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
