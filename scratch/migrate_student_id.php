<?php
require_once __DIR__ . '/../config/database.php';
try {
    getDB()->exec("ALTER TABLE students MODIFY COLUMN student_id VARCHAR(100) NOT NULL");
    echo "Column student_id increased to 100 successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
