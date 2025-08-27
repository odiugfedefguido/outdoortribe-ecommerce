<?php
// public/index.php (HOME con ricerca e filtro categoria)
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/img_path.php';

$q   = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat = isset($_GET['cat']) ? (int)$_GET['cat']  : 0;

// Categorie per il filtro
$cats = [];
if ($res = $conn->query("SELECT id, name FROM category ORDER BY name")) {
  $cats = $res->fetch_all(MYSQLI_ASSOC);
}

// Query prodotti con filtri: SOLO attivi e con stock > 0
$where  = ["p.is_active=1", "p.stock > 0"];
$params = [];
$types  = '';

if ($q !== '') {
  $where[]  = "(p.title LIKE CONCAT('%', ?, '%') OR p.description LIKE CONCAT('%', ?, '%'))";
  $params[] = $q; $params[] = $q; $types .= 'ss';
}
if ($cat > 0) {
  $where[]  = "p.category_id = ?";
  $params[] = $cat; $types .= 'i';
}

$sql = "SELECT p.id, p.title, p.price, p.currency, p.stock, p.image_filename
        FROM product p
        " . ($cat > 0 ? "JOIN category c ON c.id = p.category_id " : "") . "
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.id DESC
        LIMIT 100";

$stmt = $conn->prepare($sql);
if ($types !== '') {
  // bind_param variabile: gli argomenti devono essere passati per riferimento
  $bind = array_merge([$types], $params);
  foreach ($bind as $k => $v) { $bind[$k] = &$bind[$k]; }
  call_user_func_array([$stmt, 'bind_param'], $bind);
}
$stmt->execute();
$prods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OutdoorTribe · Home</title>
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/styles.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/main.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/components.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/header/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/components/back.css">
  <link rel="stylesheet" href="<?= $BASE ?>/templates/footer/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>/public/styles/catalog.css">

</head>
<body>
<?php include __DIR__ . "/../templates/header/header.html"; ?>
<?php include __DIR__ . "/../templates/components/back.php"; ?>

<section class="page">
  <h1>Prodotti</h1>

  <form method="get" class="searchbar" style="display:flex; gap:.5rem; align-items:center; margin: 1rem 0;">
    <input type="text" name="q" placeholder="Cerca..." value="<?= htmlspecialchars($q) ?>" style="flex:1; padding:.5rem;">
    <select name="cat" style="padding:.5rem;">
      <option value="0">Tutte le categorie</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cat===(int)$c['id']?'selected':'' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Filtra</button>
  </form>

  <?php if (empty($prods)): ?>
    <p>Nessun prodotto trovato.</p>
  <?php else: ?>
    <div class="grid-prod">
      <?php foreach ($prods as $p): ?>
        <a class="prod-card" href="<?= $BASE ?>/public/products/details.php?id=<?= (int)$p['id'] ?>">
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
