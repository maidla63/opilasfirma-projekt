<?php
require __DIR__ . '/src/bootstrap.php';
$me = current_user();
if (!$me || ($me['role'] ?? '') !== 'admin') { http_response_code(403); exit('Ainult adminile.'); }
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $msg = ['err' => 'Seanss aegus, proovi uuesti.'];
  if (csrf_ok()) {
    $act = $_POST['act'] ?? ''; $email = trim((string)($_POST['email'] ?? '')); $sid = (int)($_POST['school_id'] ?? 0);
    $sch = $pdo->prepare('SELECT name FROM schools WHERE id=?'); $sch->execute([$sid]); $schoolName = $sch->fetchColumn();
    $uq = $pdo->prepare('SELECT id FROM users WHERE email=?'); $uq->execute([$email]); $uid = $uq->fetchColumn();
    if ($act === 'remove') { $pdo->prepare('UPDATE users SET manages_school_id=NULL WHERE id=?')->execute([(int)($_POST['uid'] ?? 0)]); $msg = ['ok' => 'Ligipääs eemaldatud.']; }
    elseif ($act === 'assign') {
      if (!$schoolName) $msg = ['err' => 'Vali kool.'];
      elseif (!$uid) $msg = ['err' => 'Sellist kasutajat pole. Loo haldurikonto allpool.'];
      else { $pdo->prepare('UPDATE users SET manages_school_id=? WHERE id=?')->execute([$sid, $uid]); $msg = ['ok' => "Ligipääs antud: $email → $schoolName"]; }
    } elseif ($act === 'create') {
      $name = trim((string)($_POST['full_name'] ?? '')); $pass = (string)($_POST['password'] ?? '');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || mb_strlen($pass) < 8 || !$schoolName) $msg = ['err' => 'Täida kõik väljad (parool vähemalt 8 märki).'];
      elseif ($uid) $msg = ['err' => 'See email on juba kasutusel, kasuta "Anna ligipääs".'];
      else {
        $pdo->prepare('INSERT INTO users(full_name,email,password_hash,role,school,verified,verified_at,manages_school_id) VALUES(?,?,?,?,?,1,NOW(),?)')
            ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), 'user', $schoolName, $sid]);
        $msg = ['ok' => "Haldurikonto loodud: $email. Anna parool talle edasi."];
      }
    }
  }
  $_SESSION['flash'] = $msg; header('Location: admin_managers.php'); exit;
}
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$schools = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();
$mgr = $pdo->query('SELECT u.id, u.email, u.full_name, s.name school FROM users u JOIN schools s ON s.id=u.manages_school_id ORDER BY s.name')->fetchAll();
$opts = '<option value="">Vali kool</option>' . implode('', array_map(fn($s) => '<option value="' . (int)$s['id'] . '">' . e($s['name']) . '</option>', $schools));
$tok = csrf_token();
?>
<!doctype html><html lang="et"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Haldurid</title>
<link rel="stylesheet" href="assets/kk.css"><link rel="stylesheet" href="assets/extra.css"><link rel="stylesheet" href="assets/confirm.css">
<script>if(localStorage.kkTheme==='dark')document.documentElement.dataset.theme='dark';</script></head><body>
<header class="bar"><a class="logo" href="./">Lurr <span>või</span> hitt</a><div class="bar-r"><a class="btn ghost" href="moderation.php">Modereerimine</a></div></header>
<main><h1>Kooli haldurid</h1>
<?php if ($flash): ?><p class="box" style="border-color:<?= isset($flash['ok']) ? 'var(--mint)' : 'var(--red)' ?>"><?= e($flash['ok'] ?? $flash['err']) ?></p><?php endif; ?>
<div class="box"><h2>Praegused haldurid</h2>
<?php if (!$mgr): ?><p class="note">Halduriid pole veel.</p><?php endif; ?>
<?php foreach ($mgr as $m): ?>
  <form method="post" style="display:flex;gap:8px;align-items:center;justify-content:space-between;margin:6px 0">
    <span><b><?= e($m['school']) ?></b> · <?= e($m['email']) ?></span>
    <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="act" value="remove"><input type="hidden" name="uid" value="<?= (int)$m['id'] ?>">
    <button class="btn" data-confirm="Eemalda ligipääs?" data-text="<?= e($m['email']) ?> ei näe enam kooli raportit." data-ok="Eemalda" data-danger>Eemalda</button></form>
<?php endforeach; ?></div>
<div class="box"><h2>Anna olemasolevale kasutajale ligipääs</h2><form method="post" class="stack">
  <input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="act" value="assign">
  <input name="email" type="email" placeholder="Kasutaja email" required><select name="school_id" required><?= $opts ?></select><button class="btn pri">Anna ligipääs</button></form></div>
<div class="box"><h2>Loo uus haldurikonto (direktor, haldusjuht)</h2><p class="note">Halduril pole õpilaspiletit, seega loo konto siin ja anna parool talle ise edasi.</p>
<form method="post" class="stack"><input type="hidden" name="csrf" value="<?= e($tok) ?>"><input type="hidden" name="act" value="create">
  <input name="full_name" placeholder="Nimi" required><input name="email" type="email" placeholder="Email" required>
  <input name="password" type="password" placeholder="Parool (vähemalt 8 märki)" required><select name="school_id" required><?= $opts ?></select><button class="btn pri">Loo konto</button></form></div>
</main><script src="assets/confirm.js"></script></body></html>
