<?php
// public/auth/signup.php
require_once dirname(__DIR__) . '/bootstrap.php';              // ../bootstrap.php
require_once dirname(__DIR__, 2) . '/server/admin_secret.php'; // ../../server/admin_secret.php

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pwd = (string)($_POST['password'] ?? '');
$pwd2 = (string)($_POST['password2'] ?? '');
$want_admin = isset($_POST['want_admin']);
$admin_code = trim($_POST['admin_code'] ?? '');

// POST -> validazione + salvataggio
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
    // esiste già?
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
  <meta charset="utf-8">
  <title>Registrazione</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- stessi CSS del login -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    /* piccolo tocco per il form, come login */
    main.page{ max-width:600px; }
    label{ display:block; margin:.5rem 0 .25rem; font-weight:600; }
    input[type="text"],input[type="email"],input[type="password"]{
      width:100%; height:42px; padding:0 10px; border:1px solid #ccc; border-radius:10px; font-size:16px;
    }
    .helper{ color:#b00; font-size:.9rem; }
    button[type=submit]{
      margin-top:.75rem; padding:10px 16px; border-radius:10px; border:0; background:#029664; color:#fff;
      font-weight:700; cursor:pointer;
    }
    .form-row{ margin-bottom:.5rem; }
  </style>
</head>
<body>
<?php include dirname(__DIR__,2) . "/templates/header/header.html"; ?>
<?php include dirname(__DIR__,2) . "/templates/components/back.php"; ?>

<main class="page">
  <h1>Crea account</h1>

  <?php if (!empty($errors['generic'])): ?>
    <div class="helper"><?= htmlspecialchars($errors['generic']) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="form-row">
      <label>Nome</label>
      <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
      <?php if (!empty($errors['name'])): ?><div class="helper"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
    </div>

    <div class="form-row">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
      <?php if (!empty($errors['email'])): ?><div class="helper"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
    </div>

    <div class="form-row">
      <label>Password</label>
      <input type="password" name="password">
      <?php if (!empty($errors['password'])): ?><div class="helper"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
    </div>

    <div class="form-row">
      <label>Ripeti password</label>
      <input type="password" name="password2">
      <?php if (!empty($errors['password2'])): ?><div class="helper"><?= htmlspecialchars($errors['password2']) ?></div><?php endif; ?>
    </div>

    <div class="form-row" style="display:flex; align-items:center; gap:.5rem;">
      <input type="checkbox" id="want_admin" name="want_admin" <?= $want_admin ? 'checked' : '' ?>>
      <label for="want_admin" style="margin:0; font-weight:600;">Registrami come amministratore</label>
    </div>

    <div id="admin_code_wrap" class="form-row" style="<?= $want_admin ? '' : 'display:none' ?>">
      <label>Codice amministratore</label>
      <input type="password" name="admin_code">
      <?php if (!empty($errors['admin_code'])): ?><div class="helper"><?= htmlspecialchars($errors['admin_code']) ?></div><?php endif; ?>
    </div>

    <button type="submit">Crea account</button>
    <p class="form-row">Hai già un account? <a href="<?= $BASE ?>/public/auth/login.php">Accedi</a></p>
  </form>
</main>

<?php include dirname(__DIR__,2) . "/templates/footer/footer.html"; ?>

<script>
  // toggle campo codice admin
  const cb = document.getElementById('want_admin');
  const wrap = document.getElementById('admin_code_wrap');
  if (cb) cb.addEventListener('change', () => { wrap.style.display = cb.checked ? '' : 'none'; });
</script>
</body>
</html>
