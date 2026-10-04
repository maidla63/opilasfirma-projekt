<?php
declare(strict_types=1);
require __DIR__ . '/../src/dash.php';
require __DIR__ . '/../src/moderation.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
  $st = db()->prepare('SELECT menu_date d, meal_name m FROM menus WHERE school_id=? AND menu_date >= CURDATE() AND menu_date <= CURDATE() + INTERVAL 7 DAY ORDER BY menu_date, id');
  $st->execute([(int)($_GET['school_id'] ?? 0)]);
  $days = [];
  foreach ($st->fetchAll() as $r) $days[$r['d']][] = $r['m'];
  json_out(['days' => array_map(fn($d, $m) => ['date' => $d, 'meals' => $m], array_keys($days), $days)]);
}

$user = api_guard();
$sid = (int)($_POST['school_id'] ?? 0);
if (!school_allowed($sid)) json_out(['error' => 'Sul pole selle kooli menüü muutmise õigust.'], 403);

if (($_POST['mode'] ?? '') === 'read') {
  $f = $_FILES['file'] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 8 * 1024 * 1024 || !is_uploaded_file($f['tmp_name'])) json_out(['error' => 'Lisa fail (max 8MB).'], 422);
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  if ($mime === 'application/pdf') {
    $part = ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode((string)file_get_contents($f['tmp_name']))]];
  } else {
    $b64 = image_b64($f['tmp_name'], 1600);
    if (!$b64) json_out(['error' => 'Lubatud: pilt (JPG, PNG, WEBP) või PDF.'], 422);
    $part = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $b64]];
  }
  $j = claude_json(claude_call([$part, ['type' => 'text', 'text' =>
    'See on koolisöökla menüü. Tagasta AINULT JSON: {"items":[{"date":"YYYY-MM-DD","meals":["toit1","toit2"]}]}. Tänane kuupäev on ' . date('Y-m-d')
    . '. Kui aastat pole kirjas, kasuta jooksvat aastat. Kirjuta ainult toitude nimed, ilma hindade, kaloraanide ja allergeenikoodideta.']], 2500));
  if (!$j || !isset($j['items'])) json_out(['error' => 'Menüüd ei õnnestunud lugeda. Proovi selgema pildiga.'], 422);
  json_out(['items' => $j['items']]);
}

if (($_POST['mode'] ?? '') === 'save') {
  $items = json_decode((string)($_POST['items'] ?? ''), true);
  if (!is_array($items) || count($items) > 31) json_out(['error' => 'Vigane menüü.'], 422);
  $pdo = db(); $pdo->beginTransaction();
  try {
    foreach ($items as $it) {
      $d = (string)($it['date'] ?? '');
      if (!DateTime::createFromFormat('Y-m-d', $d)) continue;
      $pdo->prepare('DELETE FROM menus WHERE school_id=? AND menu_date=?')->execute([$sid, $d]);
      foreach (array_slice((array)($it['meals'] ?? []), 0, 12) as $m) {
        $m = trim((string)$m);
        if ($m === '' || mb_strlen($m) > 120 || bad_words($m)) continue;
        $pdo->prepare('INSERT IGNORE INTO menus(school_id, menu_date, meal_name) VALUES(?,?,?)')->execute([$sid, $d, $m]);
      }
    }
    $pdo->commit();
  } catch (Throwable $t) { $pdo->rollBack(); error_log($t->getMessage()); json_out(['error' => 'Salvestamine ebaõnnestus.'], 500); }
  json_out(['ok' => true]);
}
json_out(['error' => 'Tundmatu tegevus'], 422);
