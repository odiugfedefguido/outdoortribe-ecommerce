<?php
/* 
 * File: admin/products/manage.php
 * Scopo: CRUD prodotti (area venditore/admin).
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../functions.php';
// TODO: proteggi pagina con checkLogin e verifica ruolo seller/admin
?>
<h2>Gestione Prodotti (Seller)</h2>
<!-- TODO: form Crea/Modifica prodotto, lista propri prodotti, elimina -->

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
