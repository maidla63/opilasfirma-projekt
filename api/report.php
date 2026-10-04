<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = api_guard();
$rid = (int)($_POST['review_id'] ?? 0);
if (!throttle('report', 10, 3600)) json_out(['error' => 'Liiga palju teateid.'], 429);
$pdo = db();
$r = $pdo->prepare('SELECT user_id FROM reviews WHERE id=?'); $r->execute([$rid]);
$owner = $r->fetchColumn();
if ($owner === false) json_out(['error' => 'Hinnangut ei leitud.'], 404);
if ((int)$owner === (int)$user['id']) json_out(['error' => 'Oma hinnangut ei saa teatada.'], 422);

$pdo->prepare('INSERT IGNORE INTO reports(review_id, user_id) VALUES(?,?)')->execute([$rid, $user['id']]);
$n = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE review_id=?'); $n->execute([$rid]);
if ((int)$n->fetchColumn() >= 3) $pdo->prepare("UPDATE reviews SET status='flagged' WHERE id=? AND status='visible'")->execute([$rid]); // 3 teadet = peidetakse kuni admin vaatab
json_out(['ok' => true]);
