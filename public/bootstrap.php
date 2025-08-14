<?php
// public/bootstrap.php
// Include unico da mettere come PRIMA riga in tutte le pagine del sito.
// - Richiede login per TUTTE le pagine tranne login/signup/logout
// - Protegge automaticamente /public/admin/* per soli admin
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/config_path.php';
require_once __DIR__ . '/../server/connection.php';

function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
function current_user_role(): string { return (string)($_SESSION['user_role'] ?? ''); }

$path = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '');
$basename = basename($path);
$allow = ['login.php','signup.php','logout.php']; // pagine pubbliche per l'autenticazione

$isAuthPage = in_array($basename, $allow, true);

// se non loggato → manda al login
if (current_user_id() <= 0 && !$isAuthPage) {
  $next = urlencode($_SERVER['REQUEST_URI'] ?? ($BASE . '/public/'));
  header("Location: {$BASE}/public/auth/login.php?next={$next}");
  exit;
}

// se stai in /public/admin/ ma non sei admin → blocca
if (strpos($path, '/public/admin/') !== false && current_user_role() !== 'admin') {
  header("Location: {$BASE}/public/?err=forbidden");
  exit;
}

// Helper da usare dentro pagine admin se vuoi un check esplicito
function require_admin() {
  global $BASE;
  if (current_user_role() !== 'admin') {
    header("Location: {$BASE}/public/?err=forbidden");
    exit;
  }
}
