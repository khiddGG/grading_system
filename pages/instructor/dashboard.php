<?php
$pageTitle = 'Instructor Dashboard';
require_once __DIR__ . '/../../includes/header.php';
requireRole('instructor');
$db = getDB();
$uid = currentUserId();

$activeSem = getActiveSemester();
$semId = $activeSem['id'] ?? 0;

$mySubjects = $db->prepare("SELECT s.*, c.course_name FROM subjects s LEFT JOIN courses c ON c.id=s.course_id WHERE s.instructor_id=? AND s.status=1 AND s.semester_id=?");
$mySubjects->execute([$uid, $semId]);
$subjects = $mySubjects->fetchAll();

$stmt = $db->prepare("SELECT COUNT(DISTINCT st.student_id) 
    FROM students st 
    JOIN subjects s ON s.id = st.subject_id 
    WHERE s.instructor_id = ? AND s.semester_id = ? AND st.status = 1");
$stmt->execute([$uid, $semId]);
$totalStudents = $stmt->fetchColumn();
?>
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon indigo">&#128203;</div>
    <div class="stat-info"><h4>My Subjects</h4><div class="stat-value"><?= count($subjects) ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon emerald">&#128101;</div>
    <div class="stat-info"><h4>Total Students</h4><div class="stat-value"><?= $totalStudents ?></div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>&#128203; My Assigned Subjects for <?= htmlspecialchars($activeSem['name'] ?? 'Current Semester') ?></h3></div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Course No.</th><th>Descriptive Title</th><th>Course</th><th>Lab</th></tr></thead>
        <tbody>
        <?php if(empty($subjects)): ?>
          <tr><td colspan="4"><div class="empty-state"><p>No subjects assigned yet.</p></div></td></tr>
        <?php else: foreach($subjects as $s): ?>
          <tr>
            <td><strong><?= htmlspecialchars($s['course_no']) ?></strong></td>
            <td><?= htmlspecialchars($s['descriptive_title']) ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($s['course_name']) ?></span></td>
            <td><?= $s['with_lab']?'<span class="badge badge-active">Yes</span>':'No' ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
