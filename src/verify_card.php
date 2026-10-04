<?php
declare(strict_types=1);

function anthropic_key(): string {
  $f = __DIR__ . '/secrets.php';
  $c = is_file($f) ? (require $f) : [];
  return getenv('ANTHROPIC_API_KEY') ?: (string)($c['anthropic_key'] ?? '');
}

function norm_school(string $s): string {
  $s = mb_strtolower($s);
  $s = preg_replace('/\b(kool|gümnaasium|põhikool|keskkool|ja|õppekeskus)\b/u', ' ', $s);
  return preg_replace('/[^\p{L}\p{N}]+/u', '', $s) ?: '';
}
function school_matches(string $onCard, string $chosen): bool {
  preg_match_all('/\d+/', $onCard, $n1); preg_match_all('/\d+/', $chosen, $n2);
  if ($n1[0] !== $n2[0]) return false; // 21. kool ja 22. kool on eri koolid
  $a = norm_school($onCard); $b = norm_school($chosen);
  if ($a === '' || $b === '') return false;
  if (min(mb_strlen($a), mb_strlen($b)) >= 4 && (str_contains($a, $b) || str_contains($b, $a))) return true;
  similar_text($a, $b, $pct);
  return $pct >= 75;
}

/** Pilti EI salvestata kettale: loetakse mällu, vähendatakse, saadetakse kontrolli ja visatakse ära. */
function verify_student_card(array $f, ?string $schoolName = null): array {
  $no = fn(string $m) => ['ok' => false, 'reason' => $m];
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return $no('Lisa õpilaspileti pilt.');
  if ($f['size'] > 8 * 1024 * 1024) return $no('Pilt on liiga suur (max 8MB).');
  $info = @getimagesize($f['tmp_name']);
  $img = match ($info[2] ?? 0) {
    IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']), IMAGETYPE_PNG => @imagecreatefrompng($f['tmp_name']),
    IMAGETYPE_WEBP => @imagecreatefromwebp($f['tmp_name']), default => false };
  if (!$img) return $no('Lubatud: JPG, PNG, WEBP.');
  if (max(imagesx($img), imagesy($img)) > 1280) {
    $s = imagesx($img) >= imagesy($img) ? imagescale($img, 1280) : imagescale($img, (int)round(imagesx($img) * 1280 / imagesy($img)), 1280);
    if ($s) $img = $s;
  }
  ob_start(); imagejpeg($img, null, 82); $b64 = base64_encode((string)ob_get_clean());

  $key = anthropic_key();
  if ($key === '') { error_log('ANTHROPIC_API_KEY puudub'); return $no('Kontroll pole hetkel saadaval.'); }

  $prompt = 'Kontrolli, kas pilt on Eesti kooli õpilaspilet. Tagasta AINULT JSON ilma lisatekstita: '
    . '{"is_student_card":true|false,"school_name":string|null,"valid":true|false|null,"is_screenshot_or_copy":true|false,"confidence":0..1}. '
    . 'valid = kas kaardil olev kehtivusaeg või õppeaasta hõlmab tänast kuupäeva (' . date('Y-m-d') . '), null kui seda pole näha. '
    . 'is_screenshot_or_copy = true, kui pilt on ekraanipilt või pilt ekraanist. '
    . 'ÄRA kirjuta välja nime, isikukoodi ega muid isikuandmeid, ainult kooli nimi.';
  $payload = json_encode(['model' => 'claude-haiku-4-5-20251001', 'max_tokens' => 200, 'messages' => [['role' => 'user', 'content' => [
    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $b64]],
    ['type' => 'text', 'text' => $prompt]]]]]);

  $ch = curl_init('https://api.anthropic.com/v1/messages');
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01']]);
  $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  unset($b64, $payload, $img);
  if ($res === false || $code !== 200) { error_log("Anthropic API $code"); return $no('Kontrolli ei saanud teha. Proovi mõne minuti pärast.'); }

  $text = json_decode($res, true)['content'][0]['text'] ?? '';
  $j = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $text)), true);
  if (!is_array($j)) return $no('Kontrolli ei saanud teha. Proovi uue pildiga.');

  if (empty($j['is_student_card']) || (float)($j['confidence'] ?? 0) < 0.7) return $no('See ei tundu õpilaspiletina. Tee selge pilt, kus on kooli nimi ja kehtivusaeg näha.');
  if (!empty($j['is_screenshot_or_copy'])) return $no('Lisa foto päris õpilaspiletist, mitte ekraanipilt.');
  if (($j['valid'] ?? null) === false) return $no('Õpilaspilet ei ole kehtiv.');
  $card = mb_substr(trim((string)preg_replace('/[^\p{L}\p{N} .,\-\'"]/u', '', (string)($j['school_name'] ?? ''))), 0, 150);
  if (mb_strlen($card) < 3) return $no('Kooli nime ei õnnestunud piletilt lugeda. Tee selgem pilt.');
  if ($schoolName !== null && !school_matches($card, $schoolName)) return $no('Kooli nimi piletil ei klapi valitud kooliga.');
  return ['ok' => true, 'reason' => '', 'school' => $card];
}
