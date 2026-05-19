<?php
// ============================================================
// PapeLESS - Student Submission Handler (AJAX)
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'upload': handleUpload(); break;
    default: jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
}

function handleUpload(): void {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
    }

    $studentId = $_SESSION['user_id'];
    $reqId     = (int)($_POST['requirement_id'] ?? 0);

    if (!$reqId) {
        jsonResponse(['success' => false, 'error' => 'Invalid requirement.']);
    }

    $db = getDB();

    // Verify requirement exists
    $req = $db->prepare("SELECT * FROM submission_requirements WHERE id = ? AND is_active = 1");
    $req->execute([$reqId]);
    if (!$req->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Requirement not found.']);
    }

    // Check if already approved (cannot resubmit approved docs)
    $existing = $db->prepare("SELECT status FROM submissions WHERE student_id = ? AND requirement_id = ? ORDER BY id DESC LIMIT 1");
    $existing->execute([$studentId, $reqId]);
    $prev = $existing->fetch();
    if ($prev && $prev['status'] === 'approved') {
        jsonResponse(['success' => false, 'error' => 'This document is already approved and cannot be resubmitted.']);
    }

    if (empty($_FILES['submission_file'])) {
        jsonResponse(['success' => false, 'error' => 'No file received.']);
    }

    $result = handleFileUpload($_FILES['submission_file'], $studentId);
    if (!$result['success']) {
        jsonResponse(['success' => false, 'error' => $result['error']]);
    }

    // Determine status label
    $status = ($prev && $prev['status'] === 'needs_revision') ? 'resubmitted' : 'pending';

    $stmt = $db->prepare("
        INSERT INTO submissions (student_id, requirement_id, file_name, original_name, file_type, file_size, file_path, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $studentId, $reqId,
        $result['file_name'], $result['original_name'],
        $result['file_type'], $result['file_size'],
        $result['file_path'], $status
    ]);

    $subId = (int)$db->lastInsertId();

    // Get student info to notify adviser
    $stu = $db->prepare("SELECT s.*, r.requirement_name FROM students s JOIN submission_requirements r ON r.id=? WHERE s.id=?");
    $stu->execute([$reqId, $studentId]);
    $student = $stu->fetch();

    if ($student && $student['adviser_id']) {
        $notifMsg = ($status === 'resubmitted')
            ? "{$student['first_name']} {$student['last_name']} resubmitted: {$student['requirement_name']}"
            : "{$student['first_name']} {$student['last_name']} uploaded: {$student['requirement_name']}";
        createNotification('adviser', $student['adviser_id'], 'New Submission', $notifMsg, 'submission', $subId, 'submission');
    }

    logActivity('student', $studentId, 'File Upload', "Uploaded: {$result['original_name']} for requirement ID $reqId");

    jsonResponse(['success' => true, 'message' => 'File uploaded successfully! Awaiting adviser review.']);
}
