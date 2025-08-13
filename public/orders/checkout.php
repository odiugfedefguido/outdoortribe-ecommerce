<?php
/* 
 * File: public/orders/checkout.php
 * Scopo: Pagina di checkout con indirizzo spedizione e riepilogo.
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
// Include il file di configurazione del percorso per arrivare a config_path.php
require_once __DIR__ . '/../config_path.php';
?>
<h2>Checkout</h2>
<!-- TODO: form indirizzo, pagamento (mock), riepilogo e conferma -->

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
