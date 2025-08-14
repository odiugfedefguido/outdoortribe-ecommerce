<?php
require_once __DIR__ . '/../bootstrap.php'; // consentito via allowlist
if (current_user_id() > 0) { header("Location: {$BASE}/public/"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $password2 = (string)($_POST['password2'] ?? '');

  if ($name==='' || $email==='' || $password==='' || $password2==='') {
    $error = 'Compila tutti i campi.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Email non valida.';
  } elseif ($password !== $password2) {
    $error = 'Le password non coincidono.';
  } else {
    // email unica?
    $stmt = $conn->prepare("SELECT id FROM `user` WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
      $error = 'Email già registrata.';
    } else {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $role = 'user';
      $stmt = $conn->prepare("INSERT INTO `user` (name,email,password_hash,role) VALUES (?,?,?,?)");
      $stmt->bind_param('ssss', $name, $email, $hash, $role);
      $stmt->execute();
      $uid = (int)$stmt->insert_id;
      $stmt->close();

      // login automatico
      $_SESSION['user_id']   = $uid;
      $_SESSION['user_name'] = $name;
      $_SESSION['user_role'] = $role;

      header("Location: {$BASE}/public/");
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Registrazione</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<section class="container">
  <h1>Crea un account</h1>
  <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Nome e cognome</label>
    <input name="name" required maxlength="120">
    <label>Email</label>
    <input type="email" name="email" required maxlength="190">
    <label>Password</label>
    <input type="password" name="password" required>
    <label>Conferma password</label>
    <input type="password" name="password2" required>
    <div class="actions" style="margin-top:10px;">
      <button type="submit">Registrati</button>
      <a class="btn-secondary" href="<?= $BASE ?>/public/auth/login.php">Hai già un account?</a>
    </div>
  </form>
</section>
<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
