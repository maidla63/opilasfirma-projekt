<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function display_name(array $u): string {
  return !empty($u['nickname']) ? $u['nickname'] : 'Õpilane #' . strtoupper(substr(md5($u['id'] . 'kk-salt'), 0, 4));
}

function user_stats(int $uid): array {
  $pdo = db();
  $s = $pdo->prepare("SELECT COUNT(*) n, COALESCE(SUM(image_path IS NOT NULL),0) photos, COALESCE(SUM(likes_count),0) likes,
                      COALESCE(SUM(eaten_percent=100),0) clean FROM reviews WHERE user_id=? AND status='visible'");
  $s->execute([$uid]); $r = array_map('intval', $s->fetch());
  // pikim koolipäevade jada (nädalavahetus ei katkesta)
  $d = $pdo->prepare("SELECT DISTINCT meal_date FROM reviews WHERE user_id=? AND status='visible' AND meal_date IS NOT NULL ORDER BY meal_date");
  $d->execute([$uid]); $best = 0; $run = 0; $prev = null;
  foreach ($d->fetchAll(PDO::FETCH_COLUMN) as $day) {
    $run = ($prev !== null && date('Y-m-d', strtotime("$prev +1 weekday")) === $day) ? $run + 1 : 1;
    $best = max($best, $run); $prev = $day;
  }
  $r['streak'] = $best;
  $r['xp'] = $r['n'] * 10 + $r['photos'] * 5 + $r['likes'] * 2 + $best * 3;
  return $r;
}

function badges(array $s): array {
  $defs = [
    ['🥄', 'Esimene suutäis', 'Esimene hinnang', $s['n'] >= 1],
    ['🧐', 'Kriitik', '25 hinnangut', $s['n'] >= 25],
    ['🏆', 'Gurmaan', '100 hinnangut', $s['n'] >= 100],
    ['📸', 'Fotograaf', '5 hinnangut pildiga', $s['photos'] >= 5],
    ['🔥', 'Nädal järjest', '7 koolipäeva järjest hinnanud', $s['streak'] >= 7],
    ['👍', 'Populaarne', '50 saadud laiki', $s['likes'] >= 50],
    ['♻️', 'Puhas taldrik', '10× sõi kõik ära', $s['clean'] >= 10],
  ];
  return array_map(fn($b) => ['icon' => $b[0], 'name' => $b[1], 'desc' => $b[2], 'earned' => $b[3]], $defs);
}

function level(int $xp): array {
  $l = (int)floor(sqrt($xp / 25)) + 1;
  $lo = 25 * ($l - 1) ** 2; $hi = 25 * $l ** 2;
  return ['level' => $l, 'pct' => (int)round(($xp - $lo) / ($hi - $lo) * 100), 'next' => $hi];
}
