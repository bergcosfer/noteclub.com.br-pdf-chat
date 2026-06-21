<?php
// ===== CONFIGURE AQUI antes de publicar =====
define('DB_HOST', 'localhost');
define('DB_USER', 'SEU_USUARIO_MYSQL');
define('DB_PASS', 'SUA_SENHA_MYSQL');
define('DB_NAME', 'SEU_BANCO_MYSQL');
// ============================================

define('CHAT_PASSWORD', 'gepeto');
define('MAX_FILE_MB', 50);
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

if (session_status() === PHP_SESSION_NONE) session_start();

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function userIp(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function isLoggedIn(): bool {
    return !empty($_SESSION['username']) && !empty($_SESSION['authenticated']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
