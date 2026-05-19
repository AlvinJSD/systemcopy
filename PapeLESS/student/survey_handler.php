<?php
// ============================================================
// PapeLESS - Student Survey Handler (AJAX)
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'submit_survey': handleSurvey(); break;
    default: jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
}

function handleSurvey(): void {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
    }

    $studentId = $_SESSION['user_id'];

    if (hasSurveyToday($studentId)) {
        jsonResponse(['success' => false, 'error' => 'You have already submitted your check-in today.']);
    }

    $q1 = (int)($_POST['q1_mood'] ?? 0);
    $q2 = (int)($_POST['q2_stress'] ?? 0);
    $q3 = (int)($_POST['q3_needs_consultation'] ?? 0);
    $q4 = (int)($_POST['q4_company_problem'] ?? 0);
    $q5 = (int)($_POST['q5_experience_rating'] ?? 0);
    $notes = sanitize($_POST['additional_notes'] ?? '');

    if (!$q1 || !$q2 || !$q5) {
        jsonResponse(['success' => false, 'error' => 'Please answer all required questions.']);
    }

    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO surveys (student_id, survey_date, q1_mood, q2_stress, q3_needs_consultation, q4_company_problem, q5_experience_rating, additional_notes)
        VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$studentId, $q1, $q2, $q3, $q4, $q5, $notes ?: null]);

    // Notify adviser if student needs consultation or has problems
    if ($q3 || $q4) {
        $stu = $db->prepare("SELECT first_name, last_name, adviser_id FROM students WHERE id = ?");
        $stu->execute([$studentId]);
        $student = $stu->fetch();

        if ($student && $student['adviser_id']) {
            $alerts = [];
            if ($q3) $alerts[] = 'needs consultation';
            if ($q4) $alerts[] = 'reported company problems';
            $alertStr = implode(' and ', $alerts);
            createNotification(
                'adviser', $student['adviser_id'],
                '⚠️ Student Alert',
                "{$student['first_name']} {$student['last_name']} {$alertStr} in today's well-being check-in.",
                'survey', $studentId, 'student'
            );
        }
    }

    logActivity('student', $studentId, 'Survey Submitted', 'Daily well-being check-in completed.');
    jsonResponse(['success' => true, 'message' => 'Check-in submitted successfully!']);
}
