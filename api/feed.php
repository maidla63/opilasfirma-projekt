<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$sid = (int)($_GET['school_id'] ?? 0);
$off = max(0, (int)($_GET['offset'] ?? 0));
$uid = (int)(current_user()['id'] ?? 0);

$sql = "SELECT r.*, v.vote_type AS mine, u.nickname FROM reviews r JOIN users u ON u.id = r.user_id
        LEFT JOIN votes v ON v.review_id = r.id AND v.user_id = :u
        WHERE r.status = 'visible'" . ($sid ? ' AND r.school_id = :s' : '') . "
        ORDER BY r.id DESC LIMIT 20 OFFSET :o";
$st = db()->prepare($sql);
$st->bindValue(':u', $uid, PDO::PARAM_INT);
if ($sid) $st->bindValue(':s', $sid, PDO::PARAM_INT);
$st->bindValue(':o', $off, PDO::PARAM_INT);
$st->execute();

$out = [];
foreach ($st->fetchAll() as $r) {
  $out[] = [
    'id' => (int)$r['id'], 'school' => $r['school'], 'meal' => $r['meal_name'], 'rating' => (int)$r['rating'],
    'again' => (bool)$r['would_eat_again'], 'eaten' => $r['eaten_percent'] === null ? null : (int)$r['eaten_percent'],
    'tags' => $r['tags'] === '' ? [] : explode(',', $r['tags']), 'date' => $r['meal_date'] ?: substr((string)$r['created_at'], 0, 10),
    'comment' => $r['comment'], 'image' => $r['image_path'],
    'likes' => (int)$r['likes_count'], 'dislikes' => (int)$r['dislikes_count'], 'mine' => $r['mine'],
    'author' => $r['nickname'] ?: 'Õpilane #' . strtoupper(substr(md5($r['user_id'] . 'kk-salt'), 0, 4)), 'uid' => (int)$r['user_id'], // päris nime ei avaldata
    'own' => $uid && (int)$r['user_id'] === $uid,
  ];
}
json_out(['reviews' => $out, 'more' => count($out) === 20]);
