<?php
// public/notifications/index.php
require_once __DIR__ . '/../bootstrap.php'; // session + $BASE + $conn
$userId = current_user_id();
if ($userId <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }

// Lista notifiche più recenti dell'utente
$stmt = $conn->prepare("
  SELECT id, product_id, type, message, is_read, created_at
  FROM notification
  WHERE user_id=?
  ORDER BY created_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/logo.svg">

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OutdoorTribe · Notifiche</title>

  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="page">
  <h1>Notifiche</h1>

  <?php if (empty($rows)): ?>
    <p class="muted">Non hai notifiche.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Notifica</th>
          <th style="width:180px;">Data</th>
          <th style="width:160px;">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $n):
          $isUnread = (int)$n['is_read'] === 0;
          $message  = htmlspecialchars($n['message'] ?? '');
          $type     = htmlspecialchars($n['type'] ?? '');
          $created  = htmlspecialchars($n['created_at'] ?? '');
          $pid      = (int)($n['product_id'] ?? 0);
        ?>
        <tr>
          <td>
            <?php if ($pid > 0): ?>
              <a href="<?= $BASE ?>/public/products/details.php?id=<?= $pid ?>">
                <?= $isUnread ? '<strong>'.$message.'</strong>' : $message ?>
              </a>
            <?php else: ?>
              <?= $isUnread ? '<strong>'.$message.'</strong>' : $message ?>
            <?php endif; ?>
            <div><small class="muted"><?= $type ?></small></div>
          </td>
          <td><small class="muted"><?= $created ?></small></td>
          <td>
            <?php if ($isUnread): ?>
              <form method="post" action="<?= $BASE ?>/public/notifications/mark_read.php" style="display:inline">
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                <button class="btn-secondary">Segna come letta</button>
              </form>
            <?php else: ?>
              <small class="muted">Letta</small>
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
