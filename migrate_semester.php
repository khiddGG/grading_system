<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

try {
    echo "Starting Semester Management Migration...<br>";
    
    // 1. Create semesters table
    $db->exec("CREATE TABLE IF NOT EXISTS semesters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        status TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "Table 'semesters' created/verified.<br>";

    // 2. Insert initial semester if none exists
    $count = $db->query("SELECT COUNT(*) FROM semesters")->fetchColumn();
    if ($count == 0) {
        $db->exec("INSERT INTO semesters (name, status) VALUES ('First Semester 2024-2025', 1)");
        echo "Initial semester created.<br>";
    }

    // 3. Add semester_id to subjects
    $check = $db->query("SHOW COLUMNS FROM subjects LIKE 'semester_id'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE subjects ADD COLUMN semester_id INT AFTER instructor_id");
        echo "Added 'semester_id' to subjects table.<br>";
        
        // Link existing subjects to the active semester
        $activeId = $db->query("SELECT id FROM semesters WHERE status=1 LIMIT 1")->fetchColumn();
        if ($activeId) {
            $db->prepare("UPDATE subjects SET semester_id = ? WHERE semester_id IS NULL")->execute([$activeId]);
            echo "Linked existing subjects to active semester.<br>";
        }
        
        $db->exec("ALTER TABLE subjects ADD FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE");
        echo "Foreign key added to subjects.<br>";
    }

    echo "<strong>Semester Migration Successful!</strong><br>";
    echo "<a href='/grading_systemv2/pages/admin/dashboard.php'>Go to Dashboard</a>";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
