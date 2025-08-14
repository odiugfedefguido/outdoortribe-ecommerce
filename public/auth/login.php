<?php
require_once __DIR__ . '/../bootstrap.php'; // consentito via allowlist
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $error = 'Inserisci email e password.';
  } else {
    $stmt = $conn->prepare("SELECT id, name, email, password_hash, role FROM `user` WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u && password_verify($password, $u['password_hash'])) {
      $_SESSION['user_id']   = (int)$u['id'];
      $_SESSION['user_name'] = $u['name'] ?? '';
      $_SESSION['user_role'] = $u['role'] ?? 'user';

      $next = $_GET['next'] ?? $_POST['next'] ?? ($BASE . '/public/');
      header("Location: " . $next);
      exit;
    } else {
      $error = 'Credenziali non valide.';
    }
  }
}
$next = $_GET['next'] ?? ($BASE . '/public/');
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<section class="container">
  <h1>Accedi</h1>
  <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
    <label>Email</label>
    <input type="email" name="email" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <div class="actions" style="margin-top:10px;">
      <button type="submit">Entra</button>
      <a class="btn-secondary" href="<?= $BASE ?>/public/auth/signup.php">Registrati</a>
    </div>
  </form>
</section>
<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
