<?php
session_start();
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../../admin/functions.php';
require_once __DIR__ . '/../config_path.php';
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <title>E‑commerce</title>
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>

<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<h1>Benvenuto nel nuovo E‑commerce</h1>

<p>Questo progetto riusa auth e DB del vecchio social. Le funzioni e‑commerce sono da completare.</p>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
