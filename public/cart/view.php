<?php
/* 
 * File: public/cart/view.php
 * Scopo: Visualizza carrello dell'utente.
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
?>
<h2>Il tuo carrello</h2>
<!-- TODO: mostra righe carrello da tabella cart/cart_items -->

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
