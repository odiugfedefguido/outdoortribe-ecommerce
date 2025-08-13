<?php
/*
 * File: public/index.php
 * Scopo: Home e‑commerce con vetrina prodotti + ricerca.
 * Compatibile con schema: category(id,name,slug), product(category_id, image_filename…)
 */
session_start();
require_once __DIR__ . '/../server/connection.php';
require_once __DIR__ . '/config_path.php';
//require_once __DIR__ . '/auth_guard.php';     // obbliga login
require_once __DIR__ . '/img_path.php';       // helper per immagini prodotto

/* -------------------------- Config vetrina -------------------------- */
$limit = 12;                       // numero prodotti in home
$q = trim($_GET['q'] ?? '');       // ricerca veloce
$cat = (int)($_GET['cat'] ?? 0);   // filtro categoria

/* -------------------------- Carico categorie ------------------------ */
$cats = [];
if ($res = $conn->query("SELECT id, name FROM category WHERE is_active=1 ORDER BY name")) {
  $cats = $res->fetch_all(MYSQLI_ASSOC);
}

/* -------------------------- Query prodotti -------------------------- */
$where = "p.is_active=1";
$params = [];
$types  = '';

if ($q !== '') {
  $where .= " AND (p.title LIKE CONCAT('%', ?, '%') OR p.description LIKE CONCAT('%', ?, '%'))";
  $params[] = $q; $params[] = $q; $types .= 'ss';
}
if ($cat > 0) {
  $where .= " AND p.category_id = ?";
  $params[] = $cat; $types .= 'i';
}

$sql = "
  SELECT p.id, p.title, p.price, p.currency, p.image_filename
  FROM product p
  WHERE $where
  ORDER BY p.id DESC
  LIMIT ?
";
$params[] = $limit; $types .= 'i';

$stmt = $conn->prepare($sql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <title>OutdoorTribe – E‑commerce</title>

  <!-- CSS esistenti nel progetto -->
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">

  <style>
    .home-hero { margin: 12px 0 16px }
    .home-filter { display:flex; gap:8px; flex-wrap:wrap; margin: 12px 0 }
    .prod-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 16px;
    }
    .prod-card {
      display: block; text-decoration: none; color: inherit;
      border: 1px solid #ddd; border-radius: 10px; padding: 12px; background: #fff;
    }
    .prod-card img {
      width: 100%; height: 160px; object-fit: cover; border-radius: 8px; background: #f7f7f7;
    }
    .prod-title { margin-top: 8px; font-weight: 700; min-height: 44px; }
    .prod-price { margin-top: 4px; font-weight: 600; }
    .home-actions { margin: 12px 0 20px }
  </style>
</head>
<body>

<?php include __DIR__ . "/../templates/header/header.html"; ?>

<section class="home-hero">
  <h1>Benvenuto nel nuovo E‑commerce</h1>
  <p>Scopri gli ultimi arrivi e le categorie più cercate.</p>

  <!-- Ricerca + filtro categoria -->
  <form method="get" class="home-filter">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cerca prodotto..." />
    <select name="cat">
      <option value="0">Tutte le categorie</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cat===$c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Filtra</button>
    <a href="<?= $BASE ?>/public/" style="align-self:center;">Reset</a>
  </form>
</section>

<section>
  <div class="home-actions">
    <strong>Vetrina prodotti</strong>
    · <a href="<?= $BASE ?>/public/products/list.php">Vai al catalogo completo</a>
  </div>

  <?php if (empty($products)): ?>
    <p>Nessun prodotto trovato. <a href="<?= $BASE ?>/public/products/list.php">Apri il catalogo completo</a></p>
  <?php else: ?>
    <div class="prod-grid">
      <?php foreach ($products as $p): ?>
        <a class="prod-card" href="<?= $BASE ?>/public/products/details.php?id=<?= (int)$p['id'] ?>">
          <img src="<?= htmlspecialchars(product_image_url($p)) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
          <div class="prod-title"><?= htmlspecialchars($p['title']) ?></div>
          <div class="prod-price">
            <?= number_format((float)$p['price'], 2, ',', '.') . ' ' . htmlspecialchars($p['currency'] ?? 'EUR') ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . "/../templates/footer/footer.html"; ?>
</body>
</html>
