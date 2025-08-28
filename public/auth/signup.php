<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/server/admin_secret.php';

$errors = [];
$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pwd   = (string)($_POST['password'] ?? '');
$pwd2  = (string)($_POST['password2'] ?? '');
$want_admin = isset($_POST['want_admin']);
$admin_code = trim($_POST['admin_code'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($name === '') { $errors['name'] = 'Nome richiesto'; }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Email non valida'; }
  if ($pwd === '' || strlen($pwd) < 8) { $errors['password'] = 'Password minima 8 caratteri'; }
  if ($pwd !== $pwd2) { $errors['password2'] = 'Le password non coincidono'; }

  $role = 'user';
  if ($want_admin) {
    if ($admin_code === '' || !password_verify($admin_code, ADMIN_CODE_HASH)) {
      $errors['admin_code'] = 'Codice amministratore errato.';
    } else {
      $role = 'admin';
    }
  }

  if (!$errors) {
    $stmt = $conn->prepare("SELECT id FROM user WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $errors['email'] = 'Email già registrata'; }
    $stmt->close();
  }

  if (!$errors) {
    $pwd_hash = password_hash($pwd, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $name, $email, $pwd_hash, $role);
    if ($stmt->execute()) {
      $_SESSION['user_id']   = $stmt->insert_id;
      $_SESSION['user_role'] = $role;
      header("Location: {$BASE}/public/");
      exit;
    } else {
      $errors['generic'] = 'Errore in registrazione. Riprova.';
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/mountain.svg">

  <meta charset="utf-8">
  <title>Registrazione</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- CSS globali -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">

  <!-- Stesso stile del login -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/login.css">

  <!-- Font (opzionale) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="auth-no-header">

  <!-- NIENTE header/back qui -->

  <main class="auth-login">
    <div class="auth-login__card">
      <aside class="auth-login__image">
        <img src="<?= $BASE ?>/assets/icons/login.svg" alt="Signup">
      </aside>

      <section class="auth-login__form">
        <div class="auth-login__brand">
          <img class="logo" src="<?= $BASE ?>/assets/icons/logo.svg" alt="OutdoorTribe">
        </div>

        <div class="auth-login__intro">
          <p class="intro-hero">Join OutdoorTribe — ogni avventura inizia qui.</p>
          <p class="intro-sub">Crea il tuo account per iniziare</p>
          <?php if (!empty($errors['generic'])): ?>
            <p class="intro-error"><?= htmlspecialchars($errors['generic']) ?></p>
          <?php endif; ?>
        </div>

        <form method="post" class="auth-login__fields">
          <label class="field">
            <span class="field__label">Nome</span>
            <input class="field__input" type="text" name="name" value="<?= htmlspecialchars($name) ?>">
            <?php if (!empty($errors['name'])): ?><small class="intro-error"><?= htmlspecialchars($errors['name']) ?></small><?php endif; ?>
          </label>

          <label class="field">
            <span class="field__label">Email</span>
            <input class="field__input" type="email" name="email" value="<?= htmlspecialchars($email) ?>">
            <?php if (!empty($errors['email'])): ?><small class="intro-error"><?= htmlspecialchars($errors['email']) ?></small><?php endif; ?>
          </label>

          <label class="field">
            <span class="field__label">Password</span>
            <input class="field__input" type="password" name="password">
            <?php if (!empty($errors['password'])): ?><small class="intro-error"><?= htmlspecialchars($errors['password']) ?></small><?php endif; ?>
          </label>

          <label class="field">
            <span class="field__label">Ripeti password</span>
            <input class="field__input" type="password" name="password2">
            <?php if (!empty($errors['password2'])): ?><small class="intro-error"><?= htmlspecialchars($errors['password2']) ?></small><?php endif; ?>
          </label>

          <div class="field" style="display:flex; align-items:center; gap:.5rem; margin-top:6px;">
            <input type="checkbox" id="want_admin" name="want_admin" <?= $want_admin ? 'checked' : '' ?>>
            <label for="want_admin" class="field__label" style="margin:0;">Registrami come amministratore</label>
          </div>

          <div id="admin_code_wrap" class="field" style="<?= $want_admin ? '' : 'display:none' ?>">
            <span class="field__label">Codice amministratore</span>
            <input class="field__input" type="password" name="admin_code">
            <?php if (!empty($errors['admin_code'])): ?><small class="intro-error"><?= htmlspecialchars($errors['admin_code']) ?></small><?php endif; ?>
          </div>

          <div class="auth-login__actions">
            <button type="submit" class="btn-full">Crea account</button>
            <a class="btn-outline" href="<?= $BASE ?>/public/auth/login.php">Hai già un account? Accedi</a>
          </div>
        </form>
      </section>
    </div>
  </main>

  <?php include dirname(__DIR__,2) . "/templates/footer/footer.html"; ?>

  <script>
    const cb = document.getElementById('want_admin');
    const wrap = document.getElementById('admin_code_wrap');
    if (cb) cb.addEventListener('change', () => { wrap.style.display = cb.checked ? '' : 'none'; });
  </script>
</body>
</html>
