<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('instructor');
$db = getDB();
$action = $_POST['action'] ?? '';
$subjectId = $_POST['subject_id'] ?? '';
$tab = $_POST['tab'] ?? 'quizzes';

if ($action === 'create_activity') {
    $info = explode('|', $_POST['crit_info'] ?? '');
    $category = $info[0] ?? '';
    $type = $info[1] ?? 'Lecture';

    $db->prepare("INSERT INTO activities (subject_id, category, title, total_points, activity_date, type) VALUES (?,?,?,?,?,?)")
       ->execute([$subjectId, $category, trim($_POST['title']), floatval($_POST['total_points']), $_POST['activity_date'] ?: null, $type]);
    setFlash('success', 'Activity created.');
} elseif ($action === 'update_activity') {
    $info = explode('|', $_POST['crit_info'] ?? '');
    $category = $info[0] ?? '';
    $type = $info[1] ?? 'Lecture';

    $db->prepare("UPDATE activities SET category=?, title=?, total_points=?, activity_date=?, type=? WHERE id=?")
       ->execute([$category, trim($_POST['title']), floatval($_POST['total_points']), $_POST['activity_date'] ?: null, $type, $_POST['id']]);
    setFlash('success', 'Activity updated.');
} elseif ($action === 'delete_activity') {
    $db->prepare("DELETE FROM activity_scores WHERE activity_id=?")->execute([$_POST['id']]);
    $db->prepare("DELETE FROM activities WHERE id=?")->execute([$_POST['id']]);
    setFlash('success', 'Activity deleted.');
} elseif ($action === 'save_scores') {
    $activityId = $_POST['activity_id'];
    $scores = $_POST['scores'] ?? [];
    foreach ($scores as $studentId => $score) {
        if ($score === '' || $score === null) continue;
        $db->prepare("INSERT INTO activity_scores (activity_id, student_id, score) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE score=VALUES(score)")
           ->execute([$activityId, $studentId, floatval($score)]);
    }
    setFlash('success', 'Scores saved.');
} elseif ($action === 'create_attendance') {
    $db->prepare("INSERT INTO attendance (subject_id, session_date, title) VALUES (?,?,?)")
       ->execute([$subjectId, $_POST['session_date'], trim($_POST['title'] ?? '')]);
    setFlash('success', 'Attendance session created.');
} elseif ($action === 'update_attendance') {
    $db->prepare("UPDATE attendance SET session_date=?, title=? WHERE id=?")
       ->execute([$_POST['session_date'], trim($_POST['title'] ?? ''), $_POST['id']]);
    setFlash('success', 'Attendance session updated.');
} elseif ($action === 'delete_attendance') {
    $db->prepare("DELETE FROM attendance_records WHERE attendance_id=?")->execute([$_POST['id']]);
    $db->prepare("DELETE FROM attendance WHERE id=?")->execute([$_POST['id']]);
    setFlash('success', 'Attendance session deleted.');
} elseif ($action === 'save_attendance') {
    $attId = $_POST['attendance_id'];
    $records = $_POST['records'] ?? [];
    foreach ($records as $studentId => $status) {
        $db->prepare("INSERT INTO attendance_records (attendance_id, student_id, status) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE status=VALUES(status)")
           ->execute([$attId, $studentId, intval($status)]);
    }
    setFlash('success', 'Attendance saved.');
}

header("Location: /grading_systemv2/pages/instructor/activities.php?subject_id=$subjectId&tab=$tab");
exit;
