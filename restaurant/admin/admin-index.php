<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = $pdo->prepare("SELECT * FROM restaurant_users WHERE email=? AND role='admin'");
    $st->execute([$email]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user_id'] = $u['id'];
        redirect(BASE_URL.'/admin-dashboard.php');
    }
    $error = 'Invalid admin credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
</head>
<body class="admin-login-body">
  <form method="post" class="admin-login-card">
    <div class="admin-login-logo"><?= icon('utensils', 32) ?></div>
    <h1>Admin Login</h1>
    <p class="muted">Restricted area — authorized staff only</p>

    <?php if ($error): ?>
      <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" required autofocus></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn btn-primary btn-block btn-lg">Login</button>
    <p class="form-alt"><a href="<?= BASE_URL ?>/index.php">← Back to site</a></p>
  </form>
</body>
</html>