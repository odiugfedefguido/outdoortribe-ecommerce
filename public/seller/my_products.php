<?php
require_once __DIR__ . '/../bootstrap.php';
$uid = current_user_id(); $role = current_user_role();
if ($uid <= 0) { header("Location: {$BASE}/public/auth/login.php"); exit; }
if ($role !== 'admin') { http_response_code(403); echo 'Accesso negato'; exit; }
$stmt = $conn->prepare("SELECT id, title, price, currency, stock FROM product WHERE seller_id=? ORDER BY id DESC");
$stmt->bind_param('i', $uid); $stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
?>
<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>I miei prodotti</title><link rel="stylesheet" href="<?= $BASE ?>/assets/styles.css">
<style>.badge-esaurito{background:#222;color:#fff;border-radius:8px;padding:2px 8px;font-size:12px;}table{width:100%;border-collapse:collapse;margin-top:10px;}th,td{border-bottom:1px solid #eee;padding:8px;text-align:left;}</style></head><body>
<?php include __DIR__ . "/../../templates/header/header.html"; ?>
<section class="container"><h1>I miei prodotti</h1>
<?php if (!$rows): ?><p>Non hai ancora caricato prodotti.</p><?php else: ?>
<table><thead><tr><th>ID</th><th>Titolo</th><th>Prezzo</th><th>Stock</th><th>Stato</th></tr></thead><tbody>
<?php foreach ($rows as $p): ?><tr>
<td><?= (int)$p['id'] ?></td><td><?= htmlspecialchars($p['title']) ?></td>
<td><?= number_format((float)$p['price'],2,',','.') . ' ' . htmlspecialchars($p['currency']) ?></td>
<td><?= (int)$p['stock'] ?></td><td><?php if ((int)$p['stock']<=0): ?><span class="badge-esaurito">Esaurito</span><?php else: ?>Disponibile<?php endif; ?></td>
</tr><?php endforeach; ?></tbody></table><?php endif; ?>
</section><?php include __DIR__ . "/../../templates/footer/footer.html"; ?></body></html>
