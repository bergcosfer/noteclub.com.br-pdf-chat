<?php
require 'config.php';
requireLogin();

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Heartbeat (usuário online) ─────────────────────────────────────────────
if ($action === 'ping') {
    $ip = userIp();
    db()->prepare("REPLACE INTO users_online (ip, username) VALUES (?, ?)")
         ->execute([$ip, $_SESSION['username']]);
    // limpa inativos (> 10 s) — sessão expira em 10s sem ping
    db()->exec("DELETE FROM users_online WHERE last_seen < DATE_SUB(NOW(), INTERVAL 10 SECOND)");
    $count = db()->query("SELECT COUNT(*) FROM users_online")->fetchColumn();
    echo json_encode(['online' => (int)$count]);
    exit;
}

// ── Buscar mensagens (polling) ─────────────────────────────────────────────
if ($action === 'get') {
    $since = (int)($_GET['since'] ?? 0);
    $rows  = db()->prepare(
        "SELECT id, username, ip, type, content, created_at
         FROM messages WHERE id > ? ORDER BY id ASC LIMIT 50"
    );
    $rows->execute([$since]);
    echo json_encode($rows->fetchAll());
    exit;
}

// ── Enviar mensagem de texto ───────────────────────────────────────────────
if ($action === 'send') {
    $text = trim($_POST['text'] ?? '');
    if ($text === '') { echo json_encode(['ok' => false]); exit; }
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $ip   = userIp();
    db()->prepare("INSERT INTO messages (username, ip, type, content) VALUES (?, ?, 'text', ?)")
         ->execute([$_SESSION['username'], $ip, $text]);
    echo json_encode(['ok' => true, 'id' => db()->lastInsertId()]);
    exit;
}

// ── Upload de mídia ────────────────────────────────────────────────────────
if ($action === 'upload') {
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Upload falhou']); exit;
    }
    if ($file['size'] > MAX_FILE_MB * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'Arquivo muito grande']); exit;
    }

    $mime = mime_content_type($file['tmp_name']);
    if (str_starts_with($mime, 'image/')) {
        $type   = 'image';
        $subdir = 'images/';
    } elseif (str_starts_with($mime, 'video/')) {
        $type   = 'video';
        $subdir = 'videos/';
    } elseif (str_starts_with($mime, 'audio/')) {
        $type   = 'audio';
        $subdir = 'audio/';
    } else {
        echo json_encode(['ok' => false, 'error' => 'Tipo não permitido']); exit;
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
    $filename = uniqid('', true) . '.' . $ext;
    $dest     = UPLOAD_DIR . $subdir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['ok' => false, 'error' => 'Erro ao salvar']); exit;
    }

    $url = UPLOAD_URL . $subdir . $filename;
    $ip  = userIp();
    db()->prepare("INSERT INTO messages (username, ip, type, content) VALUES (?, ?, ?, ?)")
         ->execute([$_SESSION['username'], $ip, $type, $url]);

    echo json_encode(['ok' => true, 'url' => $url, 'type' => $type]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Ação inválida']);
