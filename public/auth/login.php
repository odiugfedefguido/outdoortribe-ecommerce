<?php
require_once __DIR__ . '/../bootstrap.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  if ($email === '' || $password === '') {
    $error = 'Inserisci email e password.';
  } else {
    $stmt = $conn->prepare("SELECT id, name, email, role, password_hash, password FROM `user` WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $ok = false;
    if ($u) {
      if (!empty($u['password_hash'])) {
        $ok = password_verify($password, $u['password_hash']);
      } elseif (!empty($u['password'])) {
        $ok = hash_equals((string)$u['password'], $password);
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
$next = $_GET['next'] ?? ($BASE . '/public/');
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Accedi</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
    <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<main class="page" style="max-width:600px;">
  <h1>Accedi</h1>
  <?php if ($error !== ''): ?><div class="error" style="color:#b00;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
    <label>Email<br><input type="email" name="email" required></label><br>
    <label>Password<br><input type="password" name="password" required></label><br>
    <button type="submit">Entra</button>
    <p>Non hai un account? <a href="<?= $BASE ?>/public/auth/signup.php">Registrati</a></p>
  </form>
</main>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
