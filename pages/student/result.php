<?php
/**
 * Student Result Page
 * Student enters their Student ID to view grades, attendance, and prediction
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$sysTitle = getSetting('system_title', 'Student Evaluation System');
$sysLogo  = getSetting('system_logo', '');

$studentIdInput = trim($_GET['student_id'] ?? '');
$selectedSubject = $_GET['subject_id'] ?? null;
$student = null;
$subjects = [];
$result = null;

if ($studentIdInput) {
    // Find all subjects for this student
    $stmt = $db->prepare("SELECT st.*, s.id as subj_id, s.course_no, s.descriptive_title, s.with_lab,
            c.course_name, u.full_name as instructor_name, sem.status as sem_status, sem.name as sem_name
        FROM students st
        JOIN subjects s ON s.id = st.subject_id
        JOIN semesters sem ON sem.id = s.semester_id
        LEFT JOIN courses c ON c.id = s.course_id
        LEFT JOIN users u ON u.id = s.instructor_id
        WHERE st.student_id = ? AND st.status = 1
        ORDER BY sem.status DESC, sem.created_at DESC, s.course_no");
    $stmt->execute([$studentIdInput]);
    $subjects = $stmt->fetchAll();

    if (count($subjects) === 1 && !$selectedSubject) {
        $selectedSubject = $subjects[0]['subj_id'];
    }

    if ($selectedSubject) {
        // Find this student record for the subject
        foreach ($subjects as $sub) {
            if ($sub['subj_id'] == $selectedSubject) {
                $student = $sub;
                break;
            }
        }

        if ($student) {
            $gradeResult = computeFinalGrade($db, $student['id'], $selectedSubject);
            $conv = convertGrade($gradeResult['final_grade'], $gradeResult['is_incomplete']);
            $catScores = [];
            foreach ($gradeResult['categories'] as $cat => $info) { $catScores[$cat] = $info['percentage']; }
            $pred = predictStudent($gradeResult['final_grade'] ?? 0, $catScores);

            // Get quiz summary
            $quizzes = $db->prepare("SELECT a.title, a.total_points, COALESCE(sc.score,0) as score
                FROM activities a
                LEFT JOIN activity_scores sc ON sc.activity_id = a.id AND sc.student_id = ?
                WHERE a.subject_id = ? ORDER BY a.created_at");
            $quizzes->execute([$student['id'], $selectedSubject]);
            $quizList = $quizzes->fetchAll();

            // Get schedule
            $sched = $db->prepare("SELECT * FROM subject_schedules WHERE subject_id=?");
            $sched->execute([$selectedSubject]);
            $schedule = $sched->fetch();

            $result = [
                'grade' => $gradeResult,
                'conv' => $conv,
                'pred' => $pred,
                'quizzes' => $quizList,
                'schedule' => $schedule
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Result — <?= htmlspecialchars($sysTitle) ?></title>
  <link rel="stylesheet" href="/grading_systemv2/assets/css/style.css">
  <style>
    .result-page { max-width: 800px; margin: 0 auto; padding: 2rem 1rem; }
    .result-header { text-align: center; margin-bottom: 2rem; }
    .result-header img { width: 64px; height: 64px; border-radius: var(--radius-md); margin: 0 auto .75rem; object-fit: cover; }
    .result-header h1 { font-size: 1.4rem; color: var(--slate-800); margin-bottom: .25rem; }
    .result-header p { color: var(--slate-500); font-size: .85rem; }
    .result-section { margin-bottom: 1.25rem; }
    .result-section h3 { font-size: .95rem; margin-bottom: .5rem; color: var(--slate-700); }
    .result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .result-item { background: var(--slate-50); padding: .75rem 1rem; border-radius: var(--radius-sm); }
    .result-item label { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--slate-500); font-weight: 600; }
    .result-item .value { font-size: 1.1rem; font-weight: 700; color: var(--slate-800); }
    .back-link { display: inline-flex; align-items: center; gap: .3rem; color: var(--primary-600); font-size: .85rem; margin-bottom: 1rem; }
  </style>
</head>
<body style="background: var(--slate-100);">
<div class="result-page">
  <div class="result-header">
    <?php if ($sysLogo): ?>
      <img src="/grading_systemv2/assets/uploads/<?= htmlspecialchars($sysLogo) ?>" alt="Logo">
    <?php endif; ?>
    <h1><?= htmlspecialchars($sysTitle) ?></h1>
    <p>Student Result Portal</p>
  </div>

  <!-- Search Form -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body">
      <form method="GET" class="flex gap-3" style="align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px;">
          <label class="form-label">Enter your Student ID</label>
          <input type="text" name="student_id" class="form-control" placeholder="e.g. 2024-0001" value="<?= htmlspecialchars($studentIdInput) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">View Result</button>
      </form>
    </div>
  </div>

  <?php if ($studentIdInput && empty($subjects)): ?>
    <div class="alert alert-error">No records found for Student ID: <?= htmlspecialchars($studentIdInput) ?></div>
  <?php endif; ?>

  <?php if (count($subjects) > 1): ?>
    <!-- Subject Picker -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-header"><h3>Select Subject</h3></div>
      <div class="card-body">
        <div class="flex gap-2" style="flex-wrap:wrap;">
          <?php foreach ($subjects as $sub): ?>
            <a href="?student_id=<?= urlencode($studentIdInput) ?>&subject_id=<?= $sub['subj_id'] ?>"
               class="btn <?= $selectedSubject==$sub['subj_id']?'btn-primary':'btn-outline' ?> btn-sm">
              <?= htmlspecialchars($sub['course_no'].' - '.$sub['descriptive_title']) ?> 
              <span class="text-xs" style="opacity:0.8; margin-left:5px;">(<?= htmlspecialchars($sub['sem_name']) ?><?= $sub['sem_status'] ? ' - Active' : '' ?>)</span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($student && $result): ?>
    <a href="?student_id=<?= urlencode($studentIdInput) ?>" class="back-link">&#8592; Back</a>

    <!-- Student Info -->
    <div class="card result-section">
      <div class="card-header"><h3>&#128100; Student Information</h3></div>
      <div class="card-body">
        <div class="result-grid">
          <div class="result-item"><label>Student Name</label><div class="value"><?= htmlspecialchars($student['last_name'].', '.$student['first_name']) ?></div></div>
          <div class="result-item"><label>Student ID</label><div class="value"><?= htmlspecialchars($student['student_id']) ?></div></div>
          <div class="result-item"><label>Course No.</label><div class="value"><?= htmlspecialchars($student['course_no']) ?></div></div>
          <div class="result-item"><label>Descriptive Title</label><div class="value"><?= htmlspecialchars($student['descriptive_title']) ?></div></div>
          <div class="result-item"><label>Course</label><div class="value"><?= htmlspecialchars($student['course_name']) ?></div></div>
          <div class="result-item"><label>Instructor</label><div class="value"><?= htmlspecialchars($student['instructor_name'] ?? '—') ?></div></div>
        </div>
      </div>
    </div>

    <!-- Schedule -->
    <?php if ($result['schedule']): $sc = $result['schedule']; ?>
    <div class="card result-section">
      <div class="card-header"><h3>&#128197; Class Schedule</h3></div>
      <div class="card-body">
        <div class="result-grid">
          <div class="result-item"><label>Day</label><div class="value"><?= htmlspecialchars($sc['day'] ?? '—') ?></div></div>
          <div class="result-item"><label>Time</label><div class="value"><?= $sc['time_start'] ? formatTime($sc['time_start']).' - '.formatTime($sc['time_end']) : '—' ?></div></div>
          <div class="result-item"><label>Room</label><div class="value"><?= htmlspecialchars($sc['room'] ?? '—') ?></div></div>
          <div class="result-item"><label>Total Students</label><div class="value"><?= $sc['total_students'] ?></div></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Grade Breakdown -->
    <div class="card result-section">
      <div class="card-header"><h3>&#127942; Grade Breakdown</h3></div>
      <div class="card-body no-pad">
        <table>
          <thead><tr><th>Category</th><th>Score %</th><th>Weight</th><th>Weighted</th></tr></thead>
          <tbody>
          <?php foreach($result['grade']['categories'] as $cat => $info): ?>
            <tr>
              <td><strong><?= htmlspecialchars($cat) ?></strong></td>
              <td><?= $info['percentage'] ?>%</td>
              <td><?= $info['weight'] ?>%</td>
              <td><?= $info['weighted'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quiz Summary -->
    <?php if (!empty($result['quizzes'])): ?>
    <div class="card result-section">
      <div class="card-header"><h3>&#128221; Summary</h3></div>
      <div class="card-body no-pad">
        <table>
          <thead><tr><th>Quiz</th><th>Score</th><th>Total</th></tr></thead>
          <tbody>
          <?php foreach($result['quizzes'] as $q): ?>
            <tr>
              <td><?= htmlspecialchars($q['title']) ?></td>
              <td><?= $q['score'] ?></td>
              <td><?= $q['total_points'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Attendance -->
    <?php $att = $result['grade']['attendance']; ?>
    <div class="card result-section">
      <div class="card-header"><h3>&#128197; Attendance Summary</h3></div>
      <div class="card-body">
        <div class="result-grid">
          <div class="result-item"><label>Present</label><div class="value" style="color:var(--accent-600);"><?= $att['present'] ?></div></div>
          <div class="result-item"><label>Late</label><div class="value" style="color:var(--warm-600);"><?= $att['late'] ?></div></div>
          <div class="result-item"><label>Absent</label><div class="value" style="color:var(--danger-500);"><?= $att['absent'] ?></div></div>
          <div class="result-item"><label>Attendance %</label><div class="value"><?= $att['total']>0 ? round($att['percentage'],1).'%' : '—' ?></div></div>
        </div>
      </div>
    </div>

    <!-- Final Result -->
    <div class="card result-section">
      <div class="card-header"><h3>&#128200; Final Result</h3></div>
      <div class="card-body">
        <div class="result-grid">
          <div class="result-item"><label>Final Grade</label><div class="value" style="font-size:1.5rem;"><?= $result['grade']['final_grade'] !== null ? round($result['grade']['final_grade'],2) : '—' ?></div></div>
          <div class="result-item"><label>Equivalent</label><div class="value" style="font-size:1.5rem;color:var(--primary-600);"><?= $result['conv'][0] ?></div></div>
          <div class="result-item"><label>Description</label><div class="value"><?= $result['conv'][1] ?></div></div>
          <div class="result-item"><label>Risk Level</label><div class="value"><span class="badge <?= riskBadgeClass($result['pred']['risk_level']) ?>" style="font-size:.85rem;padding:.35rem .85rem;"><?= $result['pred']['risk_level'] ?></span></div></div>
        </div>
      </div>
    </div>

    <!-- Prediction -->
    <div class="card result-section">
      <div class="card-header"><h3>&#128300; Prediction & Suggestions</h3></div>
      <div class="card-body">
        <?php if(!empty($result['pred']['strengths'])): ?>
          <p style="margin-bottom:.5rem;"><strong style="color:var(--accent-600);">Strengths:</strong> <?= implode(', ', $result['pred']['strengths']) ?></p>
        <?php endif; ?>
        <?php if(!empty($result['pred']['weaknesses'])): ?>
          <p style="margin-bottom:.5rem;"><strong style="color:var(--danger-500);">Weaknesses:</strong> <?= implode(', ', $result['pred']['weaknesses']) ?></p>
        <?php endif; ?>
        <p style="margin-bottom:.25rem;"><strong>Suggestions:</strong></p>
        <ul style="padding-left:1.25rem;">
          <?php foreach($result['pred']['suggestions'] as $s): ?>
            <li style="font-size:.875rem;margin-bottom:.25rem;"><?= htmlspecialchars($s) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <div style="text-align:center;margin-top:2rem;font-size:.75rem;color:var(--slate-400);">
    <p><?= htmlspecialchars($sysTitle) ?> &copy; <?= date('Y') ?></p>
    <p style="margin-top:.25rem;"><a href="/grading_systemv2/pages/login.php">Staff Login</a></p>
  </div>
</div>
</body>
</html>
