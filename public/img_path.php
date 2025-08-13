<?php
// public/img_path.php
function product_image_url(array $p): string {
  $id = (int)$p['id'];
  $base = '/uploads/products'; // web path relativo alla root del progetto
  $fn = trim($p['image_filename'] ?? '');
  $candidate = $fn !== '' ? $fn : ($id . '.png');
  $fullFs = __DIR__ . '/../uploads/products/' . $candidate;
  if (is_file($fullFs)) {
    return $base . '/' . rawurlencode($candidate);
  }
  // placeholder minimale (puoi sostituire con una tua immagine)
  return 'https://via.placeholder.com/600x400?text=No+Image';
}
