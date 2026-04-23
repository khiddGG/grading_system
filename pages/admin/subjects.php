<?php
$pageTitle = 'Subjects';
require_once __DIR__ . '/../../includes/header.php';
requireRole('admin');
$db = getDB();

$sems = $db->query("SELECT * FROM semesters ORDER BY created_at DESC")->fetchAll();
$activeSem = getActiveSemester();
$selectedSemester = $_GET['semester_id'] ?? ($activeSem['id'] ?? null);

$subjects = $db->prepare("SELECT s.*, c.course_name, u.full_name as instructor_name, sem.name as semester_name
    FROM subjects s
    LEFT JOIN semesters sem ON sem.id = s.semester_id
    LEFT JOIN courses c ON c.id = s.course_id
    LEFT JOIN users u ON u.id = s.instructor_id
    WHERE s.semester_id = ?
    ORDER BY s.status DESC, s.course_no ASC");
$subjects->execute([$selectedSemester]);
$subjects = $subjects->fetchAll();

$courses = $db->query("SELECT id, course_name FROM courses WHERE status=1 ORDER BY course_name")->fetchAll();
$instructors = $db->query("SELECT id, full_name FROM users WHERE role='instructor' AND status=1 ORDER BY full_name")->fetchAll();
?>

<div class="flex gap-4 mb-4" style="align-items:flex-end; flex-wrap:wrap;">
  <form method="GET" class="flex gap-3" style="align-items:flex-end;">
    <div class="form-group" style="margin-bottom:0; min-width:250px;">
      <label class="form-label">Filter by Semester</label>
      <select name="semester_id" class="form-control" onchange="this.form.submit()">
        <?php foreach($sems as $sem): ?>
          <option value="<?= $sem['id'] ?>" <?= $sem['id']==$selectedSemester?'selected':'' ?>>
            <?= htmlspecialchars($sem['name']) ?> <?= $sem['status'] ? '(Active)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
  <button class="btn btn-primary" onclick="openModal('addModal')" style="margin-left:auto;">+ Add Subject</button>
</div>

<div class="card">
  <div class="card-header">
    <h3>&#128203; Subjects for <?= htmlspecialchars($sems[array_search($selectedSemester, array_column($sems, 'id'))]['name'] ?? 'Selected Semester') ?></h3>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr>
          <th>Course No.</th><th>Descriptive Title</th><th>Instructor</th><th>Lab</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if (empty($subjects)): ?>
          <tr><td colspan="6"><div class="empty-state"><p>No subjects in this semester.</p></div></td></tr>
        <?php else: foreach ($subjects as $s): ?>
          <tr>
            <td><strong><?= htmlspecialchars($s['course_no']) ?></strong></td>
            <td><?= htmlspecialchars($s['descriptive_title']) ?></td>
            <td><?= htmlspecialchars($s['instructor_name'] ?? 'Unassigned') ?></td>
            <td><?= $s['with_lab'] ? '<span class="badge badge-primary">Yes</span>' : 'No' ?></td>
            <td>
              <span class="badge <?= $s['status']?'badge-active':'badge-archived' ?>">
                <?= $s['status']?'Active':'Archived' ?>
              </span>
            </td>
            <td>
              <div class="flex gap-2">
                <button class="btn btn-outline btn-sm" onclick='editSubject(<?= json_encode($s) ?>)'>&#9998;</button>
                <form method="POST" action="/grading_systemv2/api/subjects.php" style="display:inline">
                  <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="status" value="<?= $s['status']?0:1 ?>">
                  <button class="btn <?= $s['status']?'btn-warning':'btn-accent' ?> btn-sm"><?= $s['status']?'&#128451;':'&#9989;' ?></button>
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
    <div class="modal-header"><h3>&#128203; Add Subject</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/subjects.php">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="semester_id" value="<?= $selectedSemester ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Target Semester</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($sems[array_search($selectedSemester, array_column($sems, 'id'))]['name'] ?? '') ?>" disabled>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Course No. <span class="required">*</span></label>
            <input type="text" name="course_no" class="form-control" placeholder="e.g. IT 101" required>
          </div>
          <div class="form-group">
            <label class="form-label">Course <span class="required">*</span></label>
            <select name="course_id" class="form-control" required>
              <?php foreach($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Descriptive Title <span class="required">*</span></label>
          <input type="text" name="descriptive_title" class="form-control" placeholder="e.g. Web Development" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Instructor</label>
            <select name="instructor_id" class="form-control">
              <option value="">— Unassigned —</option>
              <?php foreach($instructors as $inst): ?>
                <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">With Lab?</label>
            <select name="with_lab" class="form-control">
              <option value="0">No</option>
              <option value="1">Yes</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Subject</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>&#9998; Edit Subject</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/subjects.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="eId">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Course No.</label>
            <input type="text" name="course_no" id="eCourseNo" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Course</label>
            <select name="course_id" id="eCourseId" class="form-control" required>
              <?php foreach($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Descriptive Title</label>
          <input type="text" name="descriptive_title" id="eTitle" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Instructor</label>
            <select name="instructor_id" id="eInstructor" class="form-control">
              <option value="">— Unassigned —</option>
              <?php foreach($instructors as $inst): ?>
                <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Semester</label>
            <select name="semester_id" id="eSemester" class="form-control" required>
              <?php foreach($sems as $sem): ?>
                <option value="<?= $sem['id'] ?>"><?= htmlspecialchars($sem['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">With Lab?</label>
          <select name="with_lab" id="eLab" class="form-control">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
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
function editSubject(s){
  document.getElementById('eId').value=s.id;
  document.getElementById('eCourseNo').value=s.course_no;
  document.getElementById('eTitle').value=s.descriptive_title;
  document.getElementById('eCourseId').value=s.course_id;
  document.getElementById('eInstructor').value=s.instructor_id||'';
  document.getElementById('eSemester').value=s.semester_id||'';
  document.getElementById('eLab').value=s.with_lab;
  openModal('editModal');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
