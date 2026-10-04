<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/upload.php';
require __DIR__ . '/../src/moderation.php';

$user = api_guard();
if (!throttle('review', 5, 600)) json_out(['error' => 'Liiga kiiresti. Proovi mõne minuti pärast.'], 429);

$schoolId = (int)($_POST['school_id'] ?? 0);
if (($user['role'] ?? '') !== 'admin') {
  $schoolId = (int)($user['school_id'] ?? 0); // oma kool, mitte see, mis vormis on
  if ($schoolId < 1) json_out(['error' => 'Sinu kontol pole kooli. Logi välja ja uuesti sisse.'], 403);
}
$meal     = trim((string)($_POST['meal_name'] ?? ''));
$rating   = (int)($_POST['rating'] ?? 0);
$again    = !empty($_POST['would_eat_again']) ? 1 : 0;
$eaten    = isset($_POST['eaten_percent']) && $_POST['eaten_percent'] !== '' ? (int)$_POST['eaten_percent'] : null;
$comment  = trim((string)($_POST['comment'] ?? ''));
$mealDate = (string)($_POST['meal_date'] ?? date('Y-m-d'));

// tagid ainult lubatud nimekirjast
$allowedTags = ['külm', 'maitsetu', 'väike portsjon', 'liiga soolane', 'hea', 'värske', 'kuiv', 'maitsev'];
$tags = array_values(array_intersect((array)($_POST['tags'] ?? []), $allowedTags));

$d = DateTime::createFromFormat('Y-m-d', $mealDate);
$err = null;
if ($schoolId < 1)                                   $err = 'Vali kool.';
elseif ($meal === '' || mb_strlen($meal) > 120)      $err = 'Sisesta toidu nimi (max 120 märki).';
elseif ($rating < 1 || $rating > 5)                  $err = 'Hinne peab olema 1–5.';
elseif ($eaten !== null && !in_array($eaten, [0, 25, 50, 75, 100], true)) $err = 'Vigane söödud %.';
elseif (mb_strlen($comment) > 1000)                  $err = 'Kommentaar on liiga pikk (max 1000).';
elseif (!$d || $d > new DateTime('tomorrow') || $d < new DateTime('-7 days')) $err = 'Kuupäev peab olema viimase 7 päeva sees.';
if (!$err && bad_words($meal . ' ' . $comment)) $err = 'Tekstis on sobimatuid sõnu.';
if ($err) json_out(['error' => $err], 422);

$pdo = db();
$s = $pdo->prepare('SELECT name FROM schools WHERE id = ?');
$s->execute([$schoolId]);
$schoolName = $s->fetchColumn();
if (!$schoolName) json_out(['error' => 'Tundmatu kool.'], 422);

// 1 hinnang toidu kohta päevas kasutaja kohta
$dup = $pdo->prepare('SELECT 1 FROM reviews WHERE user_id=? AND school_id=? AND meal_name=? AND meal_date=? LIMIT 1');
$dup->execute([$user['id'], $schoolId, $meal, $mealDate]);
if ($dup->fetchColumn()) json_out(['error' => 'Selle toidu oled sel päeval juba hinnanud.'], 409);

$imagePath = null; $status = 'visible';
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
  $pc = photo_check($_FILES['image']);
  if ($pc === 'blocked') json_out(['error' => 'Pildil on nähtav inimene, isikuandmed või sobimatu sisu. Tee pilt ainult toidust.'], 422);
  if ($pc === 'unknown') $status = 'flagged'; // ilmub pärast admini kontrolli
  try { $imagePath = save_review_image($_FILES['image']); }
  catch (RuntimeException $ex) { json_out(['error' => $ex->getMessage()], 422); }
}

$pdo->prepare('INSERT INTO reviews (user_id, school, school_id, meal_name, rating, would_eat_again, eaten_percent, tags, meal_date, comment, image_path, status)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$user['id'], $schoolName, $schoolId, $meal, $rating, $again, $eaten, implode(',', $tags), $mealDate, $comment, $imagePath, $status]);

json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'pending' => $status !== 'visible'], 201);
