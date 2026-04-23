<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

try {
    echo "Starting migration...<br>";
    
    // Check if 'type' column exists in criteria
    $check = $db->query("SHOW COLUMNS FROM criteria LIKE 'type'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE criteria ADD COLUMN type ENUM('Lecture', 'Lab') DEFAULT 'Lecture' AFTER weight");
        echo "Added 'type' column to criteria table.<br>";
    } else {
        echo "'type' column already exists in criteria table.<br>";
    }

    // Check if 'type' column exists in activities
    $check = $db->query("SHOW COLUMNS FROM activities LIKE 'type'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE activities ADD COLUMN type ENUM('Lecture', 'Lab') DEFAULT 'Lecture' AFTER total_points");
        echo "Added 'type' column to activities table.<br>";
    } else {
        echo "'type' column already exists in activities table.<br>";
    }

    echo "<strong>Migration completed successfully!</strong><br>";
    echo "<a href='/grading_systemv2/pages/instructor/criteria.php'>Return to Criteria Page</a>";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
