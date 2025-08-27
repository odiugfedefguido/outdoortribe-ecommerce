<?php
// ESEGUI UNA SOLA VOLTA, poi cancella questo file.
$codice = 'qwerty'; // es. "N59-ADMIN-2025-!@#"
echo password_hash($codice, PASSWORD_DEFAULT), PHP_EOL;
