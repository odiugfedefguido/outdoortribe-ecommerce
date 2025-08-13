<?php
/* 
 * File: public/orders/my_orders.php
 * Scopo: Lista ordini dell'utente (buyer).
 * Stato: NUOVO (file da completare).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
// Include il file di configurazione del percorso per arrivare a config_path.php
require_once __DIR__ . '/../config_path.php';
// TODO: SELECT ordini per utente loggato

include __DIR__ . "/../../templates/footer/footer.html"; ?>
