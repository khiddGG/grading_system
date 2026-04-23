<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('instructor');
$db = getDB();

$subjectId = $_POST['subject_id'];
$day = $_POST['day'] ?? '';
$timeStart = $_POST['time_start'] ?? null;
$timeEnd = $_POST['time_end'] ?? null;
$room = trim($_POST['room'] ?? '');
// Check if schedule exists
$exists = $db->prepare("SELECT id FROM subject_schedules WHERE subject_id=?");
$exists->execute([$subjectId]);

if ($exists->fetch()) {
    $stmt = $db->prepare("UPDATE subject_schedules SET day=?, time_start=?, time_end=?, room=? WHERE subject_id=?");
    $stmt->execute([$day, $timeStart ?: null, $timeEnd ?: null, $room, $subjectId]);
} else {
    $stmt = $db->prepare("INSERT INTO subject_schedules (subject_id, day, time_start, time_end, room) VALUES (?,?,?,?,?)");
    $stmt->execute([$subjectId, $day, $timeStart ?: null, $timeEnd ?: null, $room]);
}

setFlash('success', 'Class schedule updated.');
header('Location: /grading_systemv2/pages/instructor/subjects.php');
exit;
