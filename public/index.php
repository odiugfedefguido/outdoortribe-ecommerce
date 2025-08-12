<?php
/* 
 * File: public/index.php
 * Scopo: Home e‑commerce. Punto d'ingresso pubblico.
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../server/connection.php';
require_once __DIR__ . '/../admin/functions.php';
?>
<!doctype html>
<html lang="it">
\1
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/components/components.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/header/header.css">
<meta charset="utf-8"/>
  <title>E‑commerce</title>
</head>
\1
<?php include __DIR__ . "/../templates/header/header.html"; ?>
<h1>Benvenuto nel nuovo E‑commerce</h1>
  <nav>
    <a href="/products/list.php">Prodotti</a> |
    <a href="/cart/view.php">Carrello</a> |
    <a href="/orders/my_orders.php">I miei ordini</a>
  </nav>
  <p>Questo progetto riusa auth e DB del vecchio social. Le funzioni e‑commerce sono da completare.</p>

<?php include __DIR__ . "/../templates/footer/footer.html"; ?>
</body>
</html>
