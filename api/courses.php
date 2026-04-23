<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $stmt = $db->prepare("INSERT INTO courses (course_name, description) VALUES (?, ?)");
    $stmt->execute([trim($_POST['course_name']), trim($_POST['description'] ?? '')]);
    setFlash('success', 'Course created successfully.');
} elseif ($action === 'update') {
    $stmt = $db->prepare("UPDATE courses SET course_name=?, description=? WHERE id=?");
    $stmt->execute([trim($_POST['course_name']), trim($_POST['description'] ?? ''), $_POST['id']]);
    setFlash('success', 'Course updated.');
} elseif ($action === 'toggle') {
    $stmt = $db->prepare("UPDATE courses SET status=? WHERE id=?");
    $stmt->execute([$_POST['status'], $_POST['id']]);
    setFlash('success', 'Course status updated.');
}

header('Location: /grading_systemv2/pages/admin/courses.php');
exit;
