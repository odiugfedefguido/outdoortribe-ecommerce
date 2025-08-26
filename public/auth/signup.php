<?php
require_once __DIR__ . '/../bootstrap.php';
$error = ''; $ok=false;
$ADMIN_CODE = isset($ADMIN_CODE) ? $ADMIN_CODE : (getenv('ADMIN_CODE') ?: 'ADM1N-OT');
function column_exists(mysqli $conn, string $table, string $col): bool {
  $t = $conn->real_escape_string($table); $c = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $res && $res->num_rows>0;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name=trim($_POST['name']??''); $email=trim($_POST['email']??'');
  $pwd=(string)($_POST['password']??''); $pwd2=(string)($_POST['password2']??'');
  $admin_code = trim($_POST['admin_code']??'');
  if ($name===''||$email===''||$pwd===''||$pwd2===''){ $error='Compila tutti i campi.'; }
  elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)){ $error='Email non valida.'; }
  elseif ($pwd!==$pwd2){ $error='Le password non coincidono.'; }
  elseif (strlen($pwd)<6){ $error='Password troppo corta.'; }
  else {
    $stmt=$conn->prepare("SELECT id FROM `user` WHERE email=? LIMIT 1"); $stmt->bind_param('s',$email); $stmt->execute();
    $exists=(bool)$stmt->get_result()->fetch_row(); $stmt->close();
    if ($exists){ $error='Esiste già un account con questa email.'; }
    else {
      $role = ($admin_code!=='' && hash_equals($ADMIN_CODE,$admin_code)) ? 'admin' : 'user';
      $hash = password_hash($pwd, PASSWORD_DEFAULT);
      $hasLegacyPwd = column_exists($conn,'user','password');
      if ($hasLegacyPwd){
        $stmt=$conn->prepare("INSERT INTO `user` (name,email,role,password_hash,password) VALUES (?,?,?,?,NULL)");
        $stmt->bind_param('ssss',$name,$email,$role,$hash);
      } else {
        $stmt=$conn->prepare("INSERT INTO `user` (name,email,role,password_hash) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss',$name,$email,$role,$hash);
      }
      if ($stmt->execute()){
        $uid=(int)$stmt->insert_id; $ok=true;
        $_SESSION['user_id']=$uid; $_SESSION['user_name']=$name; $_SESSION['user_role']=$role;
        $next = ($role==='admin') ? ($BASE.'/admin/products/manage.php') : ($BASE.'/public/');
        header('Location: '.$next); exit;
      } else { $error='Errore durante la creazione dell’account.'; }
    }
  }
}
$prefill_name=htmlspecialchars($_POST['name']??''); $prefill_email=htmlspecialchars($_POST['email']??'');
?>
<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><title>Registrati</title><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
<link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
<link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
<link rel="stylesheet" href="<?= $BASE ?>/public/styles/auth.css"></head><body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<main class="auth-landing"><section class="auth-split">
<aside class="brand-side"><div class="brand-overlay"><h1>Crea il tuo account</h1></div></aside>
<section class="form-side">
<div class="auth-form-card"><h2>Registrazione</h2><?php if ($error): ?><div class="notice"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="auth-form" autocomplete="off">
<label>Nome</label><input type="text" name="name" required value="<?= $prefill_name ?>">
<label>Email</label><input type="email" name="email" required value="<?= $prefill_email ?>">
<label>Password</label><input type="password" name="password" required placeholder="Minimo 6 caratteri">
<label>Conferma password</label><input type="password" name="password2" required>
<details style="margin:8px 0;"><summary>Ho un codice per creare un admin</summary>
<label>Codice admin (opzionale)</label><input type="text" name="admin_code" placeholder="Inserisci codice se ne hai uno"></details>
<div class="auth-actions"><button type="submit">Crea account</button><a class="btn-secondary" href="<?= $BASE ?>/public/auth/login.php">Hai già un account?</a></div>
</form></div></section></section></main>
<?php include __DIR__ . "/../../templates/footer/footer.html"; ?></body></html>
