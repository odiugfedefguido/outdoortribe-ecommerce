<?php
// public/index.php (HOME con ricerca e filtro categoria)
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/img_path.php';

// Input
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

// Categorie per il filtro
$cats = [];
if ($res = $conn->query("SELECT id, name FROM category ORDER BY name")) {
  $cats = $res->fetch_all(MYSQLI_ASSOC);
}

// Query prodotti con filtri
$sql = "SELECT p.id, p.title, p.price, p.currency, p.stock, p.image_filename
        FROM product p
        WHERE p.is_active=1";
$types = "";
$vals = [];

if ($cat > 0) {
  $sql .= " AND p.category_id=?";
  $types .= "i";
  $vals[] = $cat;
}
if ($q !== '') {
  $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
  $types .= "ss";
  $like = "%".$conn->real_escape_string($q)."%";
  // NB: bind_param fa escaping, il real_escape_string qui è solo extra prudenza
  $vals[] = $like;
  $vals[] = $like;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Errore prepare: ".$conn->error); }
if ($types !== "") {
  // bind_param richiede variabili per riferimento
  $bind = array_merge([$types], $vals);
  foreach ($bind as $k => $v) { $bind[$k] = &$bind[$k]; }
  call_user_func_array([$stmt, 'bind_param'], $bind);
}
$stmt->execute();
$res = $stmt->get_result();
$prods = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <style>
    .filters { display:flex; gap:8px; align-items:center; margin: 12px 0 24px; }
    .filters input[type="text"], .filters select {
      padding:10px 12px; border:1px solid #ccc; border-radius:8px; min-width: 220px;
    }
    .filters .btn, .filters button {
      appearance:none; border:1px solid var(--brand,#1b5e20); background:var(--brand,#1b5e20); color:#fff;
      padding:10px 14px; border-radius:10px; font-weight:700; cursor:pointer;
    }
    .filters .btn-secondary { background:#fff; color:#1b5e20; border-color:#1b5e20; }
    .grid {
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
    .card {
      display:block; text-decoration:none; color:inherit;
      border:1px solid #e6e6e6; border-radius:12px; padding:12px;
      transition: box-shadow .15s ease, transform .05s ease;
      background:#fff;
    }
    .card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); transform: translateY(-1px); }
    .card img {
      width: 100%; height: 160px; object-fit: cover; border-radius: 6px; background:#f7f7f7;
    }
    .prod-title { font-weight: 600; margin-top: 8px; }
    .prod-price { font-weight: 700; margin-top: 4px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../templates/header/header.html'; ?>

<section class="container">
  <h1>Prodotti</h1>

  <form class="filters" method="get" action="<?= $BASE ?>/public/">
    <input type="text" name="q" placeholder="Cerca per parola chiave..." value="<?= htmlspecialchars($q) ?>">
    <select name="cat">
      <option value="0">Tutte le tipologie</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cat===(int)$c['id']?'selected':'' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Cerca</button>
    <?php if ($q !== '' || $cat > 0): ?>
      <a class="btn btn-secondary" href="<?= $BASE ?>/public/">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (!$prods): ?>
    <p>Nessun prodotto trovato.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($prods as $p): ?>
        <a class="card" href="<?= $BASE ?>/public/products/details.php?id=<?= (int)$p['id'] ?>">
          <img src="<?= htmlspecialchars(product_image_url($p)) ?>" alt="img <?= (int)$p['id'] ?>" />
          <div class="prod-title"><?= htmlspecialchars($p['title']) ?></div>
          <div class="prod-price">
            <?= number_format((float)$p['price'], 2, ',', '.') . ' ' . htmlspecialchars($p['currency'] ?? 'EUR') ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../templates/footer/footer.html'; ?>
</body>
</html>
