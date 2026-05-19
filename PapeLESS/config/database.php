<?php
// ============================================================
// PapeLESS - Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'papeless_db');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'PapeLESS');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/PapeLESS');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/submissions/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip']);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Create PDO connection
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed. Please check your configuration.']));
        }
    }
    return $pdo;
}

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path'     => '/',
        'secure'   => false, // set true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
