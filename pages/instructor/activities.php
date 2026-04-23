<?php
$pageTitle = 'Quiz / Recitation / Attendance';
require_once __DIR__ . '/../../includes/header.php';
requireRole('instructor');
$db = getDB();
$uid = currentUserId();

// Semester Logic
$sems = $db->query("SELECT * FROM semesters ORDER BY created_at DESC")->fetchAll();
$activeSem = getActiveSemester();
$selectedSemester = $_GET['semester_id'] ?? ($activeSem['id'] ?? null);
$isEditable = isSemesterActive($selectedSemester);

$subs = $db->prepare("SELECT id, course_no, descriptive_title, with_lab FROM subjects WHERE instructor_id=? AND status=1 AND semester_id=? ORDER BY course_no");
$subs->execute([$uid, $selectedSemester]);
$mySubjects = $subs->fetchAll();
$selectedSubjectId = $_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? null);
$tab = $_GET['tab'] ?? 'quizzes';

$subjectInfo = null;
foreach($mySubjects as $s) { if($s['id'] == $selectedSubjectId) $subjectInfo = $s; }

$activities = [];
$attendanceSessions = [];
$students = [];
$subjectCriteria = [];

if ($selectedSubjectId) {
    $stmt = $db->prepare("SELECT * FROM activities WHERE subject_id=? ORDER BY created_at DESC");
    $stmt->execute([$selectedSubjectId]);
    $activities = $stmt->fetchAll();

    $stmt2 = $db->prepare("SELECT * FROM attendance WHERE subject_id=? ORDER BY session_date DESC");
    $stmt2->execute([$selectedSubjectId]);
    $attendanceSessions = $stmt2->fetchAll();

    $stmt3 = $db->prepare("SELECT * FROM students WHERE subject_id=? AND status=1 ORDER BY last_name, first_name");
    $stmt3->execute([$selectedSubjectId]);
    $students = $stmt3->fetchAll();

    $stmt4 = $db->prepare("SELECT category, type FROM criteria WHERE subject_id=? GROUP BY category, type");
    $stmt4->execute([$selectedSubjectId]);
    $subjectCriteria = $stmt4->fetchAll();
}
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
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
  </form>
</div>

<!-- Tab Navigation -->
<div class="flex gap-2 mb-4">
  <a href="?semester_id=<?= $selectedSemester ?>&subject_id=<?= $selectedSubjectId ?>&tab=quizzes" class="btn <?= $tab==='quizzes'?'btn-primary':'btn-outline' ?>">&#128221; Quizzes</a>
  <a href="?semester_id=<?= $selectedSemester ?>&subject_id=<?= $selectedSubjectId ?>&tab=recitation" class="btn <?= $tab==='recitation'?'btn-primary':'btn-outline' ?>">&#128483; Recitation</a>
  <a href="?semester_id=<?= $selectedSemester ?>&subject_id=<?= $selectedSubjectId ?>&tab=attendance" class="btn <?= $tab==='attendance'?'btn-primary':'btn-outline' ?>">&#128197; Attendance</a>
</div>

