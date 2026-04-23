<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $stmt = $db->prepare("INSERT INTO subjects (course_id, course_no, descriptive_title, instructor_id, semester_id, with_lab) VALUES (?,?,?,?,?,?)");
    $instId = $_POST['instructor_id'] ?: null;
    $semId = $_POST['semester_id'] ?: null;
    $stmt->execute([$_POST['course_id'], trim($_POST['course_no']), trim($_POST['descriptive_title']), $instId, $semId, $_POST['with_lab']]);

    // Create empty schedule if instructor assigned
    $subjectId = $db->lastInsertId();
    if ($instId) {
        $db->prepare("INSERT INTO subject_schedules (subject_id) VALUES (?)")->execute([$subjectId]);
    }
    setFlash('success', 'Subject created.');
} elseif ($action === 'update') {
    $instId = $_POST['instructor_id'] ?: null;
    $semId = $_POST['semester_id'] ?: null;
    $stmt = $db->prepare("UPDATE subjects SET course_id=?, course_no=?, descriptive_title=?, instructor_id=?, semester_id=?, with_lab=? WHERE id=?");
    $stmt->execute([$_POST['course_id'], trim($_POST['course_no']), trim($_POST['descriptive_title']), $instId, $semId, $_POST['with_lab'], $_POST['id']]);

    // Ensure schedule record exists
    $exists = $db->prepare("SELECT id FROM subject_schedules WHERE subject_id=?");
    $exists->execute([$_POST['id']]);
    if (!$exists->fetch()) {
        $db->prepare("INSERT INTO subject_schedules (subject_id) VALUES (?)")->execute([$_POST['id']]);
    }
    setFlash('success', 'Subject updated.');
} elseif ($action === 'toggle') {
    $db->prepare("UPDATE subjects SET status=? WHERE id=?")->execute([$_POST['status'], $_POST['id']]);
    setFlash('success', 'Subject status updated.');
}

header('Location: /grading_systemv2/pages/admin/subjects.php');
exit;
