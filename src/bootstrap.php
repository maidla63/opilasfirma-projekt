<?php
declare(strict_types=1);

// --- seaded (päris serveris loe .env-ist, ära commit'i paroole) ---
const DB_HOST = '127.0.0.1';
const DB_NAME = 'koolikriitik';
const DB_USER = 'root';
const DB_PASS = '';
const UPLOAD_DIR = __DIR__ . '/../uploads';
const UPLOAD_URL = 'uploads';

ini_set('display_errors', '0'); // vead logisse, mitte kasutajale
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
  session_start();
}

function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
  }
  return $pdo;
}

function e(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }

function csrf_token(): string {
  return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_ok(): bool {
  $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? '';
  return is_string($sent) && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

function json_out(array $data, int $code = 200): never {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

/** Ühine kaitse kõigile kirjutavatele API-dele. */
function api_guard(): array {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_out(['error' => 'Ainult POST'], 405);
  if (!csrf_ok()) json_out(['error' => 'Seanss aegus, laadi leht uuesti'], 419);
  $u = current_user();
  if (!$u) json_out(['error' => 'Logi sisse'], 401);
  if (empty($u['verified'])) json_out(['error' => 'Konto pole õpilaspiletiga kinnitatud. Logi uuesti sisse.'], 403);
  return $u;
}

/** Lihtne piirang: max $n toimingut $sec sekundi jooksul (seansi põhine, hiljem IP-põhine). */
function throttle(string $key, int $n, int $sec): bool {
  $now = time();
  $hits = array_filter($_SESSION['thr'][$key] ?? [], fn($t) => $t > $now - $sec);
  if (count($hits) >= $n) return false;
  $hits[] = $now;
  $_SESSION['thr'][$key] = $hits;
  return true;
}
