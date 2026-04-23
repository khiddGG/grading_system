<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

try {
    // Find duplicates and keep only the latest one (highest ID)
    $stmt = $db->query("SELECT subject_id, student_id, GROUP_CONCAT(id ORDER BY id DESC) as ids, COUNT(*) as cnt 
                        FROM students 
                        GROUP BY subject_id, student_id 
                        HAVING cnt > 1");
    $dupes = $stmt->fetchAll();

    if (empty($dupes)) {
        echo "No duplicates found.\n";
    } else {
        echo "Found " . count($dupes) . " sets of duplicates.\n";
        foreach ($dupes as $d) {
            $ids = explode(',', $d['ids']);
            $keep = array_shift($ids);
            $delete = implode(',', $ids);
            $db->exec("DELETE FROM students WHERE id IN ($delete)");
            echo "Kept ID $keep, deleted IDs $delete for Student {$d['student_id']} in Subject {$d['subject_id']}.\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
