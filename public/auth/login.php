<?php
require_once __DIR__ . '/../bootstrap.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  if ($email === '' || $password === '') {
    $error = 'Inserisci email e password.';
  } else {
    $stmt = $conn->prepare("SELECT id, name, email, role, password_hash FROM `user` WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $ok = false;
    if ($u) {
      if (!empty($u['password_hash']) && password_verify($password, $u['password_hash'])) {
        $ok = true;
      } else {
        $hasLegacy = false;
        if ($res = $conn->query("SHOW COLUMNS FROM `user` LIKE 'password'")) { $hasLegacy = (bool)$res->num_rows; }
        if ($hasLegacy) {
          $stmt2 = $conn->prepare("SELECT `password` FROM `user` WHERE id=? LIMIT 1");
          $stmt2->bind_param('i', $u['id']);
          $stmt2->execute();
          $row2 = $stmt2->get_result()->fetch_assoc();
          $stmt2->close();
          $legacy = $row2['password'] ?? '';
          if ($legacy !== '' && ($legacy === $password || $legacy === md5($password))) {
            $ok = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE `user` SET password_hash=? WHERE id=?");
            $up->bind_param('si', $newHash, $u['id']);
            $up->execute();
            $up->close();
          }
        }
      }
    }
    if ($ok) {
      $_SESSION['user_id']   = (int)$u['id'];
      $_SESSION['user_name'] = $u['name'] ?? '';
      $_SESSION['user_role'] = $u['role'] ?? 'user';
      $next = $_POST['next'] ?? ($BASE . '/public/');
      header("Location: " . $next);
      exit;
    } else {
      $error = 'Credenziali non valide.';
    }
  }
}
$next = $_GET['next'] ?? ($BASE . '/public/'); ?>
<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><title>Accedi • OutdoorTribe</title><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
<link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
<link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
<link rel="stylesheet" href="<?= $BASE ?>/public/styles/auth.css"></head><body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<main class="auth-landing"><section class="auth-split">
<aside class="brand-side"><div class="brand-overlay"><img class="brand-logo" src="<?= $BASE ?>/assets/icons/logo.svg" alt="OutdoorTribe"><h1>OutdoorTribe</h1><p>Esplora. Condividi. Acquista.</p></div></aside>
<section class="form-side"><div class="auth-form-card"><h2>Accedi</h2><?php if ($error): ?><div class="notice"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="auth-form"><input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>"><label>Email</label><input type="email" name="email" required autofocus><label>Password</label><input type="password" name="password" required>
<div class="auth-actions"><button type="submit">Entra</button><a class="btn-secondary" href="<?= $BASE ?>/public/auth/signup.php">Crea un account</a></div>
</form></div></section></section></main>
<?php include __DIR__ . "/../../templates/footer/footer.html"; ?></body></html>
