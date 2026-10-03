<?php
require __DIR__.'/api/db.php';
require __DIR__.'/api/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $school = trim($_POST['school'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!$name || !$email || !$password) {
    $error = 'Täida kohustuslikud väljad.';
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
      $stmt = $pdo->prepare("INSERT INTO users(full_name,email,password_hash,role,school) VALUES(?,?,?,?,?)");
      $stmt->execute([$name,$email,$hash,'user',$school ?: null]);
      header('Location: /koolikriitik/login.php');
      exit;
    } catch (Throwable $e) {
      $error = 'Email on juba kasutusel või tekkis viga.';
    }
  }
}
?>
<!doctype html><html lang="et"><head><meta charset="UTF-8"><title>Register</title><link rel="stylesheet" href="assets/styles.css"></head>
<body><main class="container auth">
<h1>Loo konto</h1>
<?php if($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" class="form">
  <input name="full_name" placeholder="Nimi" required>
  <input name="email" type="email" placeholder="Email" required>
  <input name="school" placeholder="Kool (valikuline)">
  <input name="password" type="password" placeholder="Parool" required>
  <button class="btn btn-primary">Registreeri</button>
</form>
<p>On konto? <a href="login.php">Logi sisse</a></p>
</main></body></html>