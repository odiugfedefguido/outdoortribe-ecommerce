<?php
require_once __DIR__ . '/../bootstrap.php'; // consentito via allowlist
session_unset();
session_destroy();
header("Location: {$BASE}/public/auth/login.php");
exit;
?>