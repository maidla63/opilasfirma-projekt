<?php
require __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/dash.php';
$me = current_user();
try { $canDash = $me && allowed_schools(); } catch (Throwable $e) { $canDash = false; }
$boot = ['csrf' => csrf_token(), 'me' => $me ? ['name' => $me['name'], 'role' => $me['role'], 'school_id' => $me['school_id'] ?? null] : null];
?>
<!doctype html>
<html lang="et">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lurr või hitt? Koolisöökla hinnangud</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/kk.css"><link rel="stylesheet" href="assets/extra.css"><link rel="stylesheet" href="assets/confirm.css">
<script>window.KK=<?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;if(localStorage.kkTheme==='dark')document.documentElement.dataset.theme='dark';</script>
</head>
<body>
<header class="bar">
  <a class="logo" href="./">Lurr <span>või</span> hitt</a>
  <nav class="tabs" role="tablist">
    <button data-tab="feed" class="on">Voog</button>
    <button data-tab="board">Koolid</button>
    <button data-tab="people">Õpilased</button>
  </nav>
  <div class="bar-r">
    <button class="ico" id="theme" aria-label="Vaheta teemat">◐</button>
    <?php if ($me): ?>
      <?php if ($canDash): ?><a class="btn ghost" href="dashboard.php">Raport</a><?php endif; ?>
      <?php if ($me['role'] === 'admin'): ?><a class="btn ghost" href="admin.php">Admin</a><?php endif; ?>
      <a class="btn ghost" href="profile.php">Profiil</a>
      <button class="btn ghost" id="logout">Välju</button>
    <?php else: ?>
      <button class="btn ghost" id="openAuth">Logi sisse</button>
    <?php endif; ?>
  </div>
</header>

<main>
  <section class="menuboard" id="hero" aria-live="polite">
    <div class="mb-head">Täna sööklas</div>
    <div class="mb-cols">
      <div class="stamp hit"><small>Päeva hitt</small><b id="hitName">—</b><em id="hitSub">Hinnanguid veel pole</em></div>
      <div class="stamp lurr"><small>Päeva lurr</small><b id="lurrName">—</b><em id="lurrSub">Hinnanguid veel pole</em></div>
      <div class="stamp waste"><small>Prügikasti läheb</small><b id="wasteNum">—</b><em id="totalSub"></em></div>
    </div>
  </section>

  <p class="menuline" id="menuLine" hidden></p>

  <section id="tab-feed" class="pane on">
    <div class="filters"><select id="schoolFilter" aria-label="Kool"><option value="0">Kõik koolid</option></select></div>
    <div id="feed"></div>
    <button class="btn wide" id="more" hidden>Näita rohkem</button>
  </section>

  <section id="tab-board" class="pane"><ol id="board" class="board"></ol>
    <p class="note">Järjestus arvestab ka hinnangute arvu, nii et üks 5★ ei löö 100 hinnangut 4,8★.</p></section>
<section id="tab-people" class="pane">
    <div class="pf"><select id="pP"><option value="week">See nädal</option><option value="all">Kogu aeg</option></select>
      <select id="pM"><option value="reviews">Kõige rohkem hinnanguid</option><option value="liked">Kõige rohkem 👍</option></select></div>
    <ol id="people" class="board"></ol></section>
</main>

<button class="fab" id="openAdd">+ Hinda toitu</button>

<dialog id="authDlg"><form method="dialog" class="x"><button aria-label="Sulge">✕</button></form>
  <div class="seg"><button data-a="login" class="on">Logi sisse</button><button data-a="register">Loo konto</button></div>
  <form id="authForm" class="stack">
    <input type="hidden" name="action" value="login">
    <input name="full_name" placeholder="Nimi (ei kuvata teistele)" data-reg hidden>
    <label class="file" data-reg hidden><input type="file" name="card" accept="image/*"><span>🪪 Lisa foto õpilaspiletist</span></label>
    <label class="check" data-reg hidden><input type="checkbox" name="consent" value="1"> Nõustun, et pilet kontrollitakse automaatselt ja kool tuvastatakse piletilt. Pilti ei salvestata ja kontrollis loetakse ainult kooli nimi ja kehtivus.</label>
    <input name="email" type="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Parool (vähemalt 8 märki)" required>
    <p class="err" id="authErr"></p><button class="btn pri">Jätka</button>
  </form></dialog>

<dialog id="addDlg"><form method="dialog" class="x"><button aria-label="Sulge">✕</button></form>
  <h2>Mis sööklas täna oli?</h2>
  <form id="addForm" class="stack" enctype="multipart/form-data">
    <select name="school_id" id="addSchool" required></select>
    <input name="meal_name" list="mealList" placeholder="Toit (nt kanasupp)" maxlength="120" required><datalist id="mealList"></datalist>
    <input name="meal_date" type="date" id="mealDate">
    <fieldset class="faces"><legend>Kuidas maitses?</legend>
      <?php foreach (['😖' => 1, '😕' => 2, '😐' => 3, '🙂' => 4, '😍' => 5] as $f => $n): ?>
        <label><input type="radio" name="rating" value="<?= $n ?>" required><span><?= $f ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <fieldset class="chips"><legend>Mis oli viga või hea?</legend>
      <?php foreach (['külm','maitsetu','väike portsjon','liiga soolane','kuiv','värske','maitsev','hea'] as $t): ?>
        <label><input type="checkbox" name="tags[]" value="<?= $t ?>"><span><?= $t ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <fieldset class="chips"><legend>Kui palju sõid ära?</legend>
      <?php foreach ([0,25,50,75,100] as $p): ?>
        <label><input type="radio" name="eaten_percent" value="<?= $p ?>"><span><?= $p ?>%</span></label>
      <?php endforeach; ?>
    </fieldset>
    <label class="check"><input type="checkbox" name="would_eat_again" value="1"> Sööksin uuesti</label>
    <textarea name="comment" rows="3" maxlength="1000" placeholder="Lühike kommentaar (ära kirjuta nimesid)"></textarea>
    <label class="file"><input type="file" name="image" accept="image/*" id="img"><span id="imgLbl">📷 Lisa pilt (ära pildista inimesi)</span></label>
    <img id="prev" alt="" hidden>
    <p class="err" id="addErr"></p><button class="btn pri">Postita anonüümselt</button>
  </form></dialog>

<div id="toast" role="status"></div>
<script src="assets/confirm.js"></script>
<script src="assets/kk.js"></script>
</body></html>
