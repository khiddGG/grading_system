<?php
$pageTitle = 'Grades';
require_once __DIR__ . '/../../includes/header.php';
requireRole('instructor');
$db = getDB();
$uid = currentUserId();

// Semester Logic
$sems = $db->query("SELECT * FROM semesters ORDER BY created_at DESC")->fetchAll();
$activeSem = getActiveSemester();
$selectedSemester = $_GET['semester_id'] ?? ($activeSem['id'] ?? null);

$subs = $db->prepare("SELECT id, course_no, descriptive_title, with_lab FROM subjects WHERE instructor_id=? AND status=1 AND semester_id=? ORDER BY course_no");
$subs->execute([$uid, $selectedSemester]);
$mySubjects = $subs->fetchAll();
$selectedSubjectId = $_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? null);

$subjectInfo = null;
foreach($mySubjects as $s) { if($s['id'] == $selectedSubjectId) $subjectInfo = $s; }

$students = [];
$criteria = [];
$gradeData = [];
if ($selectedSubjectId) {
    $stmt = $db->prepare("SELECT * FROM students WHERE subject_id=? AND status=1 ORDER BY last_name, first_name");
    $stmt->execute([$selectedSubjectId]);
    $students = $stmt->fetchAll();

    $stmt2 = $db->prepare("SELECT * FROM criteria WHERE subject_id=? ORDER BY type, id");
    $stmt2->execute([$selectedSubjectId]);
    $criteria = $stmt2->fetchAll();

    foreach ($students as $st) {
        $result = computeFinalGrade($db, $st['id'], $selectedSubjectId);
        $conv = convertGrade($result['final_grade'], $result['is_incomplete']);
        $gradeData[$st['id']] = array_merge($result, [
            'equivalent' => $conv[0],
            'description' => $conv[1]
        ]);
    }
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
  </form>
</div>

<?php if (empty($criteria) && $selectedSubjectId): ?>
  <div class="alert alert-warning">&#9888; Please set up criteria first.</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3>&#127942; Grade Summary</h3>
    <?php if($subjectInfo && $subjectInfo['with_lab']): ?>
      <span class="badge badge-primary">Lec: 66.67% | Lab: 33.33%</span>
    <?php endif; ?>
  </div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Student</th>
            <?php if($subjectInfo && $subjectInfo['with_lab']): ?>
              <th>Lec Total</th>
              <th>Lab Total</th>
            <?php endif; ?>
            <th>Final</th>
            <th>Equiv.</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
        <?php if(empty($students)): ?>
          <tr><td colspan="6"><div class="empty-state">No students.</div></td></tr>
        <?php else: foreach($students as $st):
          $gd = $gradeData[$st['id']];
        ?>
          <tr>
            <td><strong><?= htmlspecialchars($st['last_name'].', '.$st['first_name']) ?></strong><br><span class="text-xs text-muted"><?= htmlspecialchars($st['student_id']) ?></span></td>
            <?php if($subjectInfo && $subjectInfo['with_lab']): ?>
              <td><strong><?= $gd['lecture_total'] ?>%</strong></td>
              <td><strong><?= $gd['lab_total'] ?>%</strong></td>
            <?php endif; ?>
            <td><strong><?= $gd['final_grade'] !== null ? $gd['final_grade'] : '—' ?></strong></td>
            <td><span class="badge badge-primary"><?= $gd['equivalent'] ?></span></td>
            <td class="text-sm"><?= $gd['description'] ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
