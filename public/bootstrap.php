<?php
// public/bootstrap.php
// Include unico: mettilo come PRIMA riga in ogni pagina.

// 1) Sessione
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 2) BASE URL normalizzato alla radice del progetto (togli /public o /admin)
$script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$script = str_replace('\\', '/', $script); // safety su Windows
$BASE = '';
if ($script !== '') {
  if (preg_match('#^(.+?)/(public|admin)(/|$)#', $script, $m)) {
    $BASE = $m[1];
  } else {
    $BASE = rtrim(dirname($script), '/');
    if ($BASE === '/' || $BASE === false) { $BASE = ''; }
  }
}
$GLOBALS['BASE'] = $BASE; // utile per funzioni/templating

// 3) DB
require_once __DIR__ . '/../server/connection.php';

// 4) Helper utente
function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
function current_user_role(): string { return (string)($_SESSION['user_role'] ?? ''); }

// 5) Protezione base: login obbligatorio (eccetto auth) e area admin
$path = $script; // già normalizzato
$basename = basename($path);
$auth_public = ['login.php','signup.php','logout.php'];
$isAuthPage  = in_array($basename, $auth_public, true);

// Se non loggato → manda al login (tranne pagine auth)
if (current_user_id() <= 0 && !$isAuthPage) {
  $next = urlencode($_SERVER['REQUEST_URI'] ?? ($BASE . '/public/'));
  header('Location: ' . $BASE . '/public/auth/login.php?next=' . $next);
  exit;
}

// Se stai in /admin/ ma non sei admin → blocca
if (strpos($path, '/admin/') !== false && current_user_role() !== 'admin') {
  header('Location: ' . $BASE . '/public/?err=forbidden');
  exit;
}

// 6) CSRF token
if (empty($_SESSION['csrf_token'])) {
  try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  } catch (Throwable $e) {
    $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); // fallback
  }
}
