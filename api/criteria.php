<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('instructor');
$db = getDB();
$action = $_POST['action'] ?? '';
$subjectId = $_POST['subject_id'] ?? '';

if ($action === 'create' || $action === 'update') {
    $cat = trim($_POST['category']);
    $weight = floatval($_POST['weight']);
    $type = $_POST['type'] ?? 'Lecture';
    $id = $_POST['id'] ?? 0;

    // Check current total for this type
    $q = "SELECT SUM(weight) FROM criteria WHERE subject_id = ? AND type = ?";
    $params = [$subjectId, $type];
    if ($action === 'update') {
        $q .= " AND id != ?";
        $params[] = $id;
    }
    
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    $currentTotal = $stmt->fetchColumn() ?: 0;

    if (($currentTotal + $weight) > 100) {
        setFlash('error', "Cannot save. Total weight for $type would exceed 100% (Current: $currentTotal%, New: $weight%).");
    } else {
        if ($action === 'create') {
            $db->prepare("INSERT INTO criteria (subject_id, category, weight, type) VALUES (?,?,?,?)")
               ->execute([$subjectId, $cat, $weight, $type]);
            setFlash('success', 'Criteria added.');
        } else {
            $db->prepare("UPDATE criteria SET category=?, weight=?, type=? WHERE id=?")
               ->execute([$cat, $weight, $type, $id]);
            setFlash('success', 'Criteria updated.');
        }
    }
} elseif ($action === 'delete') {
    $db->prepare("DELETE FROM criteria WHERE id=?")->execute([$_POST['id']]);
    setFlash('success', 'Criteria removed.');
}

header("Location: /grading_systemv2/pages/instructor/criteria.php?subject_id=$subjectId");
exit;
