<?php
/**
 * Grading, Prediction, and Helper Functions
 */

/**
 * Get a setting value from database
 */
function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

/**
 * Get the current active semester
 */
function getActiveSemester() {
    $db = getDB();
    return $db->query("SELECT * FROM semesters WHERE status=1 LIMIT 1")->fetch();
}

/**
 * Check if a specific semester is active
 */
function isSemesterActive($semesterId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT status FROM semesters WHERE id = ?");
    $stmt->execute([$semesterId]);
    return (bool)$stmt->fetchColumn();
}


/**
 * Grade conversion table
 * Returns [equivalent, description]
 */
function convertGrade($grade, $isIncomplete = false) {
    if ($isIncomplete || $grade === null || $grade === '') return ['INC', 'Incomplete'];
    $g = round($grade, 2);
    if ($g >= 97) return ['1.00', 'Excellent'];
    if ($g >= 93) return ['1.25', 'Excellent'];
    if ($g >= 89) return ['1.50', 'Highly Satisfactory'];
    if ($g >= 85) return ['1.75', 'Highly Satisfactory'];
    if ($g >= 80) return ['2.00', 'Satisfactory'];
    if ($g >= 75) return ['2.25', 'Satisfactory'];
    if ($g >= 70) return ['2.50', 'Fairly Satisfactory'];
    if ($g >= 65) return ['2.75', 'Fairly Satisfactory'];
    if ($g >= 60) return ['3.00', 'Passed'];
    if ($g >= 55) return ['4.00', 'Conditional'];
    return ['5.00', 'Failed'];
}

/**
 * Prediction logic - returns [risk_level, weaknesses[], strengths[], suggestions[]]
 */
function predictStudent($finalGrade, $categoryScores = []) {
    $result = [
        'risk_level'  => 'Low',
        'weaknesses'  => [],
        'strengths'   => [],
        'suggestions' => []
    ];

    // Risk level based on final grade
    if ($finalGrade >= 90) {
        $result['risk_level'] = 'Low';
    } elseif ($finalGrade >= 80) {
        $result['risk_level'] = 'Medium';
    } else {
        $result['risk_level'] = 'High';
    }

    // Category analysis
    foreach ($categoryScores as $cat => $pct) {
        $catName = ucfirst(strtolower($cat));
        if ($pct >= 85) {
            $result['strengths'][] = $catName;
        } elseif ($pct < 75) {
            $result['weaknesses'][] = $catName;
        }
    }

    // Generate suggestions
    if (in_array('Quiz', $result['weaknesses'])) {
        $result['suggestions'][] = 'Review quiz materials and practice more exercises.';
    }
    if (in_array('Attendance', $result['weaknesses'])) {
        $result['suggestions'][] = 'Improve class attendance to avoid falling behind.';
    }
    if (in_array('Exam', $result['weaknesses'])) {
        $result['suggestions'][] = 'Focus on exam preparation and review key topics.';
    }
    if (in_array('Lab', $result['weaknesses'])) {
        $result['suggestions'][] = 'Spend more time on lab exercises and hands-on practice.';
    }

    if (empty($result['suggestions'])) {
        if ($result['risk_level'] === 'Low') {
            $result['suggestions'][] = 'Keep up the excellent work!';
        } elseif ($result['risk_level'] === 'Medium') {
            $result['suggestions'][] = 'Maintain consistent effort across all areas.';
        } else {
            $result['suggestions'][] = 'Seek help from the instructor and improve study habits.';
        }
    }

    return $result;
}

/**
 * Compute category score for a student in a subject
 * Returns percentage (0-100) of total earned vs total possible
 */
