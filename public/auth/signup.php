<?php
require_once dirname(__DIR__) . '/bootstrap.php';              // ../bootstrap.php
require_once dirname(__DIR__, 2) . '/server/admin_secret.php'; // ../../server/admin_secret.php


$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pwd = (string)($_POST['password'] ?? '');
$pwd2 = (string)($_POST['password2'] ?? '');
$want_admin = isset($_POST['want_admin']); // checkbox
$admin_code = trim($_POST['admin_code'] ?? '');

// Se POST: validazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validazioni base
  if ($name === '')   { $errors['name'] = 'Nome richiesto'; }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Email non valida'; }
  if ($pwd === '' || strlen($pwd) < 8) { $errors['password'] = 'Password minima 8 caratteri'; }
  if ($pwd !== $pwd2) { $errors['password2'] = 'Le password non coincidono'; }

  // Se si richiede ruolo admin → il codice è obbligatorio e deve essere corretto
  $role = 'user';
  if ($want_admin) {
    if ($admin_code === '' || !password_verify($admin_code, ADMIN_CODE_HASH)) {
      $errors['admin_code'] = 'Codice amministratore errato.';
    } else {
      $role = 'admin';
    }
  }

  // Se nessun errore → crea utente
  if (!$errors) {
    // Verifica che l'email non esista già
    $stmt = $conn->prepare("SELECT id FROM user WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $errors['email'] = 'Email già registrata';
    }
    $stmt->close();
  }

  if (!$errors) {
    // Inserimento
    $pwd_hash = password_hash($pwd, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $name, $email, $pwd_hash, $role);
    if ($stmt->execute()) {
      // opzionale: login automatico post-signup
      $_SESSION['user_id'] = $stmt->insert_id;
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
  <link href="<?= $BASE ?>/assets/css/main.css" rel="stylesheet">
</head>
<body>
  <main class="container">
    <h1>Crea account</h1>

    <?php if (!empty($errors['generic'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($errors['generic']) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label>Nome</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="form-control">
        <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars($errors['name']) ?></small><?php endif; ?>
      </div>

      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-control">
        <?php if (!empty($errors['email'])): ?><small class="text-danger"><?= htmlspecialchars($errors['email']) ?></small><?php endif; ?>
      </div>

      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control">
        <?php if (!empty($errors['password'])): ?><small class="text-danger"><?= htmlspecialchars($errors['password']) ?></small><?php endif; ?>
      </div>

      <div class="mb-3">
        <label>Ripeti password</label>
        <input type="password" name="password2" class="form-control">
        <?php if (!empty($errors['password2'])): ?><small class="text-danger"><?= htmlspecialchars($errors['password2']) ?></small><?php endif; ?>
      </div>

      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="want_admin" name="want_admin" <?= $want_admin ? 'checked' : '' ?>>
        <label class="form-check-label" for="want_admin">Registrami come amministratore</label>
      </div>

      <div id="admin_code_wrap" class="mb-3" style="<?= $want_admin ? '' : 'display:none' ?>">
        <label>Codice amministratore</label>
        <input type="password" name="admin_code" class="form-control">
        <?php if (!empty($errors['admin_code'])): ?><small class="text-danger"><?= htmlspecialchars($errors['admin_code']) ?></small><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary">Crea account</button>
    </form>
  </main>

  <script>
  // Mostra/nasconde il campo codice admin lato UI (il controllo vero è server-side)
  document.getElementById('want_admin').addEventListener('change', function () {
    document.getElementById('admin_code_wrap').style.display = this.checked ? '' : 'none';
  });
  </script>
</body>
</html>
