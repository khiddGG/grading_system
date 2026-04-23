<?php
$pageTitle = 'Evaluate';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 1 && isset($_GET['subject_id'])) {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
    $db = getDB();
    $subjectId = $_GET['subject_id'];

    $stmt = $db->prepare("SELECT category FROM criteria WHERE subject_id = ? GROUP BY category, type ORDER BY MIN(id)");
    $stmt->execute([$subjectId]);
    $criteriaCols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $db->prepare("SELECT * FROM students WHERE subject_id = ? AND status = 1 ORDER BY last_name, first_name");
    $stmt->execute([$subjectId]);
    $students = $stmt->fetchAll();

    $filename = "Evaluation_Subject_" . $subjectId . "_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fputcsv($output, array_merge(['Student ID', 'Name'], $criteriaCols, ['Final Grade', 'Equivalent']));

    foreach ($students as $st) {
        $res = computeFinalGrade($db, $st['id'], $subjectId);
        $conv = convertGrade($res['final_grade'], $res['is_incomplete']);
        $row = [$st['student_id'], $st['last_name'] . ', ' . $st['first_name']];
        foreach ($criteriaCols as $cat) { $row[] = isset($res['categories'][$cat]) ? $res['categories'][$cat]['percentage'] . '%' : '—'; }
        $row[] = $res['final_grade'] ?? '0';
        $row[] = $conv[0];
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
requireRole('instructor');
$db = getDB();
$uid = currentUserId();

// Semester Logic
$sems = $db->query("SELECT * FROM semesters ORDER BY created_at DESC")->fetchAll();
$activeSem = getActiveSemester();
$selectedSemester = $_GET['semester_id'] ?? ($activeSem['id'] ?? null);

$subs = $db->prepare("SELECT id, course_no, descriptive_title FROM subjects WHERE instructor_id=? AND status=1 AND semester_id=? ORDER BY course_no");
$subs->execute([$uid, $selectedSemester]);
$mySubjects = $subs->fetchAll();
$selectedSubject = $_GET['subject_id'] ?? ($mySubjects[0]['id'] ?? null);

$search = $_GET['search'] ?? '';
$riskFilter = $_GET['risk'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';

$students = [];
if ($selectedSubject) {
    $q = "SELECT * FROM students WHERE subject_id=? AND status=1";
    $params = [$selectedSubject];
    if ($search) {
        $q .= " AND (first_name LIKE ? OR last_name LIKE ? OR student_id LIKE ?)";
        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like]);
    }
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    foreach ($students as &$st) {
        $result = computeFinalGrade($db, $st['id'], $selectedSubject);
        $conv = convertGrade($result['final_grade'], $result['is_incomplete']);
        $catScores = [];
        foreach ($result['categories'] as $cat => $info) { $catScores[$cat] = $info['percentage']; }
        $pred = predictStudent($result['final_grade'] ?? 0, $catScores);

        $st['final_grade'] = $result['final_grade'];
        $st['is_incomplete'] = $result['is_incomplete'];
        $st['equivalent'] = $conv[0];
        $st['description'] = $conv[1];
        $st['prediction'] = $pred;
        $st['attendance'] = $result['attendance'];
        $st['categories'] = $result['categories'];
    }
    unset($st);

    if ($riskFilter) {
        $students = array_filter($students, function($s) use ($riskFilter) { return ($s['prediction']['risk_level'] ?? '') === $riskFilter; });
    }

    usort($students, function($a, $b) use ($sortBy) {
        switch ($sortBy) {
            case 'highest': return ($b['final_grade'] ?? 0) - ($a['final_grade'] ?? 0);
            case 'lowest':  return ($a['final_grade'] ?? 0) - ($b['final_grade'] ?? 0);
            case 'risk':
                $order = ['High'=>0,'Medium'=>1,'Low'=>2];
                return ($order[$a['prediction']['risk_level']] ?? 3) - ($order[$b['prediction']['risk_level']] ?? 3);
            default: return strcmp($a['last_name'], $b['last_name']);
        }
    });
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
    <div class="form-group" style="margin-bottom:0;max-width:150px;"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"></div>
    <button type="submit" class="btn btn-primary">Filter</button>
  </form>
  <?php if($selectedSubject): ?>
    <a href="?semester_id=<?= $selectedSemester ?>&subject_id=<?= $selectedSubject ?>&export=1" class="btn btn-outline" style="margin-left:auto;">&#128229; Export CSV</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header"><h3>&#128200; Student Evaluation</h3><span class="text-sm text-muted"><?= count($students) ?> student(s)</span></div>
  <div class="card-body no-pad">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Student</th><th>Final Grade</th><th>Equiv.</th><th>Description</th><th>Risk</th><th>Attendance</th><th>Details</th></tr></thead>
        <tbody>
        <?php if(empty($students)): ?>
          <tr><td colspan="7"><div class="empty-state">No data.</div></td></tr>
        <?php else: foreach($students as $st): $pred = $st['prediction']; $att = $st['attendance']; ?>
          <tr>
            <td><strong><?= htmlspecialchars($st['last_name'].', '.$st['first_name']) ?></strong><br><span class="text-xs text-muted"><?= htmlspecialchars($st['student_id']) ?></span></td>
            <td><strong><?= $st['final_grade'] !== null ? round($st['final_grade'],2) : '—' ?></strong></td>
            <td><span class="badge badge-primary"><?= $st['equivalent'] ?></span></td>
            <td class="text-sm"><?= $st['description'] ?></td>
            <td><span class="badge <?= riskBadgeClass($pred['risk_level'] ?? 'Low') ?>"><?= $pred['risk_level'] ?? 'Low' ?></span></td>
            <td class="text-xs">P:<?= $att['present'] ?> L:<?= $att['late'] ?> A:<?= $att['absent'] ?><?php if($att['total']>0): ?><br><?= round($att['percentage'],1) ?>%<?php endif; ?></td>
            <td><button class="btn btn-outline btn-sm" onclick='showDetail(<?= json_encode(["name"=>$st['last_name'].', '.$st['first_name'],"sid"=>$st['student_id'],"final"=>$st['final_grade'],"equiv"=>$st['equivalent'],"desc"=>$st['description'],"risk"=>$pred['risk_level']??"Low","weaknesses"=>$pred['weaknesses']??[],"strengths"=>$pred['strengths']??[],"suggestions"=>$pred['suggestions']??[],"att"=>$att,"cats"=>$st['categories']]) ?>)'>View</button></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="detailModal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header"><h3 id="detailTitle">Student Details</h3><button class="modal-close" onclick="closeModal('detailModal')">&times;</button></div>
    <div class="modal-body" id="detailBody"></div>
  </div>
</div>

<script>
function showDetail(d){
  var h = '<p><strong>'+d.name+'</strong> ('+d.sid+')</p><hr style="margin:.75rem 0;border:0;border-top:1px solid #e2e8f0;">';
  h += '<p><strong>Final Grade:</strong> '+(d.final!==null?d.final:'—')+' | <strong>Equivalent:</strong> '+d.equiv+' | <strong>'+d.desc+'</strong></p>';
  h += '<p><strong>Risk Level:</strong> <span class="badge '+(d.risk==='Low'?'badge-active':d.risk==='Medium'?'badge-warning':'badge-danger')+'">'+d.risk+'</span></p>';
  if(d.strengths.length) h += '<p style="color:#059669;"><strong>Strengths:</strong> '+d.strengths.join(', ')+'</p>';
  if(d.weaknesses.length) h += '<p style="color:#e11d48;"><strong>Weaknesses:</strong> '+d.weaknesses.join(', ')+'</p>';
  h += '<p><strong>Suggestions:</strong></p><ul style="padding-left:1.2rem;margin:.25rem 0;">';
  d.suggestions.forEach(function(s){ h += '<li style="font-size:.85rem;">'+s+'</li>'; });
  h += '</ul><hr style="margin:.75rem 0;border:0;border-top:1px solid #e2e8f0;">';
  h += '<p><strong>Attendance:</strong> Present='+d.att.present+' Late='+d.att.late+' Absent='+d.att.absent;
  if(d.att.total>0) h += ' ('+Math.round(d.att.percentage*10)/10+'%)';
  h += '</p>';
  if(Object.keys(d.cats).length){
    h += '<table style="width:100%;font-size:.8rem;margin-top:.5rem;"><thead><tr><th>Category</th><th>Score%</th><th>Weight</th><th>Weighted</th></tr></thead><tbody>';
    for(var cat in d.cats){ var c=d.cats[cat]; h += '<tr><td>'+cat+'</td><td>'+c.percentage+'%</td><td>'+c.weight+'%</td><td>'+c.weighted+'</td></tr>'; }
    h += '</tbody></table>';
  }
  document.getElementById('detailTitle').textContent = d.name;
  document.getElementById('detailBody').innerHTML = h;
  openModal('detailModal');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
