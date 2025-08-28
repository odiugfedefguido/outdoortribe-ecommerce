<?php

$script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\','/', $_SERVER['SCRIPT_NAME']) : '';
$BASE = '';

if ($script !== '') {
    // Cattura tutto ciò che sta prima di /public o /admin
    if (preg_match('#^(.+?)/(public|admin)(/|$)#', $script, $m)) {
        $BASE = $m[1];
    } else {
        // fallback: cartella dello script
        $BASE = rtrim(dirname($script), '/');
        if ($BASE === '/') $BASE = '';
    }
}
?>
