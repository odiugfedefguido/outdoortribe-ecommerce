<?php
require_once __DIR__ . '/../bootstrap.php'; // consentito via allowlist nel bootstrap
if (current_user_id() > 0) { header("Location: {$BASE}/public/"); exit; }

// Carica il codice segreto admin (file NON pubblico)
require_once __DIR__ . '/../../server/app_config.php';

$error = '';
// Valori sticky per ripopolare la form in caso di errore
$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');
$role_request = $_POST['role'] ?? 'user'; // 'user' | 'admin'
$admin_code_input = $_POST['admin_code'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password  = (string)($_POST['password']  ?? '');
  $password2 = (string)($_POST['password2'] ?? '');

  // Validazioni minime
  if ($name==='' || $email==='' || $password==='' || $password2==='') {
    $error = 'Compila tutti i campi obbligatori.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Email non valida.';
  } elseif ($password !== $password2) {
    $error = 'Le password non coincidono.';
  } else {
    // Se richiesta admin, valida il codice
    $role = 'user';
    if ($role_request === 'admin') {
      if (!isset($ADMIN_SIGNUP_CODE) || $ADMIN_SIGNUP_CODE === '') {
        $error = 'Codice admin non configurato. Contatta il responsabile.';
      } elseif (!hash_equals($ADMIN_SIGNUP_CODE, (string)$admin_code_input)) {
        $error = 'Codice admin non valido.';
      } else {
        $role = 'admin';
      }
    }

    // Se non ci sono errori, procedi
    if ($error === '') {
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
  <style>
    .role-box { display:flex; gap:12px; align-items:center; margin:8px 0; }
    .admin-code { display:none; margin-top:8px; }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<section class="container">
  <h1>Crea un account</h1>
  <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="post">
    <label>Nome e cognome</label>
    <input name="name" required maxlength="120" value="<?= htmlspecialchars($name) ?>">

    <label>Email</label>
    <input type="email" name="email" required maxlength="190" value="<?= htmlspecialchars($email) ?>">

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Conferma password</label>
    <input type="password" name="password2" required>

    <div class="role-box">
      <label style="margin:0;">Ruolo:</label>
      <label><input type="radio" name="role" value="user"  <?= $role_request!=='admin' ? 'checked' : '' ?>> Utente</label>
      <label><input type="radio" name="role" value="admin" <?= $role_request==='admin' ? 'checked' : '' ?>> Admin</label>
    </div>

    <div class="admin-code" id="adminCodeWrap">
      <label>Codice admin</label>
      <input name="admin_code" id="adminCode" placeholder="Inserisci il codice segreto" value="<?= htmlspecialchars($admin_code_input) ?>">
      <small class="muted">Richiesto solo se vuoi registrare un account amministratore.</small>
    </div>

    <div class="actions" style="margin-top:10px;">
      <button type="submit">Registrati</button>
      <a class="btn-secondary" href="<?= $BASE ?>/public/auth/login.php">Hai già un account?</a>
    </div>
  </form>
</section>

<script>
  // Mostra/nasconde il campo "Codice admin" in base al ruolo scelto
  (function(){
    const radios = document.querySelectorAll('input[name="role"]');
    const wrap   = document.getElementById('adminCodeWrap');
    function sync(){
      const val = document.querySelector('input[name="role"]:checked')?.value;
      wrap.style.display = (val === 'admin') ? 'block' : 'none';
    }
    radios.forEach(r => r.addEventListener('change', sync));
    sync(); // init
  })();
</script>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
