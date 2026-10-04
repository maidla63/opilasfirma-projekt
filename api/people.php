<?php
declare(strict_types=1);
require __DIR__ . '/../src/profile.php';

$period = ($_GET['p'] ?? 'week') === 'all' ? '' : 'AND r.created_at >= (NOW() - INTERVAL 7 DAY)';
$order = ($_GET['m'] ?? 'reviews') === 'liked' ? 'likes' : 'n'; // valge nimekiri, SQL-i ei satu kasutaja teksti
$rows = db()->query("SELECT u.id, u.nickname, s.name school, COUNT(r.id) n, COALESCE(SUM(r.likes_count),0) likes
  FROM reviews r JOIN users u ON u.id = r.user_id LEFT JOIN schools s ON s.id = r.school_id
  WHERE r.status='visible' $period GROUP BY u.id, u.nickname, s.name ORDER BY $order DESC, n DESC LIMIT 20")->fetchAll();
json_out(['people' => array_map(fn($u) => ['id' => (int)$u['id'], 'name' => display_name($u), 'school' => $u['school'], 'n' => (int)$u['n'], 'likes' => (int)$u['likes']], $rows)]);
