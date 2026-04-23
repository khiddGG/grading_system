<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../../includes/header.php';
requireRole('instructor');
$db = getDB();
$user = $db->prepare("SELECT * FROM users WHERE id=?");
$user->execute([currentUserId()]);
$user = $user->fetch();
?>
<div class="card" style="max-width:500px;">
  <div class="card-header"><h3>&#128113; My Profile</h3></div>
  <div class="card-body">
    <form method="POST" action="/grading_systemv2/api/settings.php">
      <input type="hidden" name="action" value="profile">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
      </div>
      <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
