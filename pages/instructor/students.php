<?php
$pageTitle = 'Students';
require_once __DIR__ . '/../../includes/header.php';
requireRole('instructor');
$db = getDB();
$uid = currentUserId();

// Semester Logic
$sems = $db->query("SELECT * FROM semesters ORDER BY created_at DESC")->fetchAll();
$activeSem = getActiveSemester();
$selectedSemester = $_GET['semester_id'] ?? ($activeSem['id'] ?? null);
$isEditable = isSemesterActive($selectedSemester);

// Filter subjects by semester
$subs = $db->prepare("SELECT id, course_no, descriptive_title FROM subjects WHERE instructor_id=? AND status=1 AND semester_id=? ORDER BY course_no");
$subs->execute([$uid, $selectedSemester]);
$mySubjects = $subs->fetchAll();
$selectedSubject = $_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? null);

$search = $_GET['search'] ?? '';

$students = [];
if ($selectedSubject) {
    $q = "SELECT * FROM students WHERE subject_id=? AND status=1";
    $params = [$selectedSubject];
    if ($search) {
        $q .= " AND (first_name LIKE ? OR last_name LIKE ? OR student_id LIKE ?)";
        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like]);
    }
    $q .= " ORDER BY last_name, first_name";
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}
?>

<div class="flex gap-4 mb-4" style="flex-wrap:wrap;align-items:flex-end;">
  <form method="GET" class="flex gap-3" style="flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="margin-bottom:0;min-width:180px;">
      <label class="form-label">Semester</label>
      <select name="semester_id" class="form-control" onchange="this.form.submit()">
        <?php foreach($sems as $sem): ?>
          <option value="<?= $sem['id'] ?>" <?= $sem['id']==$selectedSemester?'selected':'' ?>><?= htmlspecialchars($sem['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:0;min-width:200px;">
      <label class="form-label">Select Subject</label>
      <select name="subject_id" class="form-control" onchange="this.form.submit()">
        <?php if(empty($mySubjects)): ?><option value="">No subjects found</option><?php endif; ?>
        <?php foreach($mySubjects as $sub): ?>
          <option value="<?= $sub['id'] ?>" <?= $sub['id']==$selectedSubject?'selected':'' ?>><?= htmlspecialchars($sub['course_no'].' - '.$sub['descriptive_title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:0;min-width:150px;">
      <label class="form-label">Search</label>
      <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Name or ID...">
    </div>
    <button type="submit" class="btn btn-outline" style="margin-bottom:0;">Search</button>
  </form>
  <?php if($selectedSubject && $isEditable): ?>
    <div class="flex gap-2" style="margin-left:auto;">
      <button class="btn btn-outline" onclick="openModal('importModal')">&#128229; Bulk Import</button>
      <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Student</button>
    </div>
  <?php elseif($selectedSubject && !$isEditable): ?>
    <div class="alert alert-warning" style="margin:0; padding:.5rem 1rem; font-size:.85rem; margin-left:auto;">
      &#128274; <strong>Read-Only Mode</strong>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header"><h3>&#128101; Student List</h3><span class="text-sm text-muted"><?= count($students) ?> student(s)</span></div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>#</th><th>Student ID</th><th>Last Name</th><th>First Name</th><th>Gender</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($students)): ?>
          <tr><td colspan="6"><div class="empty-state"><p>No students found.</p></div></td></tr>
        <?php else: foreach($students as $i=>$st): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($st['student_id']) ?></strong></td>
            <td><?= htmlspecialchars($st['last_name']) ?></td>
            <td><?= htmlspecialchars($st['first_name']) ?></td>
            <td><?= $st['gender'] ?></td>
            <td>
              <div class="flex gap-2">
                <?php if($isEditable): ?>
                  <button class="btn btn-outline btn-sm" onclick='editStudent(<?= json_encode($st) ?>)'>&#9998;</button>
                  <form method="POST" action="/grading_systemv2/api/students.php" style="display:inline" onsubmit="return confirm('Remove student?')">
                    <input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= $st['id'] ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
                    <button class="btn btn-danger btn-sm">&#128465;</button>
                  </form>
                <?php else: ?>
                  <span class="text-xs text-muted">Locked</span>
                <?php endif; ?>
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
    <div class="modal-header"><h3>&#128101; Add Student</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/students.php">
      <input type="hidden" name="action" value="create"><input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Student ID</label><input type="text" name="student_id" class="form-control" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Gender</label><select name="gender" class="form-control"><option value="Male">Male</option><option value="Female">Female</option></select></div>
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
    <div class="modal-header"><h3>&#9998; Edit Student</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/students.php">
      <input type="hidden" name="action" value="update"><input type="hidden" name="id" id="eId"><input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Student ID</label><input type="text" name="student_id" id="eStudentId" class="form-control" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" id="eFirst" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" id="eLast" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Gender</label><select name="gender" id="eGender" class="form-control"><option value="Male">Male</option><option value="Female">Female</option></select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Import Modal -->
<div class="modal-backdrop" id="importModal">
  <div class="modal">
    <div class="modal-header"><h3>&#128229; Bulk Import Students</h3><button class="modal-close" onclick="closeModal('importModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/students.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="bulk_import"><input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
      <div class="modal-body">
        <div class="alert alert-warning" style="font-size:.75rem;">
          <strong>Supported:</strong> .xlsx (Excel), .csv (Comma Separated) <br>
          <strong>Format:</strong> Col A: Student ID, Col B: Full Name, Col W: Gender (M/F) <br>
          <strong>Note:</strong> Legacy .xls not supported. Please use .xlsx.
        </div>
        <div class="form-group"><label class="form-label">Select File</label><input type="file" name="csv_file" class="form-control" accept=".csv, .xlsx" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('importModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Start Import</button>
      </div>
    </form>
  </div>
</div>

<script>
function editStudent(s){
  document.getElementById('eId').value=s.id;
  document.getElementById('eStudentId').value=s.student_id;
  document.getElementById('eFirst').value=s.first_name;
  document.getElementById('eLast').value=s.last_name;
  document.getElementById('eGender').value=s.gender;
  openModal('editModal');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
