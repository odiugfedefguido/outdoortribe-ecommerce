<?php
// public/notifications/delete.php

require_once __DIR__ . '/../bootstrap.php';

// Fallback funzioni se mancassero
if (!function_exists('current_user_id')) {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
  function current_user_role(): string { return (string)($_SESSION['user_role'] ?? ''); }
}

$uid = current_user_id();
if ($uid <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nid = (int)($_POST['id'] ?? 0);
  if ($nid > 0) {
    $stmt = $conn->prepare("DELETE FROM notification WHERE id=? AND user_id=?");
    if ($stmt) {
      $stmt->bind_param('ii', $nid, $uid);
      $stmt->execute();
      $stmt->close();
    }
  }
}
header("Location: {$BASE}/public/notifications/");
exit;
