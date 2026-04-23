<?php
$pageTitle = 'Instructors';
require_once __DIR__ . '/../../includes/header.php';
requireRole('admin');
$db = getDB();
$instructors = $db->query("SELECT * FROM users WHERE role='instructor' ORDER BY status DESC, full_name")->fetchAll();
?>
<div class="card">
  <div class="card-header">
    <h3>&#128100; Instructors</h3>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Instructor</button>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>#</th><th>Full Name</th><th>Username</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($instructors)): ?>
          <tr><td colspan="5"><div class="empty-state"><p>No instructors yet.</p></div></td></tr>
        <?php else: foreach($instructors as $i=>$u): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
            <td class="text-muted"><?= htmlspecialchars($u['username']) ?></td>
            <td><span class="badge <?= $u['status']?'badge-active':'badge-archived' ?>"><span class="badge-dot"></span> <?= $u['status']?'Active':'Archived' ?></span></td>
            <td>
              <div class="flex gap-2">
                <button class="btn btn-outline btn-sm" onclick="editInst(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['full_name'])) ?>','<?= htmlspecialchars(addslashes($u['username'])) ?>')">&#9998; Edit</button>
                <form method="POST" action="/grading_systemv2/api/instructors.php" style="display:inline" onsubmit="return confirm('<?= $u['status']?'Archive':'Restore' ?>?')">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="status" value="<?= $u['status']?0:1 ?>">
                  <button class="btn <?= $u['status']?'btn-warning':'btn-accent' ?> btn-sm"><?= $u['status']?'&#128451;':'&#9989;' ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-backdrop" id="addModal">
  <div class="modal">
    <div class="modal-header"><h3>&#128100; Add Instructor</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/instructors.php">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Username <span class="required">*</span></label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <input type="password" name="password" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>&#9998; Edit Instructor</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/instructors.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="eId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input type="text" name="full_name" id="eName" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Username <span class="required">*</span></label>
            <input type="text" name="username" id="eUser" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">New Password <span class="form-hint">(leave blank to keep)</span></label>
            <input type="password" name="password" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
function editInst(id,name,user){
  document.getElementById('eId').value=id;
  document.getElementById('eName').value=name;
  document.getElementById('eUser').value=user;
  openModal('editModal');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
