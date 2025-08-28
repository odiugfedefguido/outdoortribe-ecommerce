<?php
// admin/product_new.php  (admin è sorella di public)
require_once __DIR__ . '/../public/bootstrap.php'; // sessione + $BASE + $conn
$uid = current_user_id();
if (current_user_role() !== 'admin') { http_response_code(403); echo 'Accesso negato'; exit; } // solo admin

// Carico categorie attive
$cats = [];
if ($res = $conn->query("SELECT id, name FROM category WHERE is_active=1 ORDER BY name")) {
  $cats = $res->fetch_all(MYSQLI_ASSOC);
}

// Variabili per “sticky form”
$title = $description = '';
$price = '';
$stock = '1';           // default: 1 (minimo richiesto >=1)
$category_id = 0;

$err = $msg = '';

function slugify($s) {
  $s = iconv('UTF-8','ASCII//TRANSLIT',$s);
  $s = strtolower(trim($s));
  $s = preg_replace('/[^a-z0-9]+/','-', $s);
  return trim($s,'-') ?: uniqid('prod-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Leggi POST
  $title       = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price       = (string)($_POST['price'] ?? '');
  $stock       = (string)($_POST['stock'] ?? '1');
  $category_id = (int)($_POST['category_id'] ?? 0);

  // Normalizza numeri
  $price_f = (float)$price;
  $stock_i = (int)$stock;

  // Validazioni: descrizione può essere vuota; gli altri NO
  $errors = [];
  if ($title === '')          $errors[] = 'Titolo obbligatorio.';
  if ($price_f <= 0)          $errors[] = 'Prezzo deve essere maggiore di 0.';
  if ($category_id <= 0)      $errors[] = 'Seleziona una categoria.';
  if ($stock_i < 1)           $errors[] = 'Quantità (stock) deve essere almeno 1.';

  if ($errors) {
    $err = implode(' ', $errors);
  } else {
    // Slug unico (consente titoli duplicati)
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

    // INSERT con seller_id; image_filename NULL per ora
    $currency = 'EUR';
    $is_active = 1;

    $stmt = $conn->prepare("INSERT INTO product
      (seller_id, category_id, title, slug, description, price, currency, stock, is_active, image_filename)
      VALUES (?,?,?,?,?,?,?,?,?,NULL)");
    $stmt->bind_param('iisssdsii', $uid, $category_id, $title, $slug, $description, $price_f, $currency, $stock_i, $is_active);
    $stmt->execute();
    $pid = (int)$stmt->insert_id;
    $stmt->close();

    // Upload immagine opzionale
    $uploadsDir = __DIR__ . '/../uploads/products'; // admin -> uploads
    if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }

    if (!empty($_FILES['image']['tmp_name'])) {
      $allowed = ['png','jpg','jpeg','webp'];
      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed, true)) {
        $err = 'Formato immagine non supportato (usa png/jpg/jpeg/webp).';
      } else {
        $filename = $pid . '.' . $ext;
        $dest = $uploadsDir . '/' . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
          $stmt = $conn->prepare("UPDATE product SET image_filename=? WHERE id=?");
          $stmt->bind_param('si', $filename, $pid);
          $stmt->execute();
          $stmt->close();
        }
      }
    } else {
      // Se non hai caricato nulla → usa default.jpg se esiste
      $defaultFile = $uploadsDir . '/default.jpg';
      if (is_file($defaultFile)) {
        $filename = 'default.jpg';
        $stmt = $conn->prepare("UPDATE product SET image_filename=? WHERE id=?");
        $stmt->bind_param('si', $filename, $pid);
        $stmt->execute();
        $stmt->close();
      }
    }

    // Redirect finale se nessun errore “post-insert” (es. immagine non valida)
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
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/icons/logo.svg">
  <link rel="shortcut icon" href="<?= $BASE ?>/assets/icons/mountain.svg">

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin · Nuovo prodotto</title>
  <base href="<?= htmlspecialchars($BASE) ?>/">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .notice{background:#fff4f4;color:#a00;border:1px solid #e6b8b8;padding:.75rem;border-radius:8px;margin:.75rem 0;}
    .ok{background:#f3fff4;color:#05630d;border:1px solid #b7e4be;padding:.75rem;border-radius:8px;margin:.75rem 0;}
    form label{display:block;margin-top:.5rem;}
    form input, form select, form textarea{width:100%;max-width:640px;}
    .actions{display:flex;gap:.75rem;align-items:center;margin-top:.5rem;}
  </style>
</head>
<body>
<?php include __DIR__ . "/../templates/header/header.html"; ?>
<?php include __DIR__ . "/../templates/components/back.php"; ?>

<section class="container">
  <h1>Aggiungi prodotto</h1>
  <?php if ($err): ?><div class="notice"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>Titolo</label>
    <input name="title" required value="<?= htmlspecialchars($title) ?>">

    <label>Categoria</label>
    <select name="category_id" required>
      <option value="0" <?= $category_id===0 ? 'selected' : '' ?>>— Seleziona una categoria —</option>
      <?php foreach ($cats as $c): $cid=(int)$c['id']; ?>
        <option value="<?= $cid ?>" <?= ($category_id===$cid ? 'selected' : '') ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Descrizione (opzionale)</label>
    <textarea name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>

    <div class="actions">
      <div style="flex:1">
        <label>Prezzo</label>
        <input type="number" name="price" min="0" step="0.01" required value="<?= htmlspecialchars($price) ?>">
      </div>
      <div style="flex:1">
        <label>Quantità (min 1)</label>
        <input type="number" name="stock" min="1" step="1" value="<?= htmlspecialchars($stock) ?>">
      </div>
    </div>

    <label>Immagine (png/jpg/jpeg/webp) — opzionale</label>
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
