<?php
// public/auth/login.php
require_once __DIR__ . '/../bootstrap.php'; // (consentito in allowlist nel bootstrap)

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
  <title>Accedi • OutdoorTribe</title>
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
        <h1>OutdoorTribe</h1>
        <p>Esplora. Condividi. Acquista attrezzatura per le tue avventure.</p>
      </div>
    </aside>

    <!-- Colonna form (destra) -->
    <section class="form-side">
      <div class="auth-form-card">
        <h2>Accedi</h2>
        <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" class="auth-form">
          <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

          <label>Email</label>
          <input type="email" name="email" required autofocus placeholder="tu@esempio.it">

          <label>Password</label>
          <input type="password" name="password" required placeholder="••••••••">

          <div class="auth-actions">
            <button type="submit">Entra</button>
            <a class="btn-secondary" href="<?= $BASE ?>/public/auth/signup.php">Crea un account</a>
          </div>

          <div class="auth-minor">
            <a href="#" onclick="alert('Funzionalità non ancora disponibile'); return false;">Password dimenticata?</a>
          </div>
        </form>
      </div>
    </section>
  </section>
</main>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
