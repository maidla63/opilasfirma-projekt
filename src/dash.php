<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** Koolid, mille andmeid see kasutaja tohib näha. Admin = kõik, haldur = oma kool. */
function allowed_schools(): array {
  $u = current_user();
  if (!$u) return [];
  $pdo = db();
  if (($u['role'] ?? '') === 'admin') return $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();
  $s = $pdo->prepare('SELECT s.id, s.name FROM users u JOIN schools s ON s.id = u.manages_school_id WHERE u.id = ?');
  $s->execute([$u['id']]);
  return $s->fetchAll();
}

function school_allowed(int $sid): bool {
  foreach (allowed_schools() as $s) if ((int)$s['id'] === $sid) return true;
  return false;
}

function dash_data(int $sid, int $days): array {
  $pdo = db();
  $to   = date('Y-m-d', strtotime('+1 day'));
  $from = date('Y-m-d', strtotime("-$days days"));
  $prev = date('Y-m-d', strtotime('-' . ($days * 2) . ' days'));
  $q = function (string $sql, array $p) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); };
  $rd = fn($v, int $d = 1) => $v === null ? null : round((float)$v, $d);
  $waste = fn($e) => $e === null ? null : round(100 - (float)$e, 0);
  $base = "FROM reviews WHERE status='visible' AND meal_date >= ? AND meal_date < ?";

  $kpi = function (string $a, string $b) use ($q, $sid, $rd, $waste, $base) {
    $r = $q("SELECT COUNT(*) n, AVG(rating) r, AVG(eaten_percent) e, AVG(would_eat_again)*100 a $base AND school_id=?", [$a, $b, $sid])[0];
    return ['n' => (int)$r['n'], 'rating' => $rd($r['r'], 2), 'waste' => $waste($r['e']), 'again' => $rd($r['a'], 0)];
  };
  $cur = $kpi($from, $to);
  $old = $kpi($prev, $from);

  $all = $q("SELECT AVG(rating) r, AVG(eaten_percent) e $base", [$from, $to])[0];
  $rank = $q("SELECT school_id, AVG(rating) r $base GROUP BY school_id ORDER BY r DESC", [$from, $to]);
  $pos = null;
  foreach ($rank as $i => $row) if ((int)$row['school_id'] === $sid) $pos = $i + 1;

  $trend = array_map(fn($t) => ['d' => $t['d'], 'n' => (int)$t['n'], 'r' => $rd($t['r'], 2), 'w' => $waste($t['e'])],
    $q("SELECT meal_date d, COUNT(*) n, AVG(rating) r, AVG(eaten_percent) e $base AND school_id=? GROUP BY meal_date ORDER BY meal_date", [$from, $to, $sid]));

  $week = array_map(fn($t) => ['w' => (int)$t['w'], 'n' => (int)$t['n'], 'r' => $rd($t['r'], 2), 'waste' => $waste($t['e'])],
    $q("SELECT WEEKDAY(meal_date) w, COUNT(*) n, AVG(rating) r, AVG(eaten_percent) e $base AND school_id=? GROUP BY w ORDER BY w", [$from, $to, $sid]));

  $meals = array_map(fn($t) => ['meal' => $t['m'], 'n' => (int)$t['n'], 'r' => $rd($t['r'], 2), 'waste' => $waste($t['e'])],
    $q("SELECT MIN(meal_name) m, COUNT(*) n, AVG(rating) r, AVG(eaten_percent) e $base AND school_id=? GROUP BY LOWER(meal_name) HAVING COUNT(*) >= 2", [$from, $to, $sid]));
  $byR = $meals; usort($byR, fn($a, $b) => $a['r'] <=> $b['r']);
  $byW = array_values(array_filter($meals, fn($m) => $m['waste'] !== null)); usort($byW, fn($a, $b) => $b['waste'] <=> $a['waste']);

  $tags = [];
  foreach ($q("SELECT tags $base AND school_id=? AND tags <> ''", [$from, $to, $sid]) as $row)
    foreach (explode(',', $row['tags']) as $t) $tags[$t] = ($tags[$t] ?? 0) + 1;
  arsort($tags);

  // anonüümne võrdlus: ainult koolid, kus on vähemalt 5 hinnangut, ja vähemalt 3 kooli (nimesid ei avaldata)
  $sch = $q("SELECT AVG(rating) r, AVG(eaten_percent) e $base GROUP BY school_id HAVING COUNT(*) >= 5", [$from, $to]);
  $bm = null;
  if (count($sch) >= 3) {
    $rs = array_map(fn($x) => (float)$x['r'], $sch); sort($rs);
    $ws = array_values(array_map(fn($x) => 100 - (float)$x['e'], array_filter($sch, fn($x) => $x['e'] !== null))); sort($ws);
    $at = fn(array $a, float $p) => $a ? $a[(int)floor((count($a) - 1) * $p)] : null;
    $mineR = $cur['n'] >= 5 ? $cur['rating'] : null;
    $mineW = $cur['n'] >= 5 ? $cur['waste'] : null;
    $bm = ['schools' => count($sch),
      'rating' => ['median' => $rd($at($rs, .5), 2), 'top' => $rd($at($rs, .75), 2)],
      'waste' => ['median' => $rd($at($ws, .5), 0), 'top' => $rd($at($ws, .25), 0)],
      'pctRating' => $mineR === null ? null : (int)round(100 * count(array_filter($rs, fn($v) => $v < $mineR)) / count($rs)),
      'pctWaste' => ($mineW === null || !$ws) ? null : (int)round(100 * count(array_filter($ws, fn($v) => $v > $mineW)) / count($ws))];
  }

  return [
    'days' => $days, 'kpi' => $cur, 'prev' => $old, 'bench' => $bm,
    'market' => ['rating' => $rd($all['r'], 2), 'waste' => $waste($all['e'])],
    'rank' => ['pos' => $pos, 'of' => count($rank)],
    'trend' => $trend, 'week' => $week,
    'worst' => array_slice($byR, 0, 5), 'best' => array_slice(array_reverse($byR), 0, 5), 'wasted' => array_slice($byW, 0, 5),
    'tags' => $tags,
  ];
}
