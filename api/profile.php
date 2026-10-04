<?php
declare(strict_types=1);
require __DIR__ . '/../src/profile.php';
require __DIR__ . '/../src/moderation.php';

$user = api_guard();
$nick = trim((string)($_POST['nickname'] ?? ''));
if ($nick !== '' && !preg_match('/^[\p{L}\p{N}_\- ]{3,20}$/u', $nick)) json_out(['error' => '3–20 märki: tähed, numbrid, tühik, _ ja -.'], 422);
if ($nick !== '' && bad_words($nick)) json_out(['error' => 'Sobimatu kasutajanimi.'], 422);
try { db()->prepare('UPDATE users SET nickname=? WHERE id=?')->execute([$nick === '' ? null : $nick, $user['id']]); }
catch (PDOException $e) { json_out(['error' => 'See kasutajanimi on juba võetud.'], 409); }
json_out(['ok' => true]);
