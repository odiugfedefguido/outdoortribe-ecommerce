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

 
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">


  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/login.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

  <style>
 
    .invalid{ border-color:#a10a0a !important; background:#ffecec; }
    .intro-error{ color:#a10a0a; margin-top:6px; }
    .client-error{ display:none; }
  </style>
</head>
<body class="auth-no-header">

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

      
        <form id="signupForm" method="post" class="auth-login__fields" novalidate>
          <label class="field">
            <span class="field__label">Nome</span>
            <input id="name" class="field__input" type="text" name="name"
                   value="<?= htmlspecialchars($name) ?>"
                   required
                   pattern="[A-Za-zÀ-ÖØ-öø-ÿ' ]{2,60}"
                   placeholder="Es. Mario Rossi">
            <?php if (!empty($errors['name'])): ?><small class="intro-error"><?= htmlspecialchars($errors['name']) ?></small><?php endif; ?>
            <small id="err_name" class="intro-error client-error">Inserisci un nome valido (solo lettere/spazi/apostrofi, 2–60 caratteri).</small>
          </label>

          <label class="field">
            <span class="field__label">Email</span>
            <input id="email" class="field__input" type="email" name="email"
                   value="<?= htmlspecialchars($email) ?>" required>
            <?php if (!empty($errors['email'])): ?><small class="intro-error"><?= htmlspecialchars($errors['email']) ?></small><?php endif; ?>
            <small id="err_email" class="intro-error client-error">Inserisci un’email valida.</small>
          </label>

          <label class="field">
            <span class="field__label">Password</span>
            <input id="password" class="field__input" type="password" name="password" required minlength="8" placeholder="Minimo 8 caratteri">
            <?php if (!empty($errors['password'])): ?><small class="intro-error"><?= htmlspecialchars($errors['password']) ?></small><?php endif; ?>
            <small id="err_password" class="intro-error client-error">La password deve avere almeno 8 caratteri e contenere almeno una lettera e un numero.</small>
          </label>

          <label class="field">
            <span class="field__label">Ripeti password</span>
            <input id="password2" class="field__input" type="password" name="password2" required>
            <?php if (!empty($errors['password2'])): ?><small class="intro-error"><?= htmlspecialchars($errors['password2']) ?></small><?php endif; ?>
            <small id="err_password2" class="intro-error client-error">Le password non coincidono.</small>
          </label>

          <div class="field" style="display:flex; align-items:center; gap:.5rem; margin-top:6px;">
            <input type="checkbox" id="want_admin" name="want_admin" <?= $want_admin ? 'checked' : '' ?>>
            <label for="want_admin" class="field__label" style="margin:0;">Registrami come amministratore</label>
          </div>

          <div id="admin_code_wrap" class="field" style="<?= $want_admin ? '' : 'display:none' ?>">
            <span class="field__label">Codice amministratore</span>
            <input id="admin_code" class="field__input" type="password" name="admin_code" <?= $want_admin ? 'required' : '' ?>>
            <?php if (!empty($errors['admin_code'])): ?><small class="intro-error"><?= htmlspecialchars($errors['admin_code']) ?></small><?php endif; ?>
            <small id="err_admin_code" class="intro-error client-error">Inserisci il codice amministratore.</small>
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
    // toggle admin code
    const cb = document.getElementById('want_admin');
    const wrap = document.getElementById('admin_code_wrap');
    const adminCode = document.getElementById('admin_code');
    if (cb) cb.addEventListener('change', () => {
      const on = cb.checked;
      wrap.style.display = on ? '' : 'none';
      if (adminCode) {
        adminCode.required = on;
        if (!on) { adminCode.value=''; hide('err_admin_code'); adminCode.classList.remove('invalid'); }
      }
    });

    // helpers UI
    const show = id => { const e=document.getElementById(id); if(e) e.style.display='block'; };
    const hide = id => { const e=document.getElementById(id); if(e) e.style.display='none'; };
    function invalid(el, errId){ el.classList.add('invalid'); show(errId); }
    function valid(el, errId){ el.classList.remove('invalid'); hide(errId); }

    // regole
    const reName = /^[A-Za-zÀ-ÖØ-öø-ÿ' ]{2,60}$/;
    function passwordStrong(p){ return p.length>=8 && /[A-Za-z]/.test(p) && /\d/.test(p); }

    // validazione submit
    document.getElementById('signupForm')?.addEventListener('submit', function(ev){
      const name = document.getElementById('name');
      const email = document.getElementById('email');
      const p1 = document.getElementById('password');
      const p2 = document.getElementById('password2');

      let ok = true;

      if (!reName.test((name.value||'').trim())) { invalid(name,'err_name'); ok=false; } else { valid(name,'err_name'); }

      if (!email.checkValidity()) { invalid(email,'err_email'); ok=false; } else { valid(email,'err_email'); }

      if (!passwordStrong(p1.value||'')) { invalid(p1,'err_password'); ok=false; } else { valid(p1,'err_password'); }

      if ((p1.value||'') !== (p2.value||'')) { invalid(p2,'err_password2'); ok=false; } else { valid(p2,'err_password2'); }

      if (cb && cb.checked) {
        if (!adminCode.value.trim()) { invalid(adminCode,'err_admin_code'); ok=false; } else { valid(adminCode,'err_admin_code'); }
      }

      if (!ok) ev.preventDefault();
    });

    // clear error on input
    ['name','email','password','password2','admin_code'].forEach(id=>{
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', ()=>{
        const map = {
          name:['err_name', v=>reName.test((v||'').trim())],
          email:['err_email', v=>document.getElementById('email').checkValidity()],
          password:['err_password', v=>passwordStrong(v||'')],
          password2:['err_password2', v=>(document.getElementById('password').value||'')=== (v||'')],
          admin_code:['err_admin_code', v=>!cb || !cb.checked || !!(v||'').trim()]
        };
        const [errId, okFn] = map[id];
        if (okFn(el.value)) valid(el,errId); else invalid(el,errId);
      });
    });
  </script>
</body>
</html>