<?php if ($tab === 'quizzes'): ?>
<!-- QUIZZES TAB -->
<div class="card">
  <div class="card-header">
    <h3>&#128221; Quizzes & Major Activities</h3>
    <?php if($selectedSubjectId && $isEditable): ?>
      <button class="btn btn-primary" onclick="openAddModal('Quiz')">+ Add Activity</button>
    <?php elseif(!$isEditable): ?>
      <span class="badge badge-archived">Archived Semester (Locked)</span>
    <?php endif; ?>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Title</th><th>Type</th><th>Category</th><th>Points</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php 
        $filteredActs = array_filter($activities, function($a){ return strtolower($a['category']) !== 'recitation'; });
        if(empty($filteredActs)): ?>
          <tr><td colspan="6"><div class="empty-state">No quizzes yet.</div></td></tr>
        <?php else: foreach($filteredActs as $act): ?>
          <tr>
            <td><strong><?= htmlspecialchars($act['title']) ?></strong></td>
            <td><span class="badge <?= $act['type']=='Lab'?'badge-warning':'badge-primary' ?>"><?= $act['type'] ?></span></td>
            <td><span class="text-sm"><?= htmlspecialchars($act['category']) ?></span></td>
            <td><?= $act['total_points'] ?></td>
            <td class="text-muted"><?= $act['activity_date'] ?? '—' ?></td>
            <td>
              <div class="flex gap-2">
                <a href="?semester_id=<?= $selectedSemester ?>&subject_id=<?= $selectedSubjectId ?>&tab=quizzes&score_id=<?= $act['id'] ?>" class="btn btn-accent btn-sm">&#9998; Score</a>
                <?php if($isEditable): ?>
                  <button class="btn btn-outline btn-sm" onclick='editActivity(<?= json_encode($act) ?>)'>&#9998; Edit</button>
                  <form method="POST" action="/grading_systemv2/api/activities.php" style="display:inline">
                    <input type="hidden" name="action" value="delete_activity"><input type="hidden" name="id" value="<?= $act['id'] ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>"><input type="hidden" name="tab" value="quizzes">
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">&#128465;</button>
                  </form>
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

<?php
$scoreId = $_GET['score_id'] ?? null;
if ($scoreId && $selectedSubjectId):
    $actInfo = $db->prepare("SELECT * FROM activities WHERE id=?");
    $actInfo->execute([$scoreId]);
    $actInfo = $actInfo->fetch();
    if ($actInfo):
        $existingScores = [];
        $stmt = $db->prepare("SELECT student_id, score FROM activity_scores WHERE activity_id=?");
        $stmt->execute([$scoreId]);
        foreach($stmt->fetchAll() as $sc) { $existingScores[$sc['student_id']] = $sc['score']; }
?>
<div class="card mt-4" id="scoringSection">
  <div class="card-header"><h3>&#9998; Scoring: <?= htmlspecialchars($actInfo['title']) ?> (<?= $actInfo['total_points'] ?> pts)</h3></div>
  <div class="card-body">
    <form method="POST" action="/grading_systemv2/api/activities.php">
      <input type="hidden" name="action" value="save_scores"><input type="hidden" name="activity_id" value="<?= $scoreId ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      <table>
        <thead><tr><th>Student</th><th>Score / <?= $actInfo['total_points'] ?></th></tr></thead>
        <tbody>
        <?php foreach($students as $st): ?>
          <tr>
            <td><?= htmlspecialchars($st['last_name'].', '.$st['first_name']) ?></td>
            <td><input type="number" name="scores[<?= $st['id'] ?>]" class="form-control" style="max-width:120px;" min="0" max="<?= $actInfo['total_points'] ?>" step="0.5" value="<?= $existingScores[$st['id']] ?? '' ?>" <?= !$isEditable ? 'disabled' : '' ?>></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if($isEditable): ?><button type="submit" class="btn btn-primary mt-4">Save Scores</button><?php endif; ?>
    </form>
  </div>
</div>
<script>document.getElementById('scoringSection').scrollIntoView();</script>
<?php endif; endif; ?>

