<?php
// notify.php — roda via cron job, envia email se houver mensagem nova
// Cron sugerido: */1 * * * * (a cada 1 minuto)

require dirname(__FILE__) . '/config.php';

define('NOTIFY_EMAIL', 'SEU_EMAIL_AQUI');
define('LAST_ID_FILE', dirname(__FILE__) . '/last_notified_id.txt');

$lastId = (int)(file_exists(LAST_ID_FILE) ? file_get_contents(LAST_ID_FILE) : 0);

try {
    $st = db()->prepare(
        "SELECT id, username, type, content, created_at
         FROM messages WHERE id > ? ORDER BY id ASC LIMIT 20"
    );
    $st->execute([$lastId]);
    $msgs = $st->fetchAll();
} catch (Exception $e) {
    exit(1);
}

if (empty($msgs)) exit(0);

// monta corpo do email
$lines = [];
foreach ($msgs as $m) {
    $time = date('d/m H:i', strtotime($m['created_at']));
    $body = $m['type'] === 'text' ? $m['content'] : "[{$m['type']}]";
    $lines[] = "[{$time}] {$m['username']}: {$body}";
}

$total   = count($msgs);
$subject = "💬 Chat Gepeto — {$total} mensagem(ns) nova(s)";
$message = implode("\n", $lines) . "\n\nVer chat: https://pdf.noteclub.com.br/login.php";
$headers = "From: noreply@noteclub.com.br\r\nContent-Type: text/plain; charset=UTF-8";

mail(NOTIFY_EMAIL, $subject, $message, $headers);

// salva último id notificado
file_put_contents(LAST_ID_FILE, end($msgs)['id']);
