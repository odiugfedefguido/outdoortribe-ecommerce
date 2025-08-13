<?php
// public/img_path.php
// Restituisce l'URL dell'immagine prodotto, usando $BASE se presente o calcolandolo al volo.
// Convenzione: uploads/products/{id}.png|jpg|jpeg|webp oppure product.image_filename (solo nome file).

// Calcola il BASE se non definito (es. se config_path.php non è stato incluso prima)
function _img_base(): string {
  if (!empty($GLOBALS['BASE'])) {
    return rtrim($GLOBALS['BASE'], '/');
  }
  $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\','/', $_SERVER['SCRIPT_NAME']) : '';
  if ($script === '') return '';
  $b = preg_replace('#/public(/.*)?$#', '', $script);
  return $b ?: '';
}

function product_image_url(array $p): string {
  $BASE = _img_base();

  // Cartelle web/FS
  $webUploads = $BASE . '/uploads/products';
  // __DIR__ è "public" -> risalgo a root progetto e poi entro in uploads/products
  $fsUploads  = __DIR__ . '/../uploads/products';

  // Placeholder: prova prima place_older.png nella cartella public/images
  $placeholder = null;
  foreach (['place_older.png', 'placeholder-product.png'] as $ph) {
    $fsPh = __DIR__ . '/images/' . $ph;           // FS: public/images/...
    if (is_file($fsPh)) {
      $placeholder = $BASE . '/public/images/' . rawurlencode($ph); // URL
      break;
    }
  }
  if (!$placeholder) {
    // Estremo fallback se il file non esiste
    $placeholder = 'https://via.placeholder.com/600x400?text=No+Image';
  }

  // ID prodotto
  $id = (int)($p['id'] ?? 0);
  if ($id <= 0) {
    return $placeholder;
  }

  // Eventuale nome file nel DB
  $dbName = '';
  if (!empty($p['image_filename'])) {
    $dbName = basename(trim((string)$p['image_filename']));
  }

  // Candidati: prima il nome dal DB, poi {id}.{estensione}
  $candidates = [];
  if ($dbName !== '') {
    $candidates[] = $dbName;
  }
  foreach (['png','jpg','jpeg','webp'] as $ext) {
    $candidates[] = $id . '.' . $ext;
  }

  // Cerca il primo file esistente
  foreach ($candidates as $name) {
    $fs = $fsUploads . '/' . $name;
    if (is_file($fs)) {
      return $webUploads . '/' . rawurlencode($name);
    }
  }

  // Nessuna immagine trovata -> placeholder
  return $placeholder;
}
