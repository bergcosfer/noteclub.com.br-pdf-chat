<?php
require 'config.php';
requireLogin();
$username = $_SESSION['username'];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Chat Gepeto</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header id="chat-header">
  <span>💬 Chat Gepeto</span>
  <div id="online-status"></div>
  <a href="logout.php" class="btn-logout">Sair</a>
</header>

<div id="messages"></div>

<div id="emoji-picker-container" class="hidden">
  <div id="emoji-grid"></div>
</div>

<footer id="chat-footer">
  <div id="toolbar">
    <button id="btn-emoji"  title="Emoji">😊</button>
    <button id="btn-image"  title="Imagem/Vídeo">📎</button>
    <button id="btn-camera" title="Câmera">📷</button>
    <button id="btn-mic"    title="Áudio">🎙️</button>
    <button id="btn-audio"  title="MP3">🎵</button>
  </div>
  <div id="input-row">
    <textarea id="msg-input" placeholder="Digite uma mensagem..." rows="1"></textarea>
    <button id="btn-send">➤</button>
  </div>
  <!-- inputs ocultos -->
  <input type="file" id="file-image" accept="image/*,video/*" hidden>
  <input type="file" id="file-audio" accept="audio/*"         hidden>
</footer>

<!-- Modal câmera/gravação de vídeo -->
<div id="camera-modal" class="modal hidden">
  <div class="modal-inner">
    <video id="camera-preview" autoplay playsinline muted></video>
    <div class="modal-controls">
      <button id="btn-snap">📸 Foto</button>
      <button id="btn-rec-start">⏺ Gravar</button>
      <button id="btn-rec-stop" disabled>⏹ Parar</button>
      <button id="btn-cam-close">✕</button>
    </div>
    <canvas id="snap-canvas" hidden></canvas>
  </div>
</div>

<script>
  const CURRENT_USER = <?= json_encode($username) ?>;
</script>
<script src="chat.js"></script>
</body>
</html>
