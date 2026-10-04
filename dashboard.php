<?php
require __DIR__ . '/src/dash.php';
$me = current_user();
$schools = $me ? allowed_schools() : [];
$boot = ['schools' => $schools, 'csrf' => csrf_token()];
?>
<!doctype html>
<html lang="et">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kooli sööklaraport</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/kk.css"><link rel="stylesheet" href="assets/dash.css"><link rel="stylesheet" href="assets/extra.css">
<script>if(localStorage.kkTheme==='dark')document.documentElement.dataset.theme='dark';window.DASH=<?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
</head>
<body>
<header class="bar noprint"><a class="logo" href="./">Lurr <span>või</span> hitt</a><div class="bar-r"><a class="btn ghost" href="./">Avaleht</a></div></header>
<main class="dash">
<?php if (!$me): ?>
  <p>Raporti nägemiseks <a href="./">logi sisse</a>.</p>
<?php elseif (!$schools): ?>
  <p>Sinu kontole pole ühtegi kooli seotud. Küsi ligipääsu Lurr või hitt meeskonnalt.</p>
<?php else: ?>
  <div class="toolbar noprint">
    <select id="dSchool" aria-label="Kool"><?php foreach ($schools as $s): ?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select>
    <select id="dDays" aria-label="Periood"><option value="7">Viimased 7 päeva</option><option value="30" selected>Viimased 30 päeva</option><option value="90">Viimased 90 päeva</option></select>
    <a class="btn" id="csv" href="#">Laadi alla CSV</a>
    <button class="btn pri" onclick="window.print()">Salvesta PDF</button>
  </div>
  <details class="box noprint"><summary><b>Menüü import</b> (foto, PDF või ekraanipilt)</summary>
    <p class="note">Claude loeb menüü tabeliks. Kontrolli ja paranda enne salvestamist. Õpilased näevad seda hindamisel.</p>
    <input type="file" id="menuFile" accept="image/*,application/pdf"> <button class="btn" id="menuRead" type="button">Loe menüü</button>
    <div id="menuPrev"></div><button class="btn pri" id="menuSave" type="button" hidden>Salvesta menüü</button> <span class="err" id="menuErr"></span></details>
  <h1 id="title">Sööklaraport</h1><p class="meta" id="sub"></p>
  <div id="out"><p class="note">Laen andmeid…</p></div>
<?php endif; ?>
</main>
<script src="assets/dash.js"></script>
</body></html>
