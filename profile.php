<?php
require __DIR__ . '/src/profile.php';
$me = current_user();
$uid = (int)($_GET['u'] ?? ($me['id'] ?? 0));
$q = db()->prepare('SELECT id, nickname, school FROM users WHERE id=?'); $q->execute([$uid]);
$u = $q->fetch();
if (!$u) { http_response_code(404); exit('Profiili ei leitud. <a href="./">Avalehele</a>'); }
$st = user_stats($uid); $bd = badges($st); $lv = level($st['xp']);
$own = $me && (int)$me['id'] === $uid;
$rec = db()->prepare("SELECT meal_name, rating, meal_date, likes_count FROM reviews WHERE user_id=? AND status='visible' ORDER BY id DESC LIMIT 5");
$rec->execute([$uid]); $recent = $rec->fetchAll();
$faces = ['', '😖', '😕', '😐', '🙂', '😍'];
?>
<!doctype html><html lang="et"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(display_name($u)) ?> · Lurr või hitt</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/kk.css"><link rel="stylesheet" href="assets/extra.css">
<script>if(localStorage.kkTheme==='dark')document.documentElement.dataset.theme='dark';</script></head><body>
<header class="bar"><a class="logo" href="./">Lurr <span>või</span> hitt</a><div class="bar-r"><a class="btn ghost" href="./">Avaleht</a></div></header>
<main>
  <h1 style="margin:.2em 0"><?= e(display_name($u)) ?></h1>
  <p class="meta"><?= e($u['school'] ?: '') ?></p>
  <div class="box"><b>Tase <?= $lv['level'] ?></b> <span class="meta">· <?= $st['xp'] ?> / <?= $lv['next'] ?> XP</span>
    <div class="meter"><i style="width:<?= max(2, $lv['pct']) ?>%"></i></div></div>
  <div class="stats">
    <div class="box"><b><?= $st['n'] ?></b><small>hinnangut</small></div>
    <div class="box"><b><?= $st['photos'] ?></b><small>pilti</small></div>
    <div class="box"><b><?= $st['likes'] ?></b><small>👍 saadud</small></div>
    <div class="box"><b><?= $st['streak'] ?></b><small>päeva järjest</small></div>
  </div>
  <h2>Märgid</h2>
  <div class="bdgs"><?php foreach ($bd as $b): ?>
    <div class="bdg <?= $b['earned'] ? 'earned' : 'locked' ?>" title="<?= e($b['desc']) ?>"><span><?= $b['icon'] ?></span><b><?= e($b['name']) ?></b><small><?= e($b['desc']) ?></small></div>
  <?php endforeach; ?></div>
  <?php if ($recent): ?><h2>Viimased hinnangud</h2><?php foreach ($recent as $r): ?>
    <div class="box"><b><?= e($r['meal_name']) ?></b> <?= $faces[(int)$r['rating']] ?> <span class="meta">· <?= e($r['meal_date']) ?> · 👍 <?= (int)$r['likes_count'] ?></span></div>
  <?php endforeach; endif; ?>
  <?php if ($own): ?>
    <h2>Kasutajanimi</h2>
    <form id="nf" class="stack"><input name="nickname" maxlength="20" placeholder="Jäta tühjaks, et olla „<?= e(display_name(['id' => $uid])) ?>“" value="<?= e($u['nickname']) ?>">
      <p class="note">Ära kasuta oma pärisnime. Avalikult nähakse ainult seda nime ja kooli.</p><p class="err" id="ne"></p><button class="btn pri">Salvesta</button></form>
    <script>document.getElementById('nf').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target);
      const r=await fetch('api/profile.php',{method:'POST',body:f,headers:{'X-CSRF-Token':<?= json_encode(csrf_token()) ?>},credentials:'same-origin'});
      const j=await r.json();r.ok?location.reload():document.getElementById('ne').textContent=j.error;};</script>
  <?php endif; ?>
</main></body></html>
