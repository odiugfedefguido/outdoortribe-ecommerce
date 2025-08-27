<?php
// admin/product_new.php  (admin è sorella di public)
require_once __DIR__ . '/../public/bootstrap.php'; // sessione + $BASE + $conn
$uid = current_user_id();
if (current_user_role() !== 'admin') { http_response_code(403); echo 'Accesso negato'; exit; } // solo admin

// Fallback: se per qualunque motivo $BASE non è settata, ricarico la config
if (!isset($BASE) || $BASE === '') {
  require_once __DIR__ . '/../public/config_path.php';
}

// Carico categorie
$cats = [];
if ($res = $conn->query("SELECT id, name FROM category WHERE is_active=1 ORDER BY name")) {
  $cats = $res->fetch_all(MYSQLI_ASSOC);
}

$err = $msg = '';

function slugify($s) {
  $s = iconv('UTF-8','ASCII//TRANSLIT',$s);
  $s = strtolower(trim($s));
  $s = preg_replace('/[^a-z0-9]+/','-', $s);
  return trim($s,'-') ?: uniqid('prod-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $category_id = (int)($_POST['category_id'] ?? 0);
  $currency = 'EUR';
  $is_active = 1;

  if ($title === '' || $price <= 0) {
    $err = 'Titolo e prezzo sono obbligatori.';
  } else {
    // Slug unico (nome duplicato consentito)
    $slugBase = slugify($title);
    $slug = $slugBase;
    $i = 2;
    $stmtSlug = $conn->prepare("SELECT 1 FROM product WHERE slug=? LIMIT 1");
    while (true) {
      $stmtSlug->bind_param('s', $slug);
      $stmtSlug->execute();
      $stmtSlug->store_result();
      if ($stmtSlug->num_rows === 0) break;
      $slug = $slugBase . '-' . $i;
      $i++;
    }
    $stmtSlug->close();
    $stmt = $conn->prepare("INSERT INTO product (seller_id,category_id,title,slug,description,price,currency,stock,is_active,image_filename) VALUES (?,?,?,?,?,?,?,?,?,NULL)");
    $stmt->bind_param('iisssdsii', $uid, $category_id, $title, $slug, $description, $price, $currency, $stock, $is_active);
    $stmt->execute();
    $pid = (int)$stmt->insert_id;
    $stmt->close();

    // Upload immagine opzionale
    if (!empty($_FILES['image']['tmp_name'])) {
      $allowed = ['png','jpg','jpeg','webp'];
      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed, true)) {
        $err = 'Formato immagine non supportato (usa png/jpg/jpeg/webp).';
      } else {
        $uploadsDir = __DIR__ . '/../uploads/products'; // admin -> uploads
        if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }
        $filename = $pid . '.' . $ext;
        $dest = $uploadsDir . '/' . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
          $stmt = $conn->prepare("UPDATE product SET image_filename=? WHERE id=?");
          $stmt->bind_param('si', $filename, $pid);
          $stmt->execute();
          $stmt->close();
        }
      }
    }

    // Se non è stata caricata alcuna immagine, imposta default.jpg se esiste
    if (!$err && empty($_FILES['image']['tmp_name'])) {
      $uploadsDir = __DIR__ . '/../uploads/products';
      $defaultFile = $uploadsDir . '/default.jpg';
      if (is_file($defaultFile)) {
        $filename = 'default.jpg';
        $stmt = $conn->prepare('UPDATE product SET image_filename=? WHERE id=?');
        $stmt->bind_param('si', $filename, $pid);
        $stmt->execute();
        $stmt->close();
      }
    }

    if (!$err) {
      header("Location: {$BASE}/public/products/details.php?id={$pid}");
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin · Nuovo prodotto</title>

  <!-- Base URL per stabilizzare link/asset -->
  <base href="<?= htmlspecialchars($BASE) ?>/">

  <!-- Stessi CSS delle pagine public -->
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../templates/header/header.html"; ?>

<section class="container">
  <h1>Aggiungi prodotto</h1>
  <?php if ($err): ?><div class="notice"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>Titolo</label>
    <input name="title" required>

    <label>Categoria</label>
    <select name="category_id">
      <option value="0">— Nessuna —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Descrizione</label>
    <textarea name="description" rows="4"></textarea>

    <div class="actions">
      <div style="flex:1">
        <label>Prezzo</label>
        <input type="number" name="price" min="0" step="0.01" required>
      </div>
      <div style="flex:1">
        <label>Stock</label>
        <input type="number" name="stock" min="0" step="1" value="0">
      </div>
    </div>

    <label>Immagine (png/jpg/jpeg/webp)</label>
    <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp">

    <div class="actions" style="margin-top:10px;">
      <button type="submit">Crea prodotto</button>
      <a class="btn-secondary" href="<?= $BASE ?>/public/">Annulla</a>
    </div>
  </form>
</section>

<?php include __DIR__ . "/../templates/footer/footer.html"; ?>
</body>
</html>
