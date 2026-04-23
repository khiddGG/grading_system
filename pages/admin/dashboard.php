<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../../includes/header.php';
requireRole('admin');
$db = getDB();

$activeSem = getActiveSemester();
$semId = $activeSem['id'] ?? 0;

$totalCourses     = $db->query("SELECT COUNT(*) FROM courses WHERE status=1")->fetchColumn();
$totalSubjects    = $db->prepare("SELECT COUNT(*) FROM subjects WHERE status=1 AND semester_id=?");
$totalSubjects->execute([$semId]);
$totalSubjects = $totalSubjects->fetchColumn();

$totalInstructors = $db->query("SELECT COUNT(*) FROM users WHERE role='instructor' AND status=1")->fetchColumn();

$totalStudents = $db->prepare("SELECT COUNT(DISTINCT st.student_id) FROM students st JOIN subjects s ON s.id = st.subject_id WHERE s.semester_id = ? AND st.status=1");
$totalStudents->execute([$semId]);
$totalStudents = $totalStudents->fetchColumn();

// Chart data — students per course
$chartData = $db->prepare("SELECT c.course_name, COUNT(DISTINCT st.student_id) as cnt
    FROM courses c
    LEFT JOIN subjects s ON s.course_id = c.id AND s.status=1 AND s.semester_id=?
    LEFT JOIN students st ON st.subject_id = s.id AND st.status=1
    WHERE c.status=1 GROUP BY c.id ORDER BY c.course_name");
$chartData->execute([$semId]);
$chartData = $chartData->fetchAll();
$chartLabels = array_column($chartData, 'course_name');
$chartValues = array_column($chartData, 'cnt');
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon indigo">&#128218;</div>
    <div class="stat-info"><h4>Courses</h4><div class="stat-value"><?= $totalCourses ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon emerald">&#128203;</div>
    <div class="stat-info"><h4>Subjects</h4><div class="stat-value"><?= $totalSubjects ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber">&#128100;</div>
    <div class="stat-info"><h4>Instructors</h4><div class="stat-value"><?= $totalInstructors ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon rose">&#128101;</div>
    <div class="stat-info"><h4>Students</h4><div class="stat-value"><?= $totalStudents ?></div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>&#128202; Students per Course</h3></div>
  <div class="card-body">
    <canvas id="courseChart" height="100"></canvas>
  </div>
</div>

<script src="/grading_systemv2/assets/js/chart.min.js"></script>
<script>
new Chart(document.getElementById('courseChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      label: 'Students',
      data: <?= json_encode(array_map('intval', $chartValues)) ?>,
      backgroundColor: ['#818cf8','#34d399','#fbbf24','#fb7185','#a78bfa','#38bdf8'],
      borderRadius: 8,
      barThickness: 40
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
