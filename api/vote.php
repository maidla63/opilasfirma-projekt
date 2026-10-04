<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$user = api_guard();
$reviewId = (int)($_POST['review_id'] ?? 0);
$type = $_POST['vote_type'] ?? '';
if ($reviewId < 1 || !in_array($type, ['like', 'dislike'], true)) json_out(['error' => 'Vigane päring'], 422);
if (!throttle('vote', 40, 60)) json_out(['error' => 'Rahulikumalt'], 429);

$pdo = db();
$pdo->beginTransaction();
try {
  $ex = $pdo->prepare('SELECT vote_type FROM votes WHERE review_id=? AND user_id=? FOR UPDATE');
  $ex->execute([$reviewId, $user['id']]);
  $cur = $ex->fetchColumn();

  if ($cur === $type) {          // sama nupp uuesti = hääl tagasi
    $pdo->prepare('DELETE FROM votes WHERE review_id=? AND user_id=?')->execute([$reviewId, $user['id']]);
    $mine = null;
  } elseif ($cur) {              // vahetus
    $pdo->prepare('UPDATE votes SET vote_type=? WHERE review_id=? AND user_id=?')->execute([$type, $reviewId, $user['id']]);
    $mine = $type;
  } else {
    $pdo->prepare('INSERT INTO votes (review_id, user_id, vote_type) VALUES (?,?,?)')->execute([$reviewId, $user['id'], $type]);
    $mine = $type;
  }

  // loendurid alati tõest (votes tabelist), mitte +1/-1
  $pdo->prepare("UPDATE reviews SET
      likes_count    = (SELECT COUNT(*) FROM votes WHERE review_id=? AND vote_type='like'),
      dislikes_count = (SELECT COUNT(*) FROM votes WHERE review_id=? AND vote_type='dislike')
      WHERE id=?")->execute([$reviewId, $reviewId, $reviewId]);

  $c = $pdo->prepare('SELECT likes_count, dislikes_count FROM reviews WHERE id=?');
  $c->execute([$reviewId]);
  $counts = $c->fetch();
  $pdo->commit();
} catch (Throwable $t) {
  $pdo->rollBack();
  error_log($t->getMessage());
  json_out(['error' => 'Serveri viga'], 500);
}
json_out(['ok' => true, 'likes' => (int)$counts['likes_count'], 'dislikes' => (int)$counts['dislikes_count'], 'mine' => $mine]);
