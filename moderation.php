<?php
require __DIR__ . '/src/bootstrap.php';
$me = current_user();
if (!$me || ($me['role'] ?? '') !== 'admin') { http_response_code(403); exit('Ainult adminile.'); }
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $id = (int)($_POST['id'] ?? 0); $act = $_POST['act'] ?? '';
  if ($act === 'approve') { $pdo->prepare("UPDATE reviews SET status='visible' WHERE id=?")->execute([$id]); $pdo->prepare('DELETE FROM reports WHERE review_id=?')->execute([$id]); }
  if ($act === 'hide') $pdo->prepare("UPDATE reviews SET status='hidden' WHERE id=?")->execute([$id]);
  if ($act === 'delete') {
    $p = $pdo->prepare('SELECT image_path FROM reviews WHERE id=?'); $p->execute([$id]);
    if ($img = $p->fetchColumn()) @unlink(UPLOAD_DIR . '/' . basename($img));
    foreach (['votes', 'reports'] as $t) $pdo->prepare("DELETE FROM $t WHERE review_id=?")->execute([$id]);
    $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]);
  }
  header('Location: moderation.php'); exit;
}
$rows = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM reports x WHERE x.review_id=r.id) reps FROM reviews r WHERE r.status='flagged' ORDER BY r.id DESC LIMIT 50")->fetchAll();
?>
<!doctype html><html lang="et"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Modereerimine</title>
<link rel="stylesheet" href="assets/kk.css"><link rel="stylesheet" href="assets/extra.css"><link rel="stylesheet" href="assets/confirm.css">
<script>if(localStorage.kkTheme==='dark')document.documentElement.dataset.theme='dark';</script></head><body>
<header class="bar"><a class="logo" href="./">Lurr <span>või</span> hitt</a><div class="bar-r"><a class="btn ghost" href="admin.php">Admin</a></div></header>
<main><h1>Modereerimine (<?= count($rows) ?>)</h1>
<?php if (!$rows): ?><p class="note">Järjekord on tühi. Siia jõuavad hinnangud, mida on kolm korda teatatud või mille pilti ei saanud automaatselt kontrollida.</p><?php endif; ?>
<?php foreach ($rows as $r): ?>
  <article class="rev"><div class="rev-h"><h3><?= e($r['meal_name']) ?> (<?= (int)$r['rating'] ?>/5)</h3><span class="meta"><?= (int)$r['reps'] ?> teadet</span></div>
    <div class="meta"><?= e($r['school']) ?> · <?= e($r['created_at']) ?></div>
    <?php if ($r['image_path']): ?><img src="<?= e($r['image_path']) ?>" alt="Foto"><?php endif; ?>
    <p><?= nl2br(e($r['comment'])) ?></p>
    <div class="row-btns">
      <?php foreach (['approve' => ['Kinnita', ''], 'hide' => ['Peida', ''], 'delete' => ['Kustuta', 'data-confirm="Kustuta hinnang?" data-text="Seda ei saa tagasi võtta." data-danger"']] as $a => [$label, $attr]): ?>
        <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="act" value="<?= $a ?>">
          <button class="btn <?= $a === 'approve' ? 'pri' : '' ?>" <?= $attr ?>><?= $label ?></button></form>
      <?php endforeach; ?>
    </div></article>
<?php endforeach; ?></main><script src="assets/confirm.js"></script></body></html>
