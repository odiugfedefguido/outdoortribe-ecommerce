<?php
// public/notifications/index.php
require_once __DIR__ . '/../bootstrap.php'; // session + $BASE + $conn
$userId = current_user_id();
if ($userId <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }

// Lista notifiche più recenti dell'utente
$stmt = $conn->prepare("SELECT id, product_id, type, message, is_read, created_at FROM notification WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/mountain.svg">

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Notifiche</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/notification.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<?php include __DIR__ . "/../../templates/components/back.php"; ?>

<section class="container">
  <h1>Notifiche</h1>
  <?php if (!$rows): ?>
    <p>Nessuna notifica.</p>
  <?php else: ?>
    <ul class="notifications">
      <?php foreach ($rows as $n): ?>
        <li class="notif <?= $n['is_read'] ? 'read' : 'unread' ?>">
          <div class="msg"><?= $n['message'] ?></div>
          <div class="meta">
            <small><?= htmlspecialchars($n['type']) ?> • <?= htmlspecialchars($n['created_at']) ?></small>
            <?php if (!$n['is_read']): ?>
              <form method="post" action="<?= $BASE ?>/public/notifications/mark_read.php" style="display:inline">
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                <button class="btn-secondary">Segna come letta</button>
              </form>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
