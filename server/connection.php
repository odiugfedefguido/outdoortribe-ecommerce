<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "ecommerceweb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Charset + collation coerenti
$conn->set_charset("utf8mb4");
$conn->query("SET collation_connection = 'utf8mb4_unicode_ci'");
