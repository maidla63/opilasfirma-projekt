<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
$pdo = db();

$g = $pdo->query("SELECT COUNT(*) n, COALESCE(AVG(rating),3) a, AVG(eaten_percent) e FROM reviews WHERE status='visible'")->fetch();
$C = (float)$g['a']; $m = 5; // Bayesi keskmine: vähe hinnanguga koolid ei võida kunstlikult

$rows = $pdo->query("SELECT s.id, s.name, COUNT(r.id) cnt, AVG(r.rating) avg_r, AVG(r.eaten_percent) eaten
                     FROM schools s JOIN reviews r ON r.school_id = s.id AND r.status='visible' GROUP BY s.id, s.name")->fetchAll();
foreach ($rows as &$r) {
  $v = (int)$r['cnt']; $R = (float)$r['avg_r'];
  $r = ['id' => (int)$r['id'], 'name' => $r['name'], 'count' => $v, 'avg' => round($R, 2),
        'score' => round(($v / ($v + $m)) * $R + ($m / ($v + $m)) * $C, 2),
        'waste' => $r['eaten'] === null ? null : (int)round(100 - (float)$r['eaten'])];
}
unset($r);
usort($rows, fn($a, $b) => $b['score'] <=> $a['score']);

// päeva hitt ja lurr (täna, vähemalt 1 hinnang)
$pick = function (string $dir) use ($pdo) {
  return $pdo->query("SELECT meal_name, school, ROUND(AVG(rating),1) a, COUNT(*) c FROM reviews
      WHERE status='visible' AND meal_date = CURDATE() GROUP BY school_id, meal_name, school
      ORDER BY AVG(rating) $dir, COUNT(*) DESC LIMIT 1")->fetch() ?: null;
};
$schools = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();

json_out(['board' => $rows, 'schools' => $schools, 'hit' => $pick('DESC'), 'lurr' => $pick('ASC'),
          'stats' => ['total' => (int)$g['n'], 'avg' => round($C, 1), 'waste' => $g['e'] === null ? null : (int)round(100 - (float)$g['e'])]]);
