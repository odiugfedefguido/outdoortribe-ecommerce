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
      if (strpos($next, $BASE . '/') !== 0) { $next = $BASE . '/public/'; }
      header("Location: " . $next); exit;
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

  <!-- CSS globali del sito -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">

  <!-- CSS specifico login (layout immagine a fianco) -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/login.css">

  <!-- (Opzionale) Google Font come nel design di esempio -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . "/../../templates/header/header.html"; ?>
  <!-- NIENTE back.php qui -->

  <main class="auth-login">
    <div class="auth-login__card">
      <!-- lato immagine -->
      <aside class="auth-login__image">
        <img src="<?= $BASE ?>/assets/icons/login.svg" alt="Login" />
      </aside>

      <!-- lato form -->
      <section class="auth-login__form">
        <div class="auth-login__brand">
          <img class="logo" src="<?= $BASE ?>/assets/icons/logo.svg" alt="OutdoorTribe">
        </div>

        <div class="auth-login__intro">
          <p class="intro-hero">Elevate your adventures with OutdoorTribe — where every step is a journey.</p>
          <p class="intro-sub">Welcome back, please login to your account</p>
          <?php if ($error !== ''): ?>
            <p class="intro-error"><?= htmlspecialchars($error) ?></p>
          <?php endif; ?>
        </div>

        <form method="post" class="auth-login__fields">
          <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

          <label class="field">
            <span class="field__label">Email</span>
            <input class="field__input" type="email" name="email" placeholder="mario.rossi@email.com" required>
          </label>

          <label class="field">
            <span class="field__label">Password</span>
            <input class="field__input" type="password" name="password" required>
          </label>

          <div class="auth-login__actions">
            <button type="submit" class="btn-full">Login</button>
            <a class="btn-outline" href="<?= $BASE ?>/public/auth/signup.php">Sign up</a>
          </div>
        </form>
      </section>
    </div>
  </main>

  <?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
