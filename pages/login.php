<?php
session_start();

// If already logged in, redirect to respective dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    header("Location: /grading_systemv2/pages/$role/dashboard.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$sysTitle = getSetting('system_title', 'Student Evaluation System');
$sysLogo  = getSetting('system_logo', '');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $db->prepare("SELECT id, username, password, full_name, role, status FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && !$user['status']) {
            $error = 'Your account has been deactivated.';
        } elseif ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            
            // Redirect based on role
            header("Location: /grading_systemv2/pages/{$user['role']}/dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= htmlspecialchars($sysTitle) ?></title>
  <link rel="stylesheet" href="/grading_systemv2/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-header">
      <?php if ($sysLogo): ?>
        <img src="/grading_systemv2/assets/uploads/<?= htmlspecialchars($sysLogo) ?>" alt="Logo" style="width:56px;height:56px;border-radius:var(--radius-md);margin:0 auto .75rem;object-fit:cover;">
      <?php else: ?>
        <div class="login-logo">SE</div>
      <?php endif; ?>
      <h1><?= htmlspecialchars($sysTitle) ?></h1>
      <p>Sign in to your account</p>
    </div>
    <div class="login-body">
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary w-full" style="padding:.75rem;">Sign In</button>
      </form>

      <div style="text-align:center;margin-top:1.5rem;">
        <a href="/grading_systemv2/pages/student/result.php" class="btn btn-outline btn-sm">&#128218; Student Result Portal</a>
      </div>

      <div style="text-align:center;margin-top:1rem;font-size:.72rem;color:var(--slate-400);">
        <p>Default: <strong>admin</strong> / password | <strong>instructor</strong> / password</p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
