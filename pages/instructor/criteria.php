<?php
$pageTitle = 'Criteria';
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
$subs = $db->prepare("SELECT id, course_no, descriptive_title, with_lab FROM subjects WHERE instructor_id=? AND status=1 AND semester_id=? ORDER BY course_no");
$subs->execute([$uid, $selectedSemester]);
$mySubjects = $subs->fetchAll();
$selectedSubjectId = $_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? null);

$subjectInfo = null;
foreach($mySubjects as $s) { if($s['id'] == $selectedSubjectId) $subjectInfo = $s; }

$criteria = [];
if ($selectedSubjectId) {
    $stmt = $db->prepare("SELECT * FROM criteria WHERE subject_id=? ORDER BY type, id");
    $stmt->execute([$selectedSubjectId]);
    $criteria = $stmt->fetchAll();
}

// Group criteria
$lecCrit = array_filter($criteria, function($c){ return $c['type'] === 'Lecture'; });
$labCrit = array_filter($criteria, function($c){ return $c['type'] === 'Lab'; });

$lecTotal = array_sum(array_column($lecCrit, 'weight'));
$labTotal = array_sum(array_column($labCrit, 'weight'));
?>

<div class="flex gap-4 mb-4" style="flex-wrap:wrap;align-items:flex-end;">
  <form method="GET" class="flex gap-3" style="align-items:flex-end;">
    <div class="form-group" style="margin-bottom:0;min-width:200px;">
      <label class="form-label">Semester</label>
      <select name="semester_id" class="form-control" onchange="this.form.submit()">
        <?php foreach($sems as $sem): ?>
          <option value="<?= $sem['id'] ?>" <?= $sem['id']==$selectedSemester?'selected':'' ?>><?= htmlspecialchars($sem['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:0;min-width:250px;">
      <label class="form-label">Select Subject</label>
      <select name="subject_id" class="form-control" onchange="this.form.submit()">
        <?php if(empty($mySubjects)): ?><option value="">No subjects found</option><?php endif; ?>
        <?php foreach($mySubjects as $sub): ?>
          <option value="<?= $sub['id'] ?>" <?= $sub['id']==$selectedSubjectId?'selected':'' ?>><?= htmlspecialchars($sub['course_no'].' - '.$sub['descriptive_title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
  <?php if($selectedSubjectId && $isEditable): ?>
    <button class="btn btn-primary" onclick="openModal('addModal')" style="margin-left:auto;">+ Add Category</button>
  <?php elseif(!$isEditable): ?>
    <div class="alert alert-warning" style="margin:0; padding:.5rem 1rem; font-size:.85rem; margin-left:auto;">
      &#128274; <strong>Locked:</strong> Archived Semester
    </div>
  <?php endif; ?>
</div>

<?php if ($subjectInfo): ?>
  <div class="mb-4">
    <span class="badge <?= $subjectInfo['with_lab'] ? 'badge-primary' : 'badge-active' ?>">
      <?= $subjectInfo['with_lab'] ? 'With Laboratory (66.67% Lec / 33.33% Lab)' : 'Lecture Only' ?>
    </span>
  </div>

  <div class="flex gap-4" style="flex-wrap:wrap;">
    <!-- Lecture Section -->
    <div class="card" style="flex:1; min-width:300px;">
      <div class="card-header"><h3>&#128214; Lecture Components</h3><span class="badge badge-primary"><?= $lecTotal ?>%</span></div>
      <div class="card-body no-pad">
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Category</th><th>Weight</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($lecCrit)): ?>
              <tr><td colspan="3"><div class="empty-state">No lecture criteria.</div></td></tr>
            <?php else: foreach($lecCrit as $cr): ?>
              <tr>
                <td><strong><?= htmlspecialchars($cr['category']) ?></strong></td>
                <td><?= $cr['weight'] ?>%</td>
                <td>
                  <div class="flex gap-2">
                    <?php if($isEditable): ?>
                      <button class="btn btn-outline btn-sm" onclick='editCrit(<?= json_encode($cr) ?>)'>&#9998;</button>
                      <form method="POST" action="/grading_systemv2/api/criteria.php" style="display:inline">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $cr['id'] ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">&#128465;</button>
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

    <!-- Lab Section -->
    <?php if($subjectInfo['with_lab']): ?>
    <div class="card" style="flex:1; min-width:300px;">
      <div class="card-header"><h3>&#128300; Lab Components</h3><span class="badge badge-warning"><?= $labTotal ?>%</span></div>
      <div class="card-body no-pad">
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Category</th><th>Weight</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($labCrit)): ?>
              <tr><td colspan="3"><div class="empty-state">No lab criteria.</div></td></tr>
            <?php else: foreach($labCrit as $cr): ?>
              <tr>
                <td><strong><?= htmlspecialchars($cr['category']) ?></strong></td>
                <td><?= $cr['weight'] ?>%</td>
                <td>
                  <div class="flex gap-2">
                    <?php if($isEditable): ?>
                      <button class="btn btn-outline btn-sm" onclick='editCrit(<?= json_encode($cr) ?>)'>&#9998;</button>
                      <form method="POST" action="/grading_systemv2/api/criteria.php" style="display:inline">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $cr['id'] ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">&#128465;</button>
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
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal-backdrop" id="addModal">
  <div class="modal modal-lg">
    <div class="modal-header"><h3>+ Add Criteria</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
    <div class="modal-body">
      <div class="modal-split">
        <!-- Form Column -->
        <form method="POST" action="/grading_systemv2/api/criteria.php" id="addCriteriaForm">
          <input type="hidden" name="action" value="create">
          <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
          
          <div class="form-group">
            <label class="form-label">Category Name</label>
            <input type="text" name="category" class="form-control" placeholder="e.g. Quizzes" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Type</label>
              <?php if($subjectInfo && $subjectInfo['with_lab']): ?>
                <select name="type" class="form-control">
                  <option value="Lecture">Lecture</option>
                  <option value="Lab">Lab</option>
                </select>
              <?php else: ?>
                <input type="text" class="form-control" value="Lecture Only" disabled>
                <input type="hidden" name="type" value="Lecture">
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label">Weight (%)</label>
              <input type="number" name="weight" class="form-control" min="1" max="100" required>
            </div>
          </div>

          <div class="alert alert-info mt-4" style="font-size:.75rem;">
            &#9432; Ensure the total weight for each type (Lecture/Lab) sums up to exactly 100%.
          </div>
          
          <div class="mt-4 flex gap-2">
            <button type="submit" class="btn btn-primary w-full">Save Category</button>
            <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
          </div>
        </form>

        <!-- Reference Column -->
        <div class="criteria-reference">
          <div class="criteria-ref-card">
            <h4>&#128214; Example: WITHOUT LAB</h4>
            <table class="ref-table">
              <thead><tr><th>Component</th><th>Weight</th></tr></thead>
              <tbody>
                <tr><td>Quizzes</td><td>20%</td></tr>
                <tr><td>Recitation</td><td>15%</td></tr>
                <tr><td>Midterm Exam</td><td>30%</td></tr>
                <tr><td>Final Exam</td><td>35%</td></tr>
                <tr style="border-top:1px solid var(--slate-300)"><td><strong>Total</strong></td><td><strong>100%</strong></td></tr>
              </tbody>
            </table>
          </div>

          <div class="criteria-ref-card">
            <h4>&#128300; Example: WITH LAB</h4>
            <div class="mb-4">
              <p class="text-xs font-bold text-primary mb-2">Lecture Components (100%)</p>
              <table class="ref-table">
                <tbody>
                  <tr><td>Quizzes</td><td>20%</td></tr>
                  <tr><td>Recitation</td><td>15%</td></tr>
                  <tr><td>Midterm Exam</td><td>30%</td></tr>
                  <tr><td>Final Exam</td><td>35%</td></tr>
                </tbody>
              </table>
            </div>
            <div>
              <p class="text-xs font-bold text-warm-600 mb-2">Laboratory Components (100%)</p>
              <table class="ref-table">
                <tbody>
                  <tr><td>Projects</td><td>25%</td></tr>
                  <tr><td>Activities</td><td>30%</td></tr>
                  <tr><td>Lab Exam</td><td>35%</td></tr>
                  <tr><td>Attendance</td><td>10%</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="formula-box">
            Final Grade Formula:
            <code>(Lecture × 0.6667) + (Lab × 0.3333)</code>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>&#9998; Edit Criteria</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/criteria.php">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="eId">
      <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="type" id="eType" class="form-control" <?= ($subjectInfo && !$subjectInfo['with_lab']) ? 'disabled' : '' ?>>
            <option value="Lecture">Lecture</option>
            <option value="Lab">Lab</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <input type="text" name="category" id="eCat" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Weight (%)</label>
          <input type="number" name="weight" id="eWeight" class="form-control" min="1" max="100" required>
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
const lecTotal = <?= (float)$lecTotal ?>;
const labTotal = <?= (float)$labTotal ?>;

function validateWeight(modalId) {
  const modal = document.getElementById(modalId);
  const typeSelect = modal.querySelector('[name="type"]');
  const weightInput = modal.querySelector('[name="weight"]');
  const saveBtn = modal.querySelector('button[type="submit"]');
  
  const type = typeSelect ? typeSelect.value : 'Lecture';
  const weight = parseFloat(weightInput.value) || 0;
  
  let baseTotal = type === 'Lab' ? labTotal : lecTotal;
  
  if (modalId === 'editModal' && window.currentEditingWeight && window.currentEditingType === type) {
      baseTotal -= window.currentEditingWeight;
  }
  
  const newTotal = baseTotal + weight;
  
  if (newTotal > 100) {
    saveBtn.disabled = true;
    saveBtn.style.opacity = '0.5';
    saveBtn.innerText = 'Exceeds 100%';
    weightInput.style.borderColor = 'var(--danger-500)';
  } else {
    saveBtn.disabled = false;
    saveBtn.style.opacity = '1';
    saveBtn.innerText = modalId === 'editModal' ? 'Update' : 'Save';
    weightInput.style.borderColor = 'var(--slate-300)';
  }
}

function editCrit(c){
  window.currentEditingWeight = parseFloat(c.weight);
  window.currentEditingType = c.type;
  
  document.getElementById('eId').value = c.id;
  document.getElementById('eCat').value = c.category;
  document.getElementById('eWeight').value = c.weight;
  document.getElementById('eType').value = c.type;
  
  validateWeight('editModal');
  openModal('editModal');
}

document.querySelectorAll('#addModal [name="weight"], #addModal [name="type"]').forEach(el => {
  el.addEventListener('input', () => validateWeight('addModal'));
});
document.querySelectorAll('#editModal [name="weight"], #editModal [name="type"]').forEach(el => {
  el.addEventListener('input', () => validateWeight('editModal'));
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
