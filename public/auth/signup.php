<?php
require_once __DIR__ . '/../bootstrap.php';
if (current_user_id() > 0) { header("Location: {$BASE}/public/"); exit; }

// Codice segreto admin (NON pubblico)
require_once __DIR__ . '/../../server/app_config.php';

$error = '';

// Sticky values
$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');
$role_request = $_POST['role'] ?? 'user'; // 'user' | 'admin'
$admin_code_input = $_POST['admin_code'] ?? '';

$phone        = trim($_POST['phone'] ?? '');
$ship_address = trim($_POST['ship_address'] ?? '');
$ship_city    = trim($_POST['ship_city'] ?? '');
$ship_zip     = trim($_POST['ship_zip'] ?? '');
$ship_country = trim($_POST['ship_country'] ?? 'Italia');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password  = (string)($_POST['password']  ?? '');
  $password2 = (string)($_POST['password2'] ?? '');

  if ($name==='' || $email==='' || $password==='' || $password2==='') {
    $error = 'Compila tutti i campi obbligatori.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Email non valida.';
  } elseif ($password !== $password2) {
    $error = 'Le password non coincidono.';
  } else {
    // ruolo richiesto
    $role = 'user';
    if ($role_request === 'admin') {
      if (!isset($ADMIN_SIGNUP_CODE) || $ADMIN_SIGNUP_CODE === '') {
        $error = 'Codice admin non configurato.';
      } elseif (!hash_equals($ADMIN_SIGNUP_CODE, (string)$admin_code_input)) {
        $error = 'Codice admin non valido.';
      } else {
        $role = 'admin';
      }
    }

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
        $stmt = $conn->prepare("
          INSERT INTO `user`
          (name,email,password_hash,role,phone,ship_address,ship_city,ship_zip,ship_country)
          VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param('sssssssss',
          $name, $email, $hash, $role, $phone, $ship_address, $ship_city, $ship_zip, $ship_country
        );
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
  <title>Registrati • OutdoorTribe</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Stili globali + stile auth “social” -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/auth.css">
</head>
<body>

<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<main class="auth-landing">
  <section class="auth-split">
    <!-- Colonna brand (sinistra) -->
    <aside class="brand-side">
      <div class="brand-overlay">
        <img class="brand-logo" src="<?= $BASE ?>/assets/icons/logo.svg" alt="OutdoorTribe">
        <h1>Unisciti alla community</h1>
        <p>Crea il tuo account e prepara la prossima avventura.</p>
      </div>
    </aside>

    <!-- Colonna form (destra) -->
    <section class="form-side">
      <div class="auth-form-card">
        <h2>Crea un account</h2>
        <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" class="auth-form">
          <div style="display:grid;gap:10px;grid-template-columns:1fr;">
            <div>
              <label>Nome e cognome</label>
              <input name="name" required maxlength="120" value="<?= htmlspecialchars($name) ?>">
            </div>
            <div>
              <label>Email</label>
              <input type="email" name="email" required maxlength="190" value="<?= htmlspecialchars($email) ?>">
            </div>
            <div>
              <label>Password</label>
              <input type="password" name="password" required>
            </div>
            <div>
              <label>Conferma password</label>
              <input type="password" name="password2" required>
            </div>

            <div>
              <label>Telefono</label>
              <input name="phone" maxlength="40" value="<?= htmlspecialchars($phone) ?>">
            </div>
            <div>
              <label>CAP</label>
              <input name="ship_zip" maxlength="20" value="<?= htmlspecialchars($ship_zip) ?>">
            </div>
            <div>
              <label>Indirizzo</label>
              <input name="ship_address" maxlength="200" value="<?= htmlspecialchars($ship_address) ?>">
            </div>
            <div>
              <label>Città</label>
              <input name="ship_city" maxlength="120" value="<?= htmlspecialchars($ship_city) ?>">
            </div>
            <div>
              <label>Nazione</label>
              <input name="ship_country" maxlength="120" value="<?= htmlspecialchars($ship_country ?: 'Italia') ?>">
            </div>
          </div>

          <div class="role-box" style="display:flex;gap:12px;align-items:center;margin:8px 0;">
            <label style="margin:0;">Ruolo:</label>
            <label><input type="radio" name="role" value="user"  <?= $role_request!=='admin' ? 'checked' : '' ?>> Utente</label>
            <label><input type="radio" name="role" value="admin" <?= $role_request==='admin' ? 'checked' : '' ?>> Admin</label>
          </div>

          <div class="admin-code" id="adminCodeWrap" style="display:none;margin-top:8px;">
            <label>Codice admin</label>
            <input name="admin_code" id="adminCode" placeholder="Inserisci il codice segreto" value="<?= htmlspecialchars($admin_code_input) ?>">
            <small class="muted">Richiesto solo se vuoi registrare un account amministratore.</small>
          </div>

          <div class="auth-actions" style="margin-top:12px;">
            <button type="submit">Registrati</button>
            <a class="btn-secondary" href="<?= $BASE ?>/public/auth/login.php">Hai già un account?</a>
          </div>

          <!-- Spazio di sicurezza per non far coprire i bottoni dal footer su schermi bassi -->
          <div style="height:64px"></div>
        </form>
      </div>
    </section>
  </section>
</main>

<script>
  // Mostra/nasconde il campo "Codice admin" in base al ruolo scelto
  (function(){
    const wrap = document.getElementById('adminCodeWrap');
    function sync(){
      const val = document.querySelector('input[name="role"]:checked')?.value;
      wrap.style.display = (val === 'admin') ? 'block' : 'none';
    }
    document.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', sync));
    sync();
  })();
</script>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
