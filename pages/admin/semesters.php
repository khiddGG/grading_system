<?php
$pageTitle = 'Semester Management';
require_once __DIR__ . '/../../includes/header.php';
requireRole('admin');
$db = getDB();

$semesters = $db->query("SELECT * FROM semesters ORDER BY created_at DESC")->fetchAll();
?>

<div class="card mb-6">
  <div class="card-header">
    <h3>&#128197; Semesters</h3>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ New Semester</button>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Semester Name</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($semesters as $sem): ?>
          <tr>
            <td><strong><?= htmlspecialchars($sem['name']) ?></strong></td>
            <td>
              <?php if($sem['status']): ?>
                <span class="badge badge-active">ACTIVE CYCLE</span>
              <?php else: ?>
                <span class="badge badge-archived">ARCHIVED</span>
              <?php endif; ?>
            </td>
            <td><?= date('M d, Y', strtotime($sem['created_at'])) ?></td>
            <td>
              <div class="flex gap-2">
                  <button class="btn btn-outline btn-sm" onclick='editSemester(<?= json_encode($sem) ?>)'>&#9998;</button>
                  <?php if(!$sem['status']): ?>
                    <form method="POST" action="/grading_systemv2/api/semesters.php" style="display:inline">
                      <input type="hidden" name="action" value="activate"><input type="hidden" name="id" value="<?= $sem['id'] ?>">
                      <button class="btn btn-accent btn-sm">Activate</button>
                    </form>
                    <form method="POST" action="/grading_systemv2/api/semesters.php" style="display:inline" onsubmit="return confirm('Delete this semester and all related data?')">
                      <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $sem['id'] ?>">
                      <button class="btn btn-danger btn-sm">&#128465;</button>
                    </form>
                  <?php else: ?>
                    <span class="text-xs text-muted">Currently Active</span>
                  <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="alert alert-warning" style="max-width:600px;">
  &#9432; <strong>Important:</strong> Activating a new semester will reset the system cycle. Instructors will need to set up their subjects and enroll students for the new semester. Past data remains accessible but read-only for instructors.
</div>

<!-- Add Modal -->
<div class="modal-backdrop" id="addModal">
  <div class="modal">
    <div class="modal-header"><h3>+ New Semester</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/semesters.php">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Semester Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Second Semester 2024-2025" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>&#9998; Edit Semester</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/semesters.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="eId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Semester Name</label>
          <input type="text" name="name" id="eName" class="form-control" required>
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
function editSemester(sem){
  document.getElementById('eId').value = sem.id;
  document.getElementById('eName').value = sem.name;
  openModal('editModal');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
