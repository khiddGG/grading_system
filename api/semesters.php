<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name']);
    $db->prepare("INSERT INTO semesters (name, status) VALUES (?, 0)")->execute([$name]);
    setFlash('success', 'Semester created.');
} elseif ($action === 'activate') {
    $id = $_POST['id'];
    // Deactivate all others
    $db->exec("UPDATE semesters SET status = 0");
    // Activate this one
    $db->prepare("UPDATE semesters SET status = 1 WHERE id = ?")->execute([$id]);
    setFlash('success', 'Semester activated. System is now in this cycle.');
} elseif ($action === 'delete') {
    $id = $_POST['id'];
    // Don't delete if active
    $active = $db->prepare("SELECT status FROM semesters WHERE id=?");
    $active->execute([$id]);
    if ($active->fetchColumn()) {
        setFlash('error', 'Cannot delete the active semester.');
    } else {
        $db->prepare("DELETE FROM semesters WHERE id=?")->execute([$id]);
        setFlash('success', 'Semester removed.');
    }
} elseif ($action === 'update') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $db->prepare("UPDATE semesters SET name = ? WHERE id = ?")->execute([$name, $id]);
    setFlash('success', 'Semester updated.');
}

header('Location: /grading_systemv2/pages/admin/semesters.php');
exit;
