<?php
require 'config.php';

// Se chegou ao login.php manualmente (GET), destrói sessão ativa
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isLoggedIn()) {
    db()->prepare("DELETE FROM users_online WHERE ip = ?")->execute([userIp()]);
    session_destroy();
    session_start();
}

if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha  = trim($_POST['senha'] ?? '');
    $nome   = trim($_POST['username'] ?? '');
    if ($senha !== CHAT_PASSWORD) {
        $error = 'Senha incorreta.';
    } elseif (strlen($nome) < 2 || strlen($nome) > 20) {
        $error = 'Nome deve ter entre 2 e 20 caracteres.';
    } else {
        $_SESSION['username']      = htmlspecialchars($nome, ENT_QUOTES);
        $_SESSION['authenticated'] = true;
        $_SESSION['ip']            = userIp();
        // registra/atualiza usuário online
        $ip = userIp();
        db()->prepare("REPLACE INTO users_online (ip, username) VALUES (?, ?)")
             ->execute([$ip, $_SESSION['username']]);
        header('Location: chat.php');
        exit;
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chat Gepeto — Entrar</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
<div class="login-box">
  <h1>💬 Chat Gepeto</h1>
  <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="text"     name="username" placeholder="Seu nome" maxlength="20" required autofocus>
    <input type="password" name="senha"    placeholder="Senha de acesso" required>
    <button type="submit">Entrar</button>
  </form>
</div>
</body>
</html>
