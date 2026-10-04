<?php
declare(strict_types=1);
require_once __DIR__ . '/verify_card.php'; // anthropic_key()

/** Lihtne sõimusõnafilter (täienda nimekirja ise). Algus peab olema sõna algus. */
function bad_words(string $t): bool {
  $t = strtr(mb_strtolower($t), ['0' => 'o', '1' => 'i', '3' => 'e', '@' => 'a', '$' => 's']);
  return (bool)preg_match('/(?<!\p{L})(kurat|perse|persse|türa|tyra|munn|pede|neeger|neger|idioot|debiil|tolgast|värdjas|vittu|pask)/u', $t);
}

function claude_call(array $content, int $max = 300): ?string {
  $key = anthropic_key();
  if ($key === '') return null;
  $ch = curl_init('https://api.anthropic.com/v1/messages');
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45,
    CURLOPT_POSTFIELDS => json_encode(['model' => 'claude-haiku-4-5-20251001', 'max_tokens' => $max, 'messages' => [['role' => 'user', 'content' => $content]]]),
    CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01']]);
  $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($res === false || $code !== 200) { error_log("Anthropic API $code"); return null; }
  return json_decode($res, true)['content'][0]['text'] ?? null;
}
function claude_json(?string $t): ?array {
  if ($t === null) return null;
  $j = json_decode(trim((string)preg_replace('/^```(?:json)?|```$/m', '', $t)), true);
  return is_array($j) ? $j : null;
}

/** Laeb pildi GD-ga, vähendab ja annab base64 JPEG. null kui pole pilt. */
function image_b64(string $tmp, int $max): ?string {
  $info = @getimagesize($tmp);
  $img = match ($info[2] ?? 0) { IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp), IMAGETYPE_PNG => @imagecreatefrompng($tmp), IMAGETYPE_WEBP => @imagecreatefromwebp($tmp), default => false };
  if (!$img) return null;
  if (max(imagesx($img), imagesy($img)) > $max) {
    $s = imagesx($img) >= imagesy($img) ? imagescale($img, $max) : imagescale($img, (int)round(imagesx($img) * $max / imagesy($img)), $max);
    if ($s) $img = $s;
  }
  ob_start(); imagejpeg($img, null, 80); return base64_encode((string)ob_get_clean());
}

/** 'ok' | 'blocked' (nägu, isikuandmed, sobimatu) | 'unknown' (kontroll ei õnnestunud -> modereerimisjärjekorda) */
function photo_check(array $f): string {
  $b64 = image_b64($f['tmp_name'], 800);
  if (!$b64) return 'unknown';
  $j = claude_json(claude_call([
    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $b64]],
    ['type' => 'text', 'text' => 'See on koolisöökla toidu foto. Tagasta AINULT JSON: {"face_visible":true|false,"readable_personal_info":true|false,"inappropriate":true|false}. '
      . 'face_visible = selgelt tuvastatav inimese nägu. readable_personal_info = loetav nimi, isikukood või telefoninumber. inappropriate = vägivald, alastus või muu sobimatu. Toit ja söökla on korras.']], 120));
  if (!$j) return 'unknown';
  return (!empty($j['face_visible']) || !empty($j['readable_personal_info']) || !empty($j['inappropriate'])) ? 'blocked' : 'ok';
}
