<?php
require 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['expired' => true]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {

// ── Ping ──────────────────────────────────────────────────────────────────
if ($action === 'ping') {
    $ip = userIp();
    db()->prepare("REPLACE INTO users_online (ip, username) VALUES (?, ?)")
         ->execute([$ip, $_SESSION['username']]);
    db()->exec("DELETE FROM users_online WHERE last_seen < DATE_SUB(NOW(), INTERVAL 10 SECOND)");
    $count = db()->query("SELECT COUNT(*) FROM users_online")->fetchColumn();
    echo json_encode(['online' => (int)$count]);
    exit;
}

// ── Get mensagens ─────────────────────────────────────────────────────────
if ($action === 'get') {
    $since = (int)($_GET['since'] ?? 0);
    $st = db()->prepare("SELECT id, username, ip, type, content, created_at FROM messages WHERE id > ? ORDER BY id ASC LIMIT 50");
    $st->execute([$since]);
    echo json_encode($st->fetchAll());
    exit;
}

// ── Enviar texto ──────────────────────────────────────────────────────────
if ($action === 'send') {
    $text = trim($_POST['text'] ?? '');
    if ($text === '') { echo json_encode(['ok' => false]); exit; }
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    db()->prepare("INSERT INTO messages (username, ip, type, content) VALUES (?, ?, 'text', ?)")
         ->execute([$_SESSION['username'], userIp(), $text]);
    echo json_encode(['ok' => true, 'id' => (int)db()->lastInsertId()]);
    exit;
}

// ── Upload ────────────────────────────────────────────────────────────────
if ($action === 'upload') {
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Upload falhou']); exit;
    }
    if ($file['size'] > MAX_FILE_MB * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'Arquivo muito grande']); exit;
    }

    $mime = mime_content_type($file['tmp_name']);
    // compatível PHP 7 — sem str_starts_with
    if (strpos($mime, 'image/') === 0)      { $type = 'image'; $subdir = 'images/'; }
    elseif (strpos($mime, 'video/') === 0)  { $type = 'video'; $subdir = 'videos/'; }
    elseif (strpos($mime, 'audio/') === 0)  { $type = 'audio'; $subdir = 'audio/';  }
    else { echo json_encode(['ok' => false, 'error' => 'Tipo não permitido']); exit; }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
    $filename = uniqid('', true) . '.' . $ext;
    $dest     = UPLOAD_DIR . $subdir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['ok' => false, 'error' => 'Erro ao salvar']); exit;
    }

    $url = UPLOAD_URL . $subdir . $filename;
    db()->prepare("INSERT INTO messages (username, ip, type, content) VALUES (?, ?, ?, ?)")
         ->execute([$_SESSION['username'], userIp(), $type, $url]);
    echo json_encode(['ok' => true, 'url' => $url, 'type' => $type]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Ação inválida']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