function computeCategoryScore($db, $studentId, $subjectId, $category) {
    // Get all activities for this category
    $stmt = $db->prepare("SELECT a.id, a.total_points, COALESCE(s.score, 0) as score
        FROM activities a
        LEFT JOIN activity_scores s ON s.activity_id = a.id AND s.student_id = ?
        WHERE a.subject_id = ? AND a.category = ?");
    $stmt->execute([$studentId, $subjectId, $category]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) return null;

    $totalPoints = 0;
    $totalScore = 0;
    foreach ($rows as $r) {
        $totalPoints += $r['total_points'];
        $totalScore += $r['score'];
    }

    if ($totalPoints == 0) return 0;
    return ($totalScore / $totalPoints) * 100;
}

/**
 * Compute attendance percentage for a student
 * Late counts as present for scoring
 */
function computeAttendanceScore($db, $studentId, $subjectId) {
    $stmt = $db->prepare("SELECT ar.status
        FROM attendance_records ar
        JOIN attendance a ON a.id = ar.attendance_id
        WHERE ar.student_id = ? AND a.subject_id = ?");
    $stmt->execute([$studentId, $subjectId]);
    $records = $stmt->fetchAll();

    if (empty($records)) return ['percentage' => null, 'present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];

    $present = 0; $late = 0; $absent = 0;
    foreach ($records as $r) {
        if ($r['status'] == 1) $present++;
        elseif ($r['status'] == 2) { $late++; }
        else $absent++;
    }

    $total = count($records);
    // Late counts as present for scoring
    $scored = $present + $late;
    $pct = $total > 0 ? ($scored / $total) * 100 : 0;

    return ['percentage' => $pct, 'present' => $present, 'late' => $late, 'absent' => $absent, 'total' => $total];
}

/**
 * Compute final grade for a student in a subject
 * Returns [final_grade, category_scores[], attendance_info]
 */
function computeFinalGrade($db, $studentId, $subjectId) {
    // Get subject info
    $stmt = $db->prepare("SELECT with_lab FROM subjects WHERE id = ?");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch();
    $withLab = (int)($subject['with_lab'] ?? 0);

    // Get criteria
    $stmt = $db->prepare("SELECT * FROM criteria WHERE subject_id = ? ORDER BY type, id");
    $stmt->execute([$subjectId]);
    $allCriteria = $stmt->fetchAll();

    if (empty($allCriteria)) {
        return [
            'final_grade' => null,
            'is_incomplete' => true,
            'categories'  => [],
            'attendance'  => computeAttendanceScore($db, $studentId, $subjectId)
        ];
    }

    $attendanceInfo = computeAttendanceScore($db, $studentId, $subjectId);
    $resultsByType = [
        'Lecture' => ['total' => 0, 'details' => []],
        'Lab'     => ['total' => 0, 'details' => []]
    ];

    $isIncomplete = false;
    foreach ($allCriteria as $cr) {
        $type = $cr['type'] ?: 'Lecture';
        $cat = $cr['category'];
        $weight = $cr['weight'];

        if (strtolower($cat) === 'attendance') {
            $pct = $attendanceInfo['percentage'];
            if ($pct === null) $isIncomplete = true;
        } else {
            // Get average score for this category and type
            $stmt = $db->prepare("SELECT a.id, a.total_points, COALESCE(s.score, 0) as score
                FROM activities a
                LEFT JOIN activity_scores s ON s.activity_id = a.id AND s.student_id = ?
                WHERE a.subject_id = ? AND a.category = ? AND a.type = ?");
            $stmt->execute([$studentId, $subjectId, $cat, $type]);
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                $pct = 0;
                $isIncomplete = true; // No activities found for this required category
            } else {
                $tPoints = 0; $tScore = 0;
                foreach ($rows as $r) {
                    $tPoints += $r['total_points'];
                    $tScore += $r['score'];
                }
                $pct = $tPoints > 0 ? ($tScore / $tPoints) * 100 : 0;
            }
        }

        $pct = $pct ?? 0;
        $weighted = ($pct * $weight) / 100;
        $resultsByType[$type]['total'] += $weighted;
        $resultsByType[$type]['details'][$cat] = [
            'percentage' => round($pct, 2),
            'weight'     => $weight,
            'weighted'   => round($weighted, 2),
            'type'       => $type
        ];
    }

    if ($withLab) {
        // Final Grade = (Lecture × 0.6667) + (Lab × 0.3333)
        $lec = $resultsByType['Lecture']['total'];
        $lab = $resultsByType['Lab']['total'];
        $finalGrade = round(($lec * 0.6667) + ($lab * 0.3333), 2);
    } else {
        $finalGrade = round($resultsByType['Lecture']['total'], 2);
    }

    return [
        'final_grade' => $finalGrade,
        'is_incomplete' => $isIncomplete,
        'categories'  => array_merge($resultsByType['Lecture']['details'], $resultsByType['Lab']['details']),
        'attendance'  => $attendanceInfo,
        'lecture_total' => round($resultsByType['Lecture']['total'], 2),
        'lab_total' => round($resultsByType['Lab']['total'], 2)
    ];
}

/**
 * Get risk badge CSS class
 */
function riskBadgeClass($level) {
    switch ($level) {
        case 'Low':    return 'badge-active';
        case 'Medium': return 'badge-warning';
        case 'High':   return 'badge-danger';
        default:       return 'badge-archived';
    }
}

/**
 * Format time for display
 */
function formatTime($time) {
    if (!$time) return '—';
    return date('g:i A', strtotime($time));
}

/**
 * Get day badge CSS class
 */
function dayBadgeClass($day) {
    $d = strtolower($day ?? '');
    if (strpos($d, 'mwf') !== false) return 'day-mwf';
    if (strpos($d, 'tth') !== false || strpos($d, 'tt') !== false) return 'day-tth';
    if (strpos($d, 'sat') !== false) return 'day-sat';
    return 'day-default';
}