<?php elseif ($tab === 'recitation'): ?>
<!-- RECITATION TAB -->
<div class="card">
  <div class="card-header">
    <h3>&#128483; Recitation Records</h3>
    <?php if($selectedSubjectId && $isEditable): ?>
      <button class="btn btn-primary" onclick="openAddModal('Recitation')">+ Add Recitation</button>
    <?php elseif(!$isEditable): ?>
      <span class="badge badge-archived">Archived Semester (Locked)</span>
    <?php endif; ?>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Title</th><th>Type</th><th>Points</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php 
        $filteredActs = array_filter($activities, function($a){ return strtolower($a['category']) === 'recitation'; });
        if(empty($filteredActs)): ?>
          <tr><td colspan="5"><div class="empty-state">No recitation records found.</div></td></tr>
        <?php else: foreach($filteredActs as $act): ?>
          <tr>
            <td><strong><?= htmlspecialchars($act['title']) ?></strong></td>
            <td><span class="badge <?= $act['type']=='Lab'?'badge-warning':'badge-primary' ?>"><?= $act['type'] ?></span></td>
            <td><?= $act['total_points'] ?></td>
            <td class="text-muted"><?= $act['activity_date'] ?? '—' ?></td>
            <td>
              <div class="flex gap-2">
                <a href="?semester_id=<?= $selectedSemester ?>&subject_id=<?= $selectedSubjectId ?>&tab=recitation&score_id=<?= $act['id'] ?>" class="btn btn-accent btn-sm">&#9998; Score</a>
                <?php if($isEditable): ?>
                  <button class="btn btn-outline btn-sm" onclick='editActivity(<?= json_encode($act) ?>)'>&#9998; Edit</button>
                  <form method="POST" action="/grading_systemv2/api/activities.php" style="display:inline">
                    <input type="hidden" name="action" value="delete_activity"><input type="hidden" name="id" value="<?= $act['id'] ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>"><input type="hidden" name="tab" value="recitation">
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">&#128465;</button>
                  </form>
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
<?php
$scoreId = $_GET['score_id'] ?? null;
if ($scoreId && $selectedSubjectId):
    $actInfo = $db->prepare("SELECT * FROM activities WHERE id=?");
    $actInfo->execute([$scoreId]);
    $actInfo = $actInfo->fetch();
    if ($actInfo):
        $existingScores = [];
        $stmt = $db->prepare("SELECT student_id, score FROM activity_scores WHERE activity_id=?");
        $stmt->execute([$scoreId]);
        foreach($stmt->fetchAll() as $sc) { $existingScores[$sc['student_id']] = $sc['score']; }
?>
<div class="card mt-4" id="scoringSection">
  <div class="card-header"><h3>&#9998; Scoring: <?= htmlspecialchars($actInfo['title']) ?> (<?= $actInfo['total_points'] ?> pts)</h3></div>
  <div class="card-body">
    <form method="POST" action="/grading_systemv2/api/activities.php">
      <input type="hidden" name="action" value="save_scores"><input type="hidden" name="activity_id" value="<?= $scoreId ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      <table>
        <thead><tr><th>Student</th><th>Score / <?= $actInfo['total_points'] ?></th></tr></thead>
        <tbody>
        <?php foreach($students as $st): ?>
          <tr>
            <td><?= htmlspecialchars($st['last_name'].', '.$st['first_name']) ?></td>
            <td><input type="number" name="scores[<?= $st['id'] ?>]" class="form-control" style="max-width:120px;" min="0" max="<?= $actInfo['total_points'] ?>" step="0.5" value="<?= $existingScores[$st['id']] ?? '' ?>" <?= !$isEditable ? 'disabled' : '' ?>></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if($isEditable): ?><button type="submit" class="btn btn-primary mt-4">Save Scores</button><?php endif; ?>
    </form>
  </div>
</div>
<script>document.getElementById('scoringSection').scrollIntoView();</script>
<?php endif; endif; ?>

