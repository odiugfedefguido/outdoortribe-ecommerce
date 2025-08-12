<?php
/* 
 * File: server/connection.php
 * Scopo: Connessione al database (MySQLi).
 * Stato: RIUSO (codice copiato dal progetto OutdoorTribe).
 * ------------------------------------------------------------------
 */
 

$servername = "localhost";
$username = "root"; // Il tuo nome utente del database
$password = ""; // La tua password del database
$dbname = "ecommerceweb"; // Il nome del tuo database

// Creazione della connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica della connessione
if ($conn == false) {
    die("Connessione fallita: " . $conn->connect_error);
}