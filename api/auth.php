<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/verify_card.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_out(['error' => 'Ainult POST'], 405);
if (!csrf_ok()) json_out(['error' => 'Seanss aegus, laadi leht uuesti'], 419);

$pdo = db();
$action = $_POST['action'] ?? '';

function start_session_for(array $u): void {
  session_regenerate_id(true);
  $sid = null;
  if (!empty($u['school'])) {
    $q = db()->prepare('SELECT id FROM schools WHERE name=?'); $q->execute([$u['school']]);
    $sid = $q->fetchColumn() ?: null;
  }
  $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['full_name'], 'email' => $u['email'], 'role' => $u['role'],
                       'school' => $u['school'], 'school_id' => $sid ? (int)$sid : null, 'verified' => (bool)($u['verified'] ?? false)];
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($action === 'logout') { $_SESSION = []; session_destroy(); json_out(['ok' => true]); }

if ($action === 'login') {
  if (!throttle('login', 6, 300)) json_out(['error' => 'Liiga palju katseid. Oota 5 minutit.'], 429);
  $q = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
  $q->execute([trim((string)($_POST['email'] ?? ''))]);
  $u = $q->fetch();
  if (!$u || !password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) json_out(['error' => 'Vale email või parool.'], 401);
  start_session_for($u);
  json_out(['ok' => true]);
}

if ($action === 'register') {
  if (!throttle('register', 4, 3600)) json_out(['error' => 'Liiga palju katseid. Proovi hiljem uuesti.'], 429);
  $name = trim((string)($_POST['full_name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $pass = (string)($_POST['password'] ?? '');
  if ($name === '' || mb_strlen($name) > 80)      json_out(['error' => 'Sisesta nimi.'], 422);
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Vigane email.'], 422);
  if (mb_strlen($pass) < 8)                       json_out(['error' => 'Parool peab olema vähemalt 8 märki.'], 422);
  if (empty($_POST['consent']))                   json_out(['error' => 'Kinnita nõusolek pileti kontrolliks.'], 422);
  $dupe = $pdo->prepare('SELECT 1 FROM users WHERE email=?'); $dupe->execute([$email]);
  if ($dupe->fetchColumn())                       json_out(['error' => 'See email on juba kasutusel.'], 409);

  $check = verify_student_card($_FILES['card'] ?? []);
  if (!$check['ok']) json_out(['error' => $check['reason']], 422);

  // leia olemasolev kool (sama nimi, sama number) või loo uus piletil oleva nime järgi
  $schoolName = null;
  foreach ($pdo->query('SELECT name FROM schools')->fetchAll() as $row) {
    if (school_matches($check['school'], $row['name'])) { $schoolName = $row['name']; break; }
  }
  if ($schoolName === null) {
    $pdo->prepare('INSERT IGNORE INTO schools(name) VALUES(?)')->execute([$check['school']]);
    $schoolName = $check['school'];
  }

  $pdo->prepare('INSERT INTO users(full_name,email,password_hash,role,school,verified,verified_at) VALUES(?,?,?,?,?,1,NOW())')
      ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), 'user', $schoolName]);
  $q = $pdo->prepare('SELECT * FROM users WHERE email=?'); $q->execute([$email]);
  start_session_for($q->fetch());
  json_out(['ok' => true], 201);
}
json_out(['error' => 'Tundmatu tegevus'], 422);