<?php else: ?>
<!-- ATTENDANCE TAB -->
<div class="card">
  <div class="card-header">
    <h3>&#128197; Attendance Sessions</h3>
    <?php if($selectedSubjectId && $isEditable): ?>
      <button class="btn btn-primary" onclick="openAddAttModal()">+ Add Session</button>
    <?php elseif(!$isEditable): ?>
      <span class="badge badge-archived">Archived Semester (Locked)</span>
    <?php endif; ?>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Date</th><th>Title</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($attendanceSessions)): ?>
          <tr><td colspan="3"><div class="empty-state">No attendance sessions.</div></td></tr>
        <?php else: foreach($attendanceSessions as $att): ?>
          <tr>
            <td><strong><?= $att['session_date'] ?></strong></td>
            <td><?= htmlspecialchars($att['title'] ?? 'Session') ?></td>
            <td>
              <div class="flex gap-2">
                <a href="?semester_id=<?= $selectedSemester ?>&subject_id=<?= $selectedSubjectId ?>&tab=attendance&att_id=<?= $att['id'] ?>" class="btn btn-accent btn-sm">&#9998; Record</a>
                <?php if($isEditable): ?>
                  <button class="btn btn-outline btn-sm" onclick='editAttendance(<?= json_encode($att) ?>)'>&#9998; Edit</button>
                  <form method="POST" action="/grading_systemv2/api/activities.php" style="display:inline">
                    <input type="hidden" name="action" value="delete_attendance"><input type="hidden" name="id" value="<?= $att['id'] ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>"><input type="hidden" name="tab" value="attendance">
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">&#128465;</button>
                  </form>
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

<?php
$attId = $_GET['att_id'] ?? null;
if ($attId && $selectedSubjectId):
    $existingRecords = [];
    $stmt = $db->prepare("SELECT student_id, status FROM attendance_records WHERE attendance_id=?");
    $stmt->execute([$attId]);
    foreach($stmt->fetchAll() as $r) { $existingRecords[$r['student_id']] = $r['status']; }
?>
<div class="card mt-4">
  <div class="card-header"><h3>&#9998; Attendance Record</h3></div>
  <div class="card-body">
    <form method="POST" action="/grading_systemv2/api/activities.php">
      <input type="hidden" name="action" value="save_attendance"><input type="hidden" name="attendance_id" value="<?= $attId ?>"><input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
      <table>
        <thead><tr><th>Student</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($students as $st): $val = $existingRecords[$st['id']] ?? 1; ?>
          <tr>
            <td><?= htmlspecialchars($st['last_name'].', '.$st['first_name']) ?></td>
            <td>
              <select name="records[<?= $st['id'] ?>]" class="form-control" style="max-width:150px;" <?= !$isEditable ? 'disabled' : '' ?>>
                <option value="1" <?= $val==1?'selected':'' ?>>Present</option>
                <option value="2" <?= $val==2?'selected':'' ?>>Late</option>
                <option value="0" <?= $val==0?'selected':'' ?>>Absent</option>
              </select>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if($isEditable): ?><button type="submit" class="btn btn-primary mt-4">Save Attendance</button><?php endif; ?>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Add/Edit Quiz Modal -->
