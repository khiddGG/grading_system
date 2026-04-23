<?php
$pageTitle = 'Courses';
require_once __DIR__ . '/../../includes/header.php';
requireRole('admin');
$db = getDB();
$courses = $db->query("SELECT * FROM courses ORDER BY status DESC, course_name ASC")->fetchAll();
?>
<div class="card">
  <div class="card-header">
    <h3>&#128218; All Courses</h3>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Course</button>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>#</th><th>Course Name</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($courses)): ?>
          <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">&#128218;</div><p>No courses yet.</p></div></td></tr>
        <?php else: foreach ($courses as $i => $c): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($c['course_name']) ?></strong></td>
            <td class="text-sm text-muted"><?= htmlspecialchars($c['description'] ?? '—') ?></td>
            <td>
              <span class="badge <?= $c['status'] ? 'badge-active' : 'badge-archived' ?>">
                <span class="badge-dot"></span> <?= $c['status'] ? 'Active' : 'Archived' ?>
              </span>
            </td>
            <td>
              <div class="flex gap-2">
                <button class="btn btn-outline btn-sm" onclick="editCourse(<?= $c['id'] ?>,'<?= htmlspecialchars(addslashes($c['course_name'])) ?>','<?= htmlspecialchars(addslashes($c['description']??'')) ?>')">&#9998; Edit</button>
                <form method="POST" action="/grading_systemv2/api/courses.php" style="display:inline" onsubmit="return confirm('<?= $c['status']?'Archive':'Restore' ?> this course?')">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="status" value="<?= $c['status']?0:1 ?>">
                  <button class="btn <?= $c['status']?'btn-warning':'btn-accent' ?> btn-sm"><?= $c['status']?'&#128451; Archive':'&#9989; Restore' ?></button>
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
    <div class="modal-header"><h3>&#128218; Add Course</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/courses.php">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Course Name <span class="required">*</span></label>
          <input type="text" name="course_name" class="form-control" placeholder="e.g. BSIT" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" placeholder="Course description"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Course</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>&#9998; Edit Course</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/courses.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Course Name <span class="required">*</span></label>
          <input type="text" name="course_name" id="editName" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="editDesc" class="form-control"></textarea>
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
function editCourse(id,name,desc){
  document.getElementById('editId').value=id;
  document.getElementById('editName').value=name;
  document.getElementById('editDesc').value=desc;
  openModal('editModal');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
