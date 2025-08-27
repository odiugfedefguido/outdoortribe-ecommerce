<?php
// templates/components/back.php
// Sticky "Indietro" bar shown under header on all pages except Home.
if (!isset($BASE) || $BASE === '') { include __DIR__ . '/../../public/config_path.php'; }

$script   = $_SERVER['SCRIPT_NAME'] ?? '';
$isHome   = (bool)preg_match('#/public/(index\.php)?$#', $script);

$ref      = $_SERVER['HTTP_REFERER'] ?? '';
$sameHost = $ref && (parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? ''));
$backUrl  = $sameHost ? $ref : ($BASE . '/public/');
?>
<?php if (!$isHome): ?>
  <div class="page-back page-back--sticky">
    <a href="<?= htmlspecialchars($backUrl) ?>"
       onclick="if(history.length>1){history.back();return false;}"
       class="back-inline" aria-label="Indietro">
      <img src="<?= $BASE ?>/assets/icons/back-icon.svg" alt="">
      <span>Indietro</span>
    </a>
  </div>
<?php endif; ?>
