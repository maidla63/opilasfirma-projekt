<?php
declare(strict_types=1);
require __DIR__ . '/../src/dash.php';

$sid = (int)($_GET['school_id'] ?? 0);
$days = in_array((int)($_GET['days'] ?? 30), [7, 30, 90], true) ? (int)$_GET['days'] : 30;
if (!current_user() || !school_allowed($sid)) { http_response_code(403); exit('Forbidden'); }

$st = db()->prepare("SELECT meal_date, meal_name, rating, eaten_percent, would_eat_again, tags FROM reviews
  WHERE school_id=? AND status='visible' AND meal_date >= ? ORDER BY meal_date DESC, id DESC");
$st->execute([$sid, date('Y-m-d', strtotime("-$days days"))]);

// CSV-süst (=, +, -, @ alguses) kaitseks Excelis
$safe = fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") ? "'" . $v : $v;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sooklaraport_' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF"; // BOM, et Excel loeks täpitähti õigesti
$out = fopen('php://output', 'w');
fputcsv($out, ['Kuupäev', 'Toit', 'Hinne (1-5)', 'Söödud %', 'Sööks uuesti', 'Tagid'], ';');
while ($r = $st->fetch()) {
  fputcsv($out, [$r['meal_date'], $safe($r['meal_name']), $r['rating'], $r['eaten_percent'], $r['would_eat_again'] ? 'jah' : 'ei', $safe($r['tags'])], ';');
}
