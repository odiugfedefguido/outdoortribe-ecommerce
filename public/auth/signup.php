<?php
require_once __DIR__ . '/../bootstrap.php';
$error = ''; $ok=false;
$ADMIN_CODE = isset($ADMIN_CODE) ? $ADMIN_CODE : (getenv('ADMIN_CODE') ?: 'ADM1N-OT');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name=trim($_POST['name']??''); $email=trim($_POST['email']??'');
  $pwd=(string)($_POST['password']??''); $pwd2=(string)($_POST['password2']??'');
  $admin_code = trim($_POST['admin_code']??'');
  if ($name===''||$email===''||$pwd===''||$pwd2===''){ $error='Compila tutti i campi.'; }
  elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)){ $error='Email non valida.'; }
  elseif ($pwd!==$pwd2){ $error='Le password non coincidono.'; }
  else {
    $stmt=$conn->prepare("SELECT id FROM `user` WHERE email=? LIMIT 1");
    $stmt->bind_param('s',$email); $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
    if ($exists){ $error='Esiste già un account con questa email.'; }
    else {
      $role = ($admin_code!=='' && hash_equals($ADMIN_CODE,$admin_code)) ? 'admin' : 'user';
      $hash = password_hash($pwd, PASSWORD_DEFAULT);
      // Inserisci, compatibile con eventuale colonna legacy password
      $res = $conn->query("SHOW COLUMNS FROM `user` LIKE 'password'");
      if ($res && $res->num_rows) {
        $stmt=$conn->prepare("INSERT INTO `user` (name,email,role,password_hash,password) VALUES (?,?,?,?,NULL)");
        $stmt->bind_param('ssss',$name,$email,$role,$hash);
      } else {
        $stmt=$conn->prepare("INSERT INTO `user` (name,email,role,password_hash) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss',$name,$email,$role,$hash);
      }
      if ($stmt->execute()){
        $uid=(int)$stmt->insert_id; $ok=true;
        $_SESSION['user_id']=$uid; $_SESSION['user_name']=$name; $_SESSION['user_role']=$role;
        header("Location: {$BASE}/public/"); exit;
      } else { $error='Errore durante la registrazione.'; }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrati</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<main class="page" style="max-width:600px;">
  <h1>Crea un account</h1>
  <?php if ($error !== ''): ?><div class="error" style="color:#b00;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Nome completo<br><input name="name" required></label><br>
    <label>Email<br><input type="email" name="email" required></label><br>
    <label>Password<br><input type="password" name="password" required></label><br>
    <label>Conferma password<br><input type="password" name="password2" required></label><br>
    <details><summary>Registrami come admin</summary><label>Codice admin<br><input name="admin_code" placeholder="facoltativo"></label></details>
    <button type="submit">Registrati</button>
  </form>
</main>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
