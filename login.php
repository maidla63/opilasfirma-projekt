<?php
require __DIR__.'/api/db.php';
require __DIR__.'/api/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user'] = [
      'id' => (int)$user['id'],
      'name' => $user['full_name'],
      'email' => $user['email'],
      'role' => $user['role'],
      'school' => $user['school'],
    ];
    header('Location: /koolikriitik/index.php');
    exit;
  } else {
    $error = 'Vale email või parool.';
  }
}
?>
<!doctype html><html lang="et"><head><meta charset="UTF-8"><title>Login</title><link rel="stylesheet" href="assets/styles.css"></head>
<body><main class="container auth">
<h1>Logi sisse</h1>
<?php if($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" class="form">
  <input name="email" type="email" placeholder="Email" required>
  <input name="password" type="password" placeholder="Parool" required>
  <button class="btn btn-primary">Logi sisse</button>
</form>
<p>Pole kontot? <a href="register.php">Registreeri</a></p>
</main></body></html>