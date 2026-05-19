<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// ============================================================
// PapeLESS - Authentication Handler
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':    handleLogin();   break;
    case 'signup':   handleSignup();  break;
    case 'logout':   handleLogout();  break;
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
}

// ── Login ────────────────────────────────────────────────────
function handleLogin(): void {
//    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
//        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
//    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'student';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'error' => 'Email and password are required.']);
    }

    $db = getDB();
    $table = match($role) {
        'student'     => 'students',
        'adviser'     => 'advisers',
        'coordinator' => 'coordinators',
        default       => null,
    };

    if (!$table) {
        jsonResponse(['success' => false, 'error' => 'Invalid role.']);
    }
            if ($role === 'student') {

                $stmt = $db->prepare("SELECT * FROM students WHERE email = ?");

            } elseif ($role === 'adviser') {

                $stmt = $db->prepare("SELECT * FROM advisers WHERE email = ? AND is_active = 1");

            } else {

                $stmt = $db->prepare("SELECT * FROM coordinators WHERE email = ? AND is_active = 1");

            }

            $stmt->execute([$email]);
            $user = $stmt->fetch();

    if (!$user || !verifyPassword($password, $user['password'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid email or password.']);
    }

    // Check student status
    if ($role === 'student' && $user['status'] !== 'approved') {
        $msg = match($user['status']) {
            'pending'   => 'Your account is pending approval by your adviser.',
            'rejected'  => 'Your account has been rejected. Reason: ' . ($user['rejection_reason'] ?? 'Not specified'),
            'completed' => 'Your internship is marked as completed.',
            default     => 'Account not accessible.',
        };
        jsonResponse(['success' => false, 'error' => $msg]);
    }

    // Set session
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_type']     = $role;
    $_SESSION['user_name']     = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['last_activity'] = time();

    if ($role === 'student') {
        $_SESSION['adviser_id'] = $user['adviser_id'];
        $_SESSION['program']    = $user['program'];
        $_SESSION['year_level'] = $user['year_level'];
    }

    logActivity($role, $user['id'], 'Login', 'User logged in successfully.');

    $redirect = match($role) {
        'student'     => BASE_URL . '/student/dashboard.php',
        'adviser'     => BASE_URL . '/adviser/dashboard.php',
        'coordinator' => BASE_URL . '/coordinator/dashboard.php',
    };

    jsonResponse(['success' => true, 'redirect' => $redirect]);
}

// ── Signup ───────────────────────────────────────────────────
function handleSignup(): void {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
    }

    // Required fields
    $required = ['student_number','first_name','last_name','birthdate','email','password','confirm_password','program','year_level','section','company_name','company_address','department_role','internship_start','time_in','time_out'];
    foreach ($required as $f) {
        if (empty($_POST[$f])) {
            jsonResponse(['success' => false, 'error' => "Field '$f' is required."]);
        }
    }

    $db = getDB();

    // Extract & sanitize
    $studentNo  = sanitize($_POST['student_number']);
    $firstName  = sanitize($_POST['first_name']);
    $middleName = sanitize($_POST['middle_name'] ?? '');
    $lastName   = sanitize($_POST['last_name']);
    $birthdate  = $_POST['birthdate'];
    $email      = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];
    $program    = sanitize($_POST['program']);
    $yearLevel  = (int)$_POST['year_level'];
    $section    = (int)$_POST['section'];
    $company    = sanitize($_POST['company_name']);
    $address    = sanitize($_POST['company_address']);
    $role       = sanitize($_POST['department_role']);
    $startDate  = $_POST['internship_start'];
    $timeIn     = $_POST['time_in'];
    $timeOut    = $_POST['time_out'];

    // Validations
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'error' => 'Invalid email address.']);
    }
    if (strlen($password) < 8) {
        jsonResponse(['success' => false, 'error' => 'Password must be at least 8 characters.']);
    }
    if ($password !== $confirm) {
        jsonResponse(['success' => false, 'error' => 'Passwords do not match.']);
    }
    if (!in_array($yearLevel, [1, 2, 3, 4])) {
        jsonResponse(['success' => false, 'error' => 'Invalid year level.']);
    }

    // Check duplicates
    $chk = $db->prepare("SELECT id FROM students WHERE email = ? OR student_number = ?");
    $chk->execute([$email, $studentNo]);
    if ($chk->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Email or student number already registered.']);
    }

    // Find matching adviser
    $adv = $db->prepare("SELECT id FROM advisers WHERE program = ? AND year_level = ? AND is_active = 1 LIMIT 1");
    $adv->execute([$program, $yearLevel]);
    $adviser = $adv->fetch();
    $adviserId = $adviser ? $adviser['id'] : null;

    // Insert student
    $stmt = $db->prepare("
        INSERT INTO students (student_number, first_name, middle_name, last_name, birthdate, email, password, program, year_level, section, company_name, company_address, department_role, internship_start, time_in, time_out, adviser_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$studentNo, $firstName, $middleName ?: null, $lastName, $birthdate, $email, hashPassword($password), $program, $yearLevel, $section, $company, $address, $role, $startDate, $timeIn, $timeOut, $adviserId]);

    $studentId = (int)$db->lastInsertId();

    // Notify adviser if assigned
    if ($adviserId) {
        createNotification('adviser', $adviserId, 'New Student Registration', "$firstName $lastName has registered and is awaiting your approval.", 'system', $studentId, 'student');
    }

    logActivity('student', $studentId, 'Registration', 'Student registered, awaiting approval.');
    jsonResponse(['success' => true, 'message' => 'Registration submitted! Please wait for your adviser to approve your account.']);
}

// ── Logout ───────────────────────────────────────────────────
function handleLogout(): void {
    if (isLoggedIn()) {
        logActivity($_SESSION['user_type'], $_SESSION['user_id'], 'Logout', 'User logged out.');
    }
    session_unset();
    session_destroy();
    jsonResponse(['success' => true, 'redirect' => BASE_URL . '/index.php']);
}
