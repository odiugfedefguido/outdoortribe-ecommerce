<?php
/* 
 * File: public/products/list.php
 * Scopo: Lista/catalogo prodotti con filtri e paginazione.
 * Stato: IMPLEMENTATO (minimo funzionante).
 * ------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../server/connection.php';
// Include il file di configurazione del percorso per arrivare a config_path.php
require_once __DIR__ . '/../config_path.php'; // Assicurati che questo file esista e contenga il percorso corretto

$limit = 12;
$page  = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$q   = trim($_GET['q'] ?? '');
$cat = intval($_GET['cat'] ?? 0);

// Carica categorie per il filtro
$cats = [];
$res = $conn->query("SELECT id, name FROM category ORDER BY name");
if ($res) { while ($r = $res->fetch_assoc()) $cats[] = $r; }

// Costruzione query con filtri
$where = "p.is_active=1";
$params = [];
$types  = '';

if ($q !== '') {
  $where .= " AND (p.title LIKE CONCAT('%', ?, '%') OR p.description LIKE CONCAT('%', ?, '%'))";
  $params[] = $q; $params[] = $q; $types .= 'ss';
}

if ($cat > 0) {
  $where .= " AND EXISTS (SELECT 1 FROM product_category pc WHERE pc.product_id=p.id AND pc.category_id=?)";
  $params[] = $cat; $types .= 'i';
}

// Conteggio totale
$sqlCount = "SELECT COUNT(*) AS cnt FROM product p WHERE $where";
$stmt = $conn->prepare($sqlCount);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$sql = "
  SELECT p.id, p.title, p.price, p.currency,
         (SELECT url FROM product_image pi WHERE pi.product_id=p.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS img
  FROM product p
  WHERE $where
  ORDER BY p.created_at DESC
  LIMIT ? OFFSET ?";

$params2 = $params; $types2 = $types . 'ii';
$params2[] = $limit; $params2[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pages = max(1, ceil($total / $limit));
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"/>
  <title>Catalogo prodotti</title>
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/components/components.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/header/header.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/footer/footer.css">
</head>
<body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>

<h2>Catalogo prodotti</h2>

<form method="get" style="margin:12px 0;">
  <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cerca...">
  <select name="cat">
    <option value="0">Tutte le categorie</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $cat==$c['id'] ? 'selected':'' ?>>
        <?= htmlspecialchars($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Filtra</button>
</form>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
  <?php foreach ($rows as $p): ?>
    <a href="/ecommerce_from_outdoortribe/public/products/details.php?id=<?= $p['id'] ?>"
       style="display:block;border:1px solid #ddd;padding:12px;text-decoration:none;color:inherit;">
      <?php if ($p['img']): ?>
        <img src="<?= htmlspecialchars($p['img']) ?>" alt="" style="width:100%;height:160px;object-fit:cover;">
      <?php endif; ?>
      <div style="font-weight:bold;margin-top:8px;"><?= htmlspecialchars($p['title']) ?></div>
      <div><?= number_format($p['price'],2,',','.') . ' ' . htmlspecialchars($p['currency']) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div style="margin:16px 0;">
  <?php for ($i=1;$i<=$pages;$i++): ?>
    <a href="?q=<?= urlencode($q) ?>&cat=<?= $cat ?>&page=<?= $i ?>" 
       style="margin-right:6px;<?= $i==$page?'font-weight:bold;':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>

<?php include __DIR__ . "/../../templates/footer/footer.html"; ?>
</body>
</html>
