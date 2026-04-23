<?php
$pageTitle = 'My Subjects';
require_once __DIR__ . '/../../includes/header.php';
requireRole('instructor');
$db = getDB();
$uid = currentUserId();

// Get all semesters for filtering
$sems = $db->query("SELECT * FROM semesters ORDER BY created_at DESC")->fetchAll();
$activeSem = getActiveSemester();
$selectedSemester = $_GET['semester_id'] ?? ($activeSem['id'] ?? null);

$isEditable = isSemesterActive($selectedSemester);

$subjects = $db->prepare("SELECT s.*, c.course_name, sc.day, sc.time_start, sc.time_end, sc.room, sc.id as sched_id,
    (SELECT COUNT(*) FROM students st WHERE st.subject_id = s.id AND st.status=1) as total_students,
    (SELECT COUNT(*) FROM students st WHERE st.subject_id = s.id AND st.status=1 AND st.gender='Female') as female_students,
    (SELECT COUNT(*) FROM students st WHERE st.subject_id = s.id AND st.status=1 AND st.gender='Male') as male_students
    FROM subjects s
    LEFT JOIN courses c ON c.id=s.course_id
    LEFT JOIN subject_schedules sc ON sc.subject_id=s.id
    WHERE s.instructor_id=? AND s.status=1 AND s.semester_id=?
    ORDER BY s.course_no");
$subjects->execute([$uid, $selectedSemester]);
$subjects = $subjects->fetchAll();
?>

<div class="flex gap-4 mb-4" style="align-items:flex-end; flex-wrap:wrap;">
  <form method="GET" class="flex gap-3" style="align-items:flex-end;">
    <div class="form-group" style="margin-bottom:0; min-width:250px;">
      <label class="form-label">Select Semester</label>
      <select name="semester_id" class="form-control" onchange="this.form.submit()">
        <?php foreach($sems as $sem): ?>
          <option value="<?= $sem['id'] ?>" <?= $sem['id']==$selectedSemester?'selected':'' ?>>
            <?= htmlspecialchars($sem['name']) ?> <?= $sem['status'] ? '(Active)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
  <?php if(!$isEditable): ?>
    <div class="alert alert-warning" style="margin:0; padding:.5rem 1rem; font-size:.85rem;">
      &#128274; <strong>Read-Only Mode:</strong> This semester is archived.
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header">
    <h3>&#128203; My Subjects & Schedule</h3>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr>
          <th>Course No.</th><th>Descriptive Title</th><th>Course</th>
          <th>Day</th><th>Time</th><th>Room</th>
          <th>Total</th><th>Female</th><th>Male</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($subjects)): ?>
          <tr><td colspan="10"><div class="empty-state"><p>No subjects found for this semester.</p></div></td></tr>
        <?php else: foreach($subjects as $s): ?>
          <tr>
            <td><strong><?= htmlspecialchars($s['course_no']) ?></strong></td>
            <td><?= htmlspecialchars($s['descriptive_title']) ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($s['course_name']) ?></span></td>
            <td><?php if($s['day']): ?><span class="day-badge <?= dayBadgeClass($s['day']) ?>"><?= htmlspecialchars($s['day']) ?></span><?php else: ?>—<?php endif; ?></td>
            <td><?= $s['time_start'] ? formatTime($s['time_start']).' - '.formatTime($s['time_end']) : '—' ?></td>
            <td><?= htmlspecialchars($s['room'] ?? '—') ?></td>
            <td><strong><?= $s['total_students'] ?></strong></td>
            <td><?= $s['female_students'] ?></td>
            <td><?= $s['male_students'] ?></td>
            <td>
              <?php if($isEditable): ?>
                <button class="btn btn-outline btn-sm" onclick='editSchedule(<?= json_encode($s) ?>)'>&#9998; Setup</button>
              <?php else: ?>
                <span class="text-xs text-muted">Locked</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Schedule Edit Modal -->
<div class="modal-backdrop" id="schedModal">
  <div class="modal">
    <div class="modal-header"><h3>&#128197; Class Setup</h3><button class="modal-close" onclick="closeModal('schedModal')">&times;</button></div>
    <form method="POST" action="/grading_systemv2/api/schedules.php">
      <input type="hidden" name="subject_id" id="sSubjectId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Subject</label>
          <input type="text" id="sSubjectName" class="form-control" disabled>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Day</label>
            <select name="day" id="sDay" class="form-control">
              <option value="">Select</option>
              <option value="MWF">MWF</option>
              <option value="TTh">TTh</option>
              <option value="MW">MW</option>
              <option value="TF">TF</option>
              <option value="Sat">Saturday</option>
              <option value="Daily">Daily</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Room</label>
            <input type="text" name="room" id="sRoom" class="form-control" placeholder="e.g. Room 201">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Time Start</label>
            <input type="time" name="time_start" id="sStart" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Time End</label>
            <input type="time" name="time_end" id="sEnd" class="form-control">
          </div>
        </div>
        <div class="alert alert-info" style="margin-top:1rem; font-size:.8rem;">
          &#9432; Student population counts are automatically updated based on the actual enrolled student list.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('schedModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Schedule</button>
      </div>
    </form>
  </div>
</div>

<script>
function editSchedule(s){
  document.getElementById('sSubjectId').value = s.id;
  document.getElementById('sSubjectName').value = s.course_no + ' - ' + s.descriptive_title;
  document.getElementById('sDay').value = s.day||'';
  document.getElementById('sRoom').value = s.room||'';
  document.getElementById('sStart').value = s.time_start||'';
  document.getElementById('sEnd').value = s.time_end||'';
  openModal('schedModal');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
