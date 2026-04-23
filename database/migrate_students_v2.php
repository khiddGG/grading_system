<?php
/**
 * Migration: Refine Students Table
 * - Increase column lengths for names and IDs
 * - Add unique constraint for (subject_id, student_id)
 */
require_once __DIR__ . '/../config/database.php';
$db = getDB();

try {
    echo "Starting Students Table Migration...<br>";

    // 1. Increase column lengths
    $db->exec("ALTER TABLE students MODIFY COLUMN student_id VARCHAR(100) NOT NULL");
    $db->exec("ALTER TABLE students MODIFY COLUMN first_name VARCHAR(100) NOT NULL");
    $db->exec("ALTER TABLE students MODIFY COLUMN last_name VARCHAR(100) NOT NULL");
    echo "Column lengths increased to 100.<br>";

    // 2. Add unique constraint if not exists
    // We check if it exists first to avoid errors on re-run
    $check = $db->query("SHOW INDEX FROM students WHERE Key_name = 'idx_subject_student'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE students ADD UNIQUE INDEX idx_subject_student (subject_id, student_id)");
        echo "Unique index 'idx_subject_student' added successfully.<br>";
    } else {
        echo "Unique index already exists.<br>";
    }

    echo "<strong>Migration Successful!</strong><br>";
    echo "<a href='/grading_systemv2/pages/instructor/students.php'>Back to Students</a>";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
