<?php
session_start();
$host='127.0.0.1'; $db='koolikriitik'; $user='root'; $pass=''; $charset='utf8mb4';
$pdo=new PDO("mysql:host=$host;dbname=$db;charset=$charset",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function esc($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
if(!isset($_SESSION['user'])){ header("Location: ./index.php"); exit; }
if(($_SESSION['user']['role'] ?? '')!=='admin'){ http_response_code(403); exit('Forbidden'); }

$flash='';

/* Delete review */
if(isset($_POST['action']) && $_POST['action']==='delete_review'){
  $id=(int)($_POST['review_id']??0);
  if($id>0){
    $q=$pdo->prepare("SELECT image_path FROM reviews WHERE id=?"); $q->execute([$id]); $r=$q->fetch();
    $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
    if($r && !empty($r['image_path'])){
      $path=__DIR__.'/'.$r['image_path'];
      if(is_file($path)) @unlink($path);
    }
    $flash='Review kustutatud.';
  }
}

/* Role management */
if(isset($_POST['action']) && $_POST['action']==='set_role'){
  $uid=(int)($_POST['user_id']??0);
  $role=$_POST['role']??'user';
  if($uid>0 && in_array($role,['user','admin'],true)){
    $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
    if($_SESSION['user']['id']===$uid){ $_SESSION['user']['role']=$role; }
    $flash='Roll uuendatud.';
  }
}

/* Export */
if(isset($_GET['export'])){
  $type=$_GET['export'];
  $rows=$pdo->query("SELECT r.id,r.school,r.meal_name,r.rating,r.would_eat_again,r.comment,r.likes_count,r.dislikes_count,r.created_at,u.full_name FROM reviews r JOIN users u ON u.id=r.user_id ORDER BY r.id DESC")->fetchAll();
  if($type==='json'){
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=reviews.json');
    echo json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); exit;
  }
  if($type==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reviews.csv');
    $out=fopen('php://output','w');
    fputcsv($out,['id','school','meal_name','rating','would_eat_again','comment','likes','dislikes','created_at','author']);
    foreach($rows as $row){
      fputcsv($out,[$row['id'],$row['school'],$row['meal_name'],$row['rating'],$row['would_eat_again'],$row['comment'],$row['likes_count'],$row['dislikes_count'],$row['created_at'],$row['full_name']]);
    }
    fclose($out); exit;
  }
}

/* Data for dashboard */
$stats=$pdo->query("SELECT COUNT(*) total, COALESCE(AVG(rating),0) avg_rating, COALESCE(SUM(would_eat_again),0) again_count FROM reviews")->fetch();
$total=(int)$stats['total']; $avg=(float)$stats['avg_rating']; $againPct=$total?round($stats['again_count']/$total*100):0;

$bySchool=$pdo->query("SELECT school, ROUND(AVG(rating),2) avg_rating, COUNT(*) cnt FROM reviews GROUP BY school ORDER BY avg_rating DESC")->fetchAll();
$recent=$pdo->query("SELECT r.*,u.full_name FROM reviews r JOIN users u ON u.id=r.user_id ORDER BY r.id DESC LIMIT 100")->fetchAll();
$users=$pdo->query("SELECT id,full_name,email,role,school,created_at FROM users ORDER BY id DESC")->fetchAll();
?>
<!doctype html><html lang="et"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--bg:#f6f7fb;--card:#fff;--text:#111;--muted:#666;--line:#e5e7eb;--pri:#2563eb;--danger:#dc2626}
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:var(--bg);color:var(--text)}
.container{width:min(1200px,92%);margin:0 auto}.top{background:#fff;border-bottom:1px solid var(--line)}
.topin{min-height:62px;display:flex;justify-content:space-between;align-items:center}.row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.btn{border:1px solid var(--line);padding:8px 11px;border-radius:10px;background:#fff;cursor:pointer;text-decoration:none;color:#111}
.btnp{background:var(--pri);color:#fff;border:none}.btnd{background:var(--danger);color:#fff;border:none}
.page{padding:16px 0}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px}
@media(max-width:900px){.grid{grid-template-columns:1fr 1fr}}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid var(--line);padding:8px;text-align:left}
small{color:var(--muted)}.flash{background:#ecfeff;border:1px solid #99f6e4;padding:8px;border-radius:8px;margin-bottom:10px}
</style></head><body>
<header class="top"><div class="container topin">
  <strong>Admin Dashboard</strong>
  <div class="row">
    <a class="btn" href="./index.php">Avaleht</a>
    <a class="btn" href="./admin.php?export=json">Export JSON</a>
    <a class="btn" href="./admin.php?export=csv">Export CSV</a>
  </div>
</div></header>

<main class="container page">
  <?php if($flash): ?><div class="flash"><?= esc($flash) ?></div><?php endif; ?>

  <section class="grid">
    <article class="card"><small>Kõik hinnangud</small><h2><?= $total ?></h2></article>
    <article class="card"><small>Keskmine hinne</small><h2><?= number_format($avg,2) ?></h2></article>
    <article class="card"><small>Sööks uuesti</small><h2><?= $againPct ?>%</h2></article>
    <article class="card"><small>Raiskamise risk (proxy)</small><h2><?= 100-$againPct ?>%</h2></article>
  </section>

  <section class="card" style="margin-top:12px">
    <h3>Hinne koolide lõikes</h3>
    <canvas id="schoolChart" height="90"></canvas>
  </section>

  <section class="card" style="margin-top:12px">
    <h3>Reviewd (kustutamine)</h3>
    <table>
      <thead><tr><th>ID</th><th>Kool</th><th>Toit</th><th>Hinne</th><th>Pilt</th><th>Autor</th><th>Aeg</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($recent as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= esc($r['school']) ?></td>
          <td><?= esc($r['meal_name']) ?></td>
          <td><?= (int)$r['rating'] ?>/5</td>
          <td><?php if(!empty($r['image_path'])): ?><a href="<?= esc($r['image_path']) ?>" target="_blank">vaata</a><?php else: ?><small>-</small><?php endif; ?></td>
          <td><?= esc($r['full_name']) ?></td>
          <td><?= esc($r['created_at']) ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Kustuta review?')">
              <input type="hidden" name="action" value="delete_review">
              <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btnd">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="card" style="margin-top:12px">
    <h3>Kasutajad & rollid</h3>
    <table>
      <thead><tr><th>ID</th><th>Nimi</th><th>Email</th><th>Kool</th><th>Roll</th><th>Muuda</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= esc($u['full_name']) ?></td>
          <td><?= esc($u['email']) ?></td>
          <td><?= esc($u['school']) ?></td>
          <td><strong><?= esc($u['role']) ?></strong></td>
          <td>
            <form method="post" class="row">
              <input type="hidden" name="action" value="set_role">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <select name="role">
                <option value="user" <?= $u['role']==='user'?'selected':'' ?>>user</option>
                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
              </select>
              <button class="btn btnp">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<script>
const labels = <?= json_encode(array_map(fn($x)=>$x['school'],$bySchool), JSON_UNESCAPED_UNICODE) ?>;
const data = <?= json_encode(array_map(fn($x)=>(float)$x['avg_rating'],$bySchool), JSON_UNESCAPED_UNICODE) ?>;
new Chart(document.getElementById('schoolChart'), {
  type:'bar',
  data:{ labels, datasets:[{ label:'Keskmine hinne', data }]},
  options:{ scales:{ y:{ min:0, max:5 } } }
});
</script>
</body></html>