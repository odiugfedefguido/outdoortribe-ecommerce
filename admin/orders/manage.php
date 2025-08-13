<?php
/* 
 * File: admin/orders/manage.php
 * Scopo: Gestione ordini (venditore): cambia stato (paid/shipped/delivered), tracking.
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
require_once __DIR__ . '/../functions.php';
// TODO: proteggi pagina con checkLogin e verifica ruolo seller/admin

include __DIR__ . "/../../templates/footer/footer.html"; ?>