<div class="modal-backdrop" id="addQuizModal">
  <div class="modal">
    <div class="modal-header"><h3 id="actModalTitle">+ Add Quiz/Activity</h3><button class="modal-close" onclick="closeModal('addQuizModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/activities.php">
      <input type="hidden" name="action" value="create_activity" id="actAction">
      <input type="hidden" name="id" id="actId">
      <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
      <input type="hidden" name="tab" id="actTab" value="<?= htmlspecialchars($tab) ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" id="actTitle" class="form-control" required></div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category / Type</label>
            <select name="crit_info" id="actCrit" class="form-control" required>
              <option value="">Select Category</option>
              <?php foreach($subjectCriteria as $cr): if(strtolower($cr['category'])==='attendance') continue; ?>
                <option value="<?= htmlspecialchars($cr['category'].'|'.$cr['type']) ?>"><?= htmlspecialchars($cr['category']) ?> (<?= $cr['type'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Total Points</label><input type="number" name="total_points" id="actPoints" class="form-control" min="1" value="100" required></div>
        </div>
        <div class="form-group"><label class="form-label">Date</label><input type="date" name="activity_date" id="actDate" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('addQuizModal')">Cancel</button><button type="submit" class="btn btn-primary" id="actSubmitBtn">Save</button></div>
    </form>
  </div>
</div>

<script>
function openAddModal(type) {
    const modal = document.getElementById('addQuizModal');
    document.getElementById('actAction').value = "create_activity";
    document.getElementById('actId').value = "";
    document.getElementById('actSubmitBtn').innerText = "Save";
    
    const critSelect = document.getElementById('actCrit');
    const titleInput = document.getElementById('actTitle');
    document.getElementById('actPoints').value = 100;
    document.getElementById('actDate').value = "";
    
    // Reset selection
    critSelect.value = "";
    titleInput.value = "";
    
    // Filter options based on type
    let firstMatch = "";
    Array.from(critSelect.options).forEach(opt => {
        if (!opt.value) return; 
        const isRecitation = opt.text.toLowerCase().includes('recitation');
        if (type === 'Recitation') {
            if (isRecitation) { opt.style.display = ""; if (!firstMatch) firstMatch = opt.value; }
            else opt.style.display = "none";
        } else {
            if (!isRecitation) { opt.style.display = ""; if (!firstMatch) firstMatch = opt.value; }
            else opt.style.display = "none";
        }
    });

    if (type === 'Recitation') {
        titleInput.placeholder = "e.g. Class Recitation 1";
        document.getElementById('actModalTitle').innerText = "+ Add Recitation";
        document.getElementById('actTab').value = "recitation";
    } else {
        titleInput.placeholder = "e.g. Quiz 1";
        document.getElementById('actModalTitle').innerText = "+ Add Quiz/Activity";
        document.getElementById('actTab').value = "quizzes";
    }
    critSelect.value = firstMatch;
    openModal('addQuizModal');
}

function editActivity(a) {
    document.getElementById('actAction').value = "update_activity";
    document.getElementById('actId').value = a.id;
    document.getElementById('actModalTitle').innerText = "Edit Activity";
    document.getElementById('actSubmitBtn').innerText = "Update";
    
    document.getElementById('actTitle').value = a.title;
    document.getElementById('actPoints').value = a.total_points;
    document.getElementById('actDate').value = a.activity_date || '';
    
    const critSelect = document.getElementById('actCrit');
    const val = a.category + '|' + a.type;
    
    // Show all temporarily to allow selecting current
    Array.from(critSelect.options).forEach(opt => opt.style.display = "");
    critSelect.value = val;
    
    openModal('addQuizModal');
}
</script>

<!-- Add/Edit Attendance Modal -->
<div class="modal-backdrop" id="addAttModal">
  <div class="modal">
    <div class="modal-header"><h3 id="attModalTitle">+ Add Attendance Session</h3><button class="modal-close" onclick="closeModal('addAttModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/activities.php">
      <input type="hidden" name="action" value="create_attendance" id="attAction">
      <input type="hidden" name="id" id="attId">
      <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">
      <input type="hidden" name="tab" value="attendance">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Session Date</label><input type="date" name="session_date" id="attDate" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" id="attTitle" class="form-control" placeholder="e.g. Week 1"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('addAttModal')">Cancel</button><button type="submit" class="btn btn-primary" id="attSubmitBtn">Save</button></div>
    </form>
  </div>
</div>

<script>
function editAttendance(a) {
    document.getElementById('attAction').value = "update_attendance";
    document.getElementById('attId').value = a.id;
    document.getElementById('attModalTitle').innerText = "Edit Session";
    document.getElementById('attSubmitBtn').innerText = "Update";
    document.getElementById('attDate').value = a.session_date;
    document.getElementById('attTitle').value = a.title || '';
    openModal('addAttModal');
}
// Reset add attendance modal when closed or opened
function openAddAttModal() {
    document.getElementById('attAction').value = "create_attendance";
    document.getElementById('attId').value = "";
    document.getElementById('attModalTitle').innerText = "+ Add Attendance Session";
    document.getElementById('attSubmitBtn').innerText = "Save";
    document.getElementById('attDate').value = "<?= date('Y-m-d') ?>";
    document.getElementById('attTitle').value = "";
    openModal('addAttModal');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
