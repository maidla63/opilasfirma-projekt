<?php
session_start();

$host='127.0.0.1'; $db='koolikriitik'; $user='root'; $pass=''; $charset='utf8mb4';
$pdo=null; $dbError=null;
try{
  $pdo=new PDO("mysql:host=$host;dbname=$db;charset=$charset",$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
}catch(Throwable $e){ $dbError=$e->getMessage(); }

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function logged_in(){ return isset($_SESSION['user']); }
function is_admin(){ return (($_SESSION['user']['role'] ?? '') === 'admin'); }

$flash='';

/* Auth */
if ($pdo && isset($_POST['action']) && $_POST['action']==='register'){
  $name=trim($_POST['full_name']??'');
  $email=trim($_POST['email']??'');
  $school=trim($_POST['school']??'');
  $password=$_POST['password']??'';
  if(!$name||!$email||!$password){ $flash='Täida kõik väljad.'; }
  else{
    try{
      $hash=password_hash($password,PASSWORD_DEFAULT);
      $pdo->prepare("INSERT INTO users(full_name,email,password_hash,role,school) VALUES(?,?,?,?,?)")
          ->execute([$name,$email,$hash,'user',$school?:null]);
      $flash='Konto loodud. Logi sisse.';
    }catch(Throwable $e){ $flash='Email on juba kasutusel.'; }
  }
}
if ($pdo && isset($_POST['action']) && $_POST['action']==='login'){
  $email=trim($_POST['email']??''); $password=$_POST['password']??'';
  $q=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1"); $q->execute([$email]); $u=$q->fetch();
  if($u && password_verify($password,$u['password_hash'])){
    $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role'],'school'=>$u['school']];
    header("Location: ./index.php"); exit;
  } else $flash='Vale email või parool.';
}
if(isset($_GET['logout'])){ session_destroy(); header("Location: ./index.php"); exit; }

/* Add review + image upload */
if($pdo && logged_in() && isset($_POST['action']) && $_POST['action']==='add_review'){
  $school=trim($_POST['school']??'');
  $meal=trim($_POST['meal_name']??'');
  $rating=(int)($_POST['rating']??3);
  $again=isset($_POST['would_eat_again'])?1:0;
  $comment=trim($_POST['comment']??'');
  $imagePath=null;

  if(isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
    if($_FILES['image']['error']===UPLOAD_ERR_OK){
      $tmp=$_FILES['image']['tmp_name'];
      $size=(int)$_FILES['image']['size'];
      $mime=mime_content_type($tmp);
      $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      if($size<=4*1024*1024 && isset($allowed[$mime])){
        $ext=$allowed[$mime];
        $file='rev_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $dir=__DIR__.'/uploads';
        if(!is_dir($dir)) mkdir($dir,0777,true);
        if(move_uploaded_file($tmp,$dir.'/'.$file)){
          $imagePath='uploads/'.$file;
        }
      } else {
        $flash='Pilt peab olema JPG/PNG/WEBP ja max 4MB.';
      }
    }
  }

  if(!$flash && $school && $meal && $comment && $rating>=1 && $rating<=5){
    $pdo->prepare("INSERT INTO reviews(user_id,school,meal_name,rating,would_eat_again,comment,image_path) VALUES(?,?,?,?,?,?,?)")
        ->execute([$_SESSION['user']['id'],$school,$meal,$rating,$again,$comment,$imagePath]);
    header("Location: ./index.php"); exit;
  } elseif(!$flash) $flash='Kontrolli sisestatud andmeid.';
}

/* Vote */
if($pdo && logged_in() && isset($_POST['action']) && $_POST['action']==='vote'){
  $reviewId=(int)($_POST['review_id']??0); $type=$_POST['vote_type']??'';
  if($reviewId>0 && in_array($type,['like','dislike'],true)){
    $q=$pdo->prepare("SELECT id,vote_type FROM votes WHERE review_id=? AND user_id=? LIMIT 1");
    $q->execute([$reviewId,$_SESSION['user']['id']]); $v=$q->fetch();
    if(!$v){
      $pdo->prepare("INSERT INTO votes(review_id,user_id,vote_type) VALUES(?,?,?)")->execute([$reviewId,$_SESSION['user']['id'],$type]);
      $pdo->prepare("UPDATE reviews SET ".($type==='like'?'likes_count=likes_count+1':'dislikes_count=dislikes_count+1')." WHERE id=?")->execute([$reviewId]);
    } elseif($v['vote_type']!==$type){
      $pdo->prepare("UPDATE votes SET vote_type=? WHERE id=?")->execute([$type,$v['id']]);
      if($type==='like'){
        $pdo->prepare("UPDATE reviews SET likes_count=likes_count+1, dislikes_count=GREATEST(dislikes_count-1,0) WHERE id=?")->execute([$reviewId]);
      } else {
        $pdo->prepare("UPDATE reviews SET dislikes_count=dislikes_count+1, likes_count=GREATEST(likes_count-1,0) WHERE id=?")->execute([$reviewId]);
      }
    }
  }
  header("Location: ./index.php"); exit;
}

/* Read data */
$schoolFilter=trim($_GET['school']??'');
$reviews=[]; $leaderboard=[]; $schools=[]; $stats=['total'=>0,'avg'=>0,'again'=>0];
if($pdo){
  $schools=$pdo->query("SELECT DISTINCT school FROM reviews ORDER BY school")->fetchAll();
  if($schoolFilter!==''){
    $q=$pdo->prepare("SELECT r.*,u.full_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.school=? ORDER BY r.id DESC LIMIT 100");
    $q->execute([$schoolFilter]); $reviews=$q->fetchAll();
  } else {
    $reviews=$pdo->query("SELECT r.*,u.full_name FROM reviews r JOIN users u ON u.id=r.user_id ORDER BY r.id DESC LIMIT 100")->fetchAll();
  }
  $leaderboard=$pdo->query("SELECT school,ROUND(AVG(rating),2) avg_rating,COUNT(*) cnt FROM reviews GROUP BY school ORDER BY avg_rating DESC,cnt DESC")->fetchAll();
  $s=$pdo->query("SELECT COUNT(*) total, COALESCE(AVG(rating),0) avg_rating, COALESCE(SUM(would_eat_again),0) again_count FROM reviews")->fetch();
  $stats['total']=(int)$s['total']; $stats['avg']=(float)$s['avg_rating']; $stats['again']=$stats['total']?round($s['again_count']/$stats['total']*100):0;
}
?>
<!doctype html><html lang="et"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Koolisööklate Toidukriitik</title>
<style>
:root{--bg:#f6f7fb;--card:#fff;--text:#111;--muted:#666;--line:#e5e7eb;--pri:#2563eb}
.dark{--bg:#0b1220;--card:#111827;--text:#e5e7eb;--muted:#9ca3af;--line:#2b3648;--pri:#60a5fa}
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:var(--bg);color:var(--text)}
.container{width:min(1180px,92%);margin:0 auto}.top{position:sticky;top:0;background:var(--card);border-bottom:1px solid var(--line)}
.topin{min-height:62px;display:flex;justify-content:space-between;align-items:center}.row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.btn{border:1px solid var(--line);padding:8px 11px;border-radius:10px;background:transparent;color:var(--text);cursor:pointer;text-decoration:none}
.btnp{background:var(--pri);color:#fff;border:none}.page{padding:16px 0}.grid{display:grid;grid-template-columns:320px 1fr 280px;gap:12px}
@media(max-width:980px){.grid{grid-template-columns:1fr}}.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:12px}
.form{display:grid;gap:8px}.form input,.form textarea,.form select{padding:9px;border:1px solid var(--line);background:transparent;color:var(--text);border-radius:8px;width:100%}
.review{border:1px solid var(--line);border-radius:10px;padding:9px;margin:8px 0}.muted{color:var(--muted)}.leader li{margin:8px 0;padding:8px;border:1px solid var(--line);border-radius:8px}
img.preview{max-width:100%;max-height:240px;border-radius:10px;border:1px solid var(--line)}.flash{background:#fef3c7;color:#7c2d12;padding:8px;border-radius:8px;margin-bottom:10px}
</style></head><body>
<header class="top"><div class="container topin">
  <strong>Koolisööklate Toidukriitik</strong>
  <div class="row">
    <button id="themeBtn" class="btn">🌙/☀️</button>
    <?php if(logged_in()): ?>
      <span class="muted"><?= esc($_SESSION['user']['name']) ?> (<?= esc($_SESSION['user']['role']) ?>)</span>
      <?php if(is_admin()): ?><a class="btn" href="./admin.php">Admin</a><?php endif; ?>
      <a class="btn" href="./index.php?logout=1">Logout</a>
    <?php endif; ?>
  </div>
</div></header>

<main class="container page">
  <?php if($dbError): ?><div class="flash">DB error: <?= esc($dbError) ?></div><?php endif; ?>
  <?php if($flash): ?><div class="flash"><?= esc($flash) ?></div><?php endif; ?>

  <?php if(!logged_in()): ?>
    <div class="grid">
      <section class="card">
        <h2>Login</h2>
        <form method="post" class="form">
          <input type="hidden" name="action" value="login">
          <input name="email" type="email" placeholder="Email" required>
          <input name="password" type="password" placeholder="Parool" required>
          <button class="btn btnp">Logi sisse</button>
        </form>
      </section>
      <section class="card">
        <h2>Register</h2>
        <form method="post" class="form">
          <input type="hidden" name="action" value="register">
          <input name="full_name" placeholder="Nimi" required>
          <input name="email" type="email" placeholder="Email" required>
          <input name="school" placeholder="Kool (valikuline)">
          <input name="password" type="password" placeholder="Parool" required>
          <button class="btn btnp">Registreeri</button>
        </form>
      </section>
      <aside class="card"><h3>Features</h3><ul><li>Leaderboard</li><li>Like/Dislike</li><li>Pildi upload</li><li>Admin analytics</li></ul></aside>
    </div>
  <?php else: ?>
    <div class="grid">
      <aside class="card">
        <h2>Lisa hinnang</h2>
        <form method="post" enctype="multipart/form-data" class="form">
          <input type="hidden" name="action" value="add_review">
          <input name="school" value="<?= esc($_SESSION['user']['school'] ?? '') ?>" placeholder="Kool" required>
          <input name="meal_name" placeholder="Toit" required>
          <label>Hinne <input type="range" min="1" max="5" name="rating" value="3"></label>
          <label><input type="checkbox" name="would_eat_again" checked> Sööks uuesti</label>
          <textarea name="comment" placeholder="Kommentaar" required></textarea>
          <label>Pilt (JPG/PNG/WEBP, max 4MB)</label>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
          <button class="btn btnp">Salvesta</button>
        </form>
      </aside>

      <section class="card">
        <div class="row" style="justify-content:space-between">
          <h2 style="margin:0">Viimased hinnangud</h2>
          <form method="get" class="row">
            <select name="school">
              <option value="">Kõik koolid</option>
              <?php foreach($schools as $s): ?>
                <option value="<?= esc($s['school']) ?>" <?= $schoolFilter===$s['school']?'selected':'' ?>><?= esc($s['school']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn">Filter</button>
          </form>
        </div>
        <?php foreach($reviews as $r): ?>
          <article class="review">
            <div class="row" style="justify-content:space-between"><strong><?= esc($r['meal_name']) ?> (<?= (int)$r['rating'] ?>/5)</strong><small class="muted"><?= esc($r['created_at']) ?></small></div>
            <div class="muted"><?= esc($r['school']) ?> · <?= esc($r['full_name']) ?></div>
            <div><?= (int)$r['would_eat_again'] ? "✅ Sööks uuesti":"❌ Ei sööks uuesti" ?></div>
            <p><?= nl2br(esc($r['comment'])) ?></p>
            <?php if(!empty($r['image_path'])): ?><img class="preview" src="<?= esc($r['image_path']) ?>" alt="toidupilt"><?php endif; ?>
            <div class="row">
              <form method="post"><input type="hidden" name="action" value="vote"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="vote_type" value="like"><button class="btn">👍 <?= (int)$r['likes_count'] ?></button></form>
              <form method="post"><input type="hidden" name="action" value="vote"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="vote_type" value="dislike"><button class="btn">👎 <?= (int)$r['dislikes_count'] ?></button></form>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <aside class="card">
        <h2>Leaderboard</h2>
        <ol class="leader">
          <?php foreach($leaderboard as $i=>$l): ?>
            <li><strong>#<?= $i+1 ?> <?= esc($l['school']) ?></strong><br><span class="muted"><?= esc($l['avg_rating']) ?>/5 · <?= (int)$l['cnt'] ?></span></li>
          <?php endforeach; ?>
        </ol>
        <hr>
        <small class="muted">Total: <?= $stats['total'] ?> · Avg: <?= number_format($stats['avg'],2) ?> · Again: <?= $stats['again'] ?>%</small>
      </aside>
    </div>
  <?php endif; ?>
</main>
<script>
const k='kk_theme_v2',r=document.documentElement,b=document.getElementById('themeBtn');
if(localStorage.getItem(k)==='dark') r.classList.add('dark');
b?.addEventListener('click',()=>{r.classList.toggle('dark');localStorage.setItem(k,r.classList.contains('dark')?'dark':'light');});
</script>
</body></html>