<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

requireRole('instructor');
$db = getDB();
$action = $_POST['action'] ?? '';
$subjectId = $_POST['subject_id'] ?? '';

try {
    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO students (subject_id, student_id, first_name, last_name, gender) VALUES (?,?,?,?,?)");
        $stmt->execute([$subjectId, trim($_POST['student_id']), trim($_POST['first_name']), trim($_POST['last_name']), $_POST['gender']]);
        setFlash('success', 'Student added.');
    } elseif ($action === 'update') {
        $stmt = $db->prepare("UPDATE students SET student_id=?, first_name=?, last_name=?, gender=? WHERE id=?");
        $stmt->execute([trim($_POST['student_id']), trim($_POST['first_name']), trim($_POST['last_name']), $_POST['gender'], $_POST['id']]);
        setFlash('success', 'Student updated.');
    } elseif ($action === 'remove') {
        $db->prepare("UPDATE students SET status=0 WHERE id=?")->execute([$_POST['id']]);
        setFlash('success', 'Student removed.');
    } elseif ($action === 'bulk_import') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $filename = $_FILES['csv_file']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $count = 0;

            if ($ext === 'xlsx') {
                if ($xlsx = SimpleXLSX::parse($_FILES['csv_file']['tmp_name'])) {
                    $rows = $xlsx->rows();
                    foreach ($rows as $i => $row) {
                        if ($i === 0) { // Check for header
                            if (isset($row[0]) && (stripos($row[0], 'id') !== false || stripos($row[0], 'student') !== false)) continue;
                        }
                        if (count($row) >= 2) {
                            try {
                                processStudentRow($db, $subjectId, $row);
                                $count++;
                            } catch (Exception $e) {
                                throw new Exception("Error on row " . ($i + 1) . ": " . $e->getMessage());
                            }
                        }
                    }
                    setFlash('success', "$count students imported from Excel successfully.");
                } else {
                    throw new Exception('Excel parse error.');
                }
            } elseif ($ext === 'xls') {
                throw new Exception('Legacy .xls format is not supported. Please Save As .xlsx or .csv.');
            } else {
                // Default to CSV
                $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
                $firstRow = fgetcsv($file);
                $i = 1;
                if (!(isset($firstRow[0]) && (stripos($firstRow[0], 'id') !== false || stripos($firstRow[0], 'student') !== false))) {
                    if (count($firstRow) >= 2) {
                        try {
                            processStudentRow($db, $subjectId, $firstRow);
                            $count++;
                        } catch (Exception $e) {
                            throw new Exception("Error on row 1: " . $e->getMessage());
                        }
                    }
                }
                while (($data = fgetcsv($file)) !== FALSE) {
                    $i++;
                    if (count($data) >= 2) {
                        try {
                            processStudentRow($db, $subjectId, $data);
                            $count++;
                        } catch (Exception $e) {
                            throw new Exception("Error on row $i: " . $e->getMessage());
                        }
                    }
                }
                fclose($file);
                setFlash('success', "$count students imported from CSV successfully.");
            }
        } else {
            setFlash('error', 'Please upload a valid file.');
        }
    }
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'Duplicate entry') !== false) $msg = "One or more students already exist in this subject.";
    if (strpos($msg, 'Data too long') !== false) $msg = "One or more fields have too much data.";
    setFlash('error', "System Error: " . $msg);
}

/**
 * Helper to process a single student row
 */
function processStudentRow($db, $subjectId, $data) {
    $studentId = substr(toUTF8(trim($data[0] ?? '')), 0, 100);
    $fullName = toUTF8(trim($data[1] ?? ''));
    
    // Gender from Column W (index 22)
    $genderRaw = strtoupper(trim($data[22] ?? 'Male'));
    $gender = 'Male';
    if ($genderRaw === 'F' || $genderRaw === 'FEMALE') $gender = 'Female';
    elseif ($genderRaw === 'M' || $genderRaw === 'MALE') $gender = 'Male';

    if (!$studentId || !$fullName) return;

    if (strpos($fullName, ',') !== false) {
        $parts = explode(',', $fullName);
        $lastName = trim($parts[0]);
        $firstName = trim($parts[1] ?? '—');
    } else {
        $parts = explode(' ', $fullName);
        if (count($parts) > 1) {
            $lastName = array_pop($parts);
            $firstName = implode(' ', $parts);
        } else {
            $firstName = $fullName;
            $lastName = '—';
        }
    }

    $firstName = substr($firstName, 0, 100);
    $lastName = substr($lastName, 0, 100);

    $stmt = $db->prepare("INSERT INTO students (subject_id, student_id, first_name, last_name, gender, status) VALUES (?,?,?,?,?,1)
                          ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), gender=VALUES(gender), status=1");
    $stmt->execute([$subjectId, $studentId, $firstName, $lastName, $gender]);
}

/**
 * Convert string to UTF-8 if it's not already
 */
function toUTF8($str) {
    if (!$str) return '';
    if (mb_check_encoding($str, 'UTF-8')) return $str;
    // Try to convert from common Excel encoding (ISO-8859-1/Windows-1252)
    return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
}

header("Location: /grading_systemv2/pages/instructor/students.php?subject_id=$subjectId");
exit;
