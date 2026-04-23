<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../../includes/header.php';
requireRole('admin');
$db = getDB();
$sysTitle = getSetting('system_title', 'Student Evaluation System');
$sysLogo  = getSetting('system_logo', '');
?>
<div class="card" style="max-width:600px;">
  <div class="card-header"><h3>&#9881; System Settings</h3></div>
  <div class="card-body">
    <form method="POST" action="/grading_systemv2/api/settings.php" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label">System Title</label>
        <input type="text" name="system_title" class="form-control" value="<?= htmlspecialchars($sysTitle) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">System Logo</label>
        <?php if($sysLogo): ?>
          <div style="margin-bottom:.75rem;">
            <img src="/grading_systemv2/assets/uploads/<?= htmlspecialchars($sysLogo) ?>" alt="Logo" style="width:80px;height:80px;border-radius:var(--radius-md);object-fit:cover;border:2px solid var(--slate-200);">
          </div>
        <?php endif; ?>
        <input type="file" name="logo" class="form-control" accept="image/*">
        <div class="form-hint">Upload a PNG/JPG image (recommended 200×200px)</div>
      </div>
      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
