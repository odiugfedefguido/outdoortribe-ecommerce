<?php
/* 
 * File: public/products/list.php
 * Scopo: Lista/catalogo prodotti con filtri e paginazione.
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
?>
<h2>Catalogo prodotti</h2>
<!-- TODO: query SELECT sui prodotti con filtri (categoria, prezzo, testo) e paginazione -->

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
