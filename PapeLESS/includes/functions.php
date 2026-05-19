<?php
// ============================================================
// PapeLESS - Helper Functions
// ============================================================
require_once __DIR__ . '/../config/database.php';

// ── Authentication ──────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['user_type']);
}

function requireLogin(string $redirectTo = '/'): void {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
    // Session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset(); session_destroy();
        header("Location: $redirectTo?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function requireRole(string $role): void {
    requireLogin('/index.php');
    if ($_SESSION['user_type'] !== $role) {
        header('Location: /index.php?error=unauthorized');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $table = match($_SESSION['user_type']) {
        'student'     => 'students',
        'adviser'     => 'advisers',
        'coordinator' => 'coordinators',
        default       => null,
    };
    if (!$table) return null;
    $stmt = $db->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// ── CSRF Protection ─────────────────────────────────────────

function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRF(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRF()) . '">';
}

// ── Notifications ────────────────────────────────────────────

function createNotification(string $userType, int $userId, string $title, string $message, string $type = 'system', ?int $refId = null, ?string $refType = null): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_type, user_id, title, message, type, reference_id, reference_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userType, $userId, $title, $message, $type, $refId, $refType]);
}

function getUnreadNotificationCount(string $userType, int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_type = ? AND user_id = ? AND is_read = 0");
    $stmt->execute([$userType, $userId]);
    return (int)$stmt->fetchColumn();
}

function getNotifications(string $userType, int $userId, int $limit = 10): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_type = ? AND user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userType, $userId, $limit]);
    return $stmt->fetchAll();
}

function markNotificationsRead(string $userType, int $userId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_type = ? AND user_id = ?");
    $stmt->execute([$userType, $userId]);
}

// ── File Upload ──────────────────────────────────────────────

function handleFileUpload(array $file, int $studentId): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed. Error code: ' . $file['error']];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds the 10MB limit.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    // Validate real MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/png','application/zip','application/x-zip-compressed'];
    if (!in_array($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid file type detected.'];
    }
    $dir = UPLOAD_PATH . $studentId . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $dir . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Failed to save file. Please try again.'];
    }
    return [
        'success'       => true,
        'file_name'     => $safeName,
        'original_name' => htmlspecialchars($file['name']),
        'file_type'     => $ext,
        'file_size'     => $file['size'],
        'file_path'     => 'assets/uploads/submissions/' . $studentId . '/' . $safeName,
    ];
}

function formatFileSize(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

function getFileIcon(string $type): string {
    return match(strtolower($type)) {
        'pdf'          => 'bi-file-earmark-pdf-fill text-danger',
        'doc', 'docx'  => 'bi-file-earmark-word-fill text-primary',
        'jpg','jpeg','png' => 'bi-file-earmark-image-fill text-success',
        'zip'          => 'bi-file-earmark-zip-fill text-warning',
        default        => 'bi-file-earmark-fill text-secondary',
    };
}

// ── Activity Log ─────────────────────────────────────────────

function logActivity(string $userType, int $userId, string $action, string $details = ''): void {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare("INSERT INTO activity_logs (user_type, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userType, $userId, $action, $details, $ip]);
}

// ── Utilities ────────────────────────────────────────────────

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatDate(string $date, string $format = 'M d, Y'): string {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

function formatDateTime(string $dt): string {
    if (!$dt) return '—';
    return date('M d, Y h:i A', strtotime($dt));
}

function timeAgo(string $datetime): string {
    $now  = time();
    $time = strtotime($datetime);
    $diff = $now - $time;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d, Y', $time);
}

function statusBadge(string $status): string {
    $map = [
        'pending'       => 'badge bg-warning text-dark',
        'approved'      => 'badge bg-success',
        'rejected'      => 'badge bg-danger',
        'needs_revision'=> 'badge bg-orange',
        'resubmitted'   => 'badge bg-info',
        'completed'     => 'badge bg-primary',
        'active'        => 'badge bg-success',
    ];
    $label = [
        'pending'       => 'Pending',
        'approved'      => 'Approved',
        'rejected'      => 'Rejected',
        'needs_revision'=> 'Needs Revision',
        'resubmitted'   => 'Resubmitted',
        'completed'     => 'Completed',
        'active'        => 'Active',
    ];
    $cls = $map[$status] ?? 'badge bg-secondary';
    $lbl = $label[$status] ?? ucfirst($status);
    return "<span class=\"$cls\">$lbl</span>";
}

function getAdvisserForStudent(int $studentId): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.* FROM advisers a JOIN students s ON s.adviser_id = a.id WHERE s.id = ?");
    $stmt->execute([$studentId]);
    return $stmt->fetch() ?: null;
}

function getSetting(string $key): ?string {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : null;
}

function hasSurveyToday(int $studentId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM surveys WHERE student_id = ? AND survey_date = CURDATE()");
    $stmt->execute([$studentId]);
    return (bool)$stmt->fetch();
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}
