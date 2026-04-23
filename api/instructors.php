<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,'instructor')");
    $stmt->execute([trim($_POST['username']), $hash, trim($_POST['full_name'])]);
    setFlash('success', 'Instructor created.');
} elseif ($action === 'update') {
    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET full_name=?, username=?, password=? WHERE id=?");
        $stmt->execute([trim($_POST['full_name']), trim($_POST['username']), $hash, $_POST['id']]);
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name=?, username=? WHERE id=?");
        $stmt->execute([trim($_POST['full_name']), trim($_POST['username']), $_POST['id']]);
    }
    setFlash('success', 'Instructor updated.');
} elseif ($action === 'toggle') {
    $db->prepare("UPDATE users SET status=? WHERE id=?")->execute([$_POST['status'], $_POST['id']]);
    setFlash('success', 'Instructor status updated.');
}

header('Location: /grading_systemv2/pages/admin/instructors.php');
exit;
