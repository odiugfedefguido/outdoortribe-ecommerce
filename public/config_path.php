<?php
/* Calcola automaticamente il BASE URL (es. /ecommerce/outdoortribe-ecommerce) in base alla richiesta corrente */
$script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\','/', $_SERVER['SCRIPT_NAME']) : '';
if ($script === '') {
    $BASE = '';
} else {
    // Esempio: "/qualcosa/public/index.php" -> "/qualcosa"
    $BASE = preg_replace('#/public(/.*)?$#', '', $script);
    if ($BASE === null) {
        $BASE = '';
    }
}
?>
