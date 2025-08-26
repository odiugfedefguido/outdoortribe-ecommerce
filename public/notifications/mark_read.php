<?php
require_once __DIR__ . '/../bootstrap.php';
$uid = current_user_id();
if ($uid <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nid = (int)($_POST['id'] ?? 0);
  if ($nid > 0) {
    $stmt = $conn->prepare("UPDATE notification SET is_read=1 WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $nid, $uid);
    $stmt->execute();
    $stmt->close();
  }
}
header("Location: {$BASE}/public/notifications/");
exit;
