<?php
// public/img_path.php
// Restituisce l'URL dell'immagine prodotto, usando $BASE se presente o calcolandolo al volo.
// Convenzione: uploads/products/{id}.png|jpg|jpeg|webp oppure product.image_filename (solo nome file).

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
  $id = (int)($p['id'] ?? 0);
  $fn = trim((string)($p['image_filename'] ?? ''));
  $base = _img_base();
  $webUploads = $base . '/uploads/products';
  $fsUploads  = dirname(__DIR__) . '/uploads/products';
  $placeholder = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200"><rect width="100%" height="100%" fill="#ddd"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="16">No image</text></svg>');

  // Se il record specifica un filename, usa quello se esiste
  if ($fn !== '') {
    $fs = $fsUploads . '/' . $fn;
    if (is_file($fs)) {
      return $webUploads . '/' . rawurlencode($fn);
    }
  }

  // Prova estensioni standard basate su id
  foreach (['jpg','jpeg','png','webp'] as $ext) {
    $fs = $fsUploads . '/' . $id . '.' . $ext;
    if (is_file($fs)) {
      return $webUploads . '/' . $id . '.' . $ext;
    }
  }

  return $placeholder;
}
