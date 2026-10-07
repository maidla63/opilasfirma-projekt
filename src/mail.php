<?php
declare(strict_types=1);

function secrets_cfg(): array { $f = __DIR__ . '/secrets.php'; return is_file($f) ? (array)(require $f) : []; }
function mail_from(): string { return (string)(secrets_cfg()['mail_from'] ?? 'raport@localhost'); }
function base_url(): string { return rtrim((string)(secrets_cfg()['base_url'] ?? 'http://localhost/koolikriitik'), '/'); }

/** $dry = true: ei saada, vaid salvestab HTML-i kausta storage/outbox (testimiseks). */
function send_mail(string $to, string $subject, string $html, bool $dry = false): bool {
  if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to . $subject)) return false;
  if ($dry) {
    $dir = __DIR__ . '/../storage/outbox';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return file_put_contents($dir . '/' . date('Ymd-His') . '_' . preg_replace('/[^a-z0-9]+/i', '_', $to) . '.html', "<!-- To: $to | Subject: $subject -->\n$html") !== false;
  }
  $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: Lurr voi hitt <" . mail_from() . ">\r\n";
  return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
}
