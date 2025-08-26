<?php
// public/bootstrap.php
// Include unico: va come PRIMA riga in ogni pagina.

// 1) Sessione
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// BASE URL normalizzato alla radice del progetto (togli tutto da /public in poi)
$scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$BASE = preg_replace('#/public(?:/.*)?$#', '', $scriptDir);
if ($BASE === '/' || $BASE === false) { $BASE = ''; } // caso root


// 3) Costanti di percorso (filesystem)
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));          // .../outdoortribe-ecommerce
if (!defined('TPL_DIR'))  define('TPL_DIR',  APP_ROOT . '/templates');   // .../templates

// 4) Connessione DB (unica)
require_once __DIR__ . '/../server/connection.php';

// 5) Helper utente
function current_user_id(): int    { return (int)($_SESSION['user_id'] ?? 0); }
function current_user_role(): string { return (string)($_SESSION['user_role'] ?? ''); }
function require_admin(): void {
  global $BASE;
  if (current_user_role() !== 'admin') {
    header("Location: {$BASE}/public/?err=forbidden");
    exit;
  }
}

// 6) Protezione accessi
$path = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '');
$basename = basename($path);

// Pagine pubbliche per l'autenticazione
$auth_public = ['login.php', 'signup.php', 'logout.php'];
$isAuthPage  = in_array($basename, $auth_public, true);

// Se non loggato → manda al login (tranne auth pages)
if (current_user_id() <= 0 && !$isAuthPage) {
  $next = urlencode($_SERVER['REQUEST_URI'] ?? ($BASE . '/public/'));
  header("Location: {$BASE}/public/auth/login.php?next={$next}");
  exit;
}

// Se stai in /public/admin/ ma non sei admin → blocca
if (strpos($path, '/public/admin/') !== false && current_user_role() !== 'admin') {
  header("Location: {$BASE}/public/?err=forbidden");
  exit;
}
