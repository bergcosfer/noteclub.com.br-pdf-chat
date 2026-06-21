/* chat.js — Chat Gepeto */
'use strict';

// ── Estado ────────────────────────────────────────────────────────────────
let lastId        = 0;
let pingTimer     = null;
let pollTimer     = null;
let micRecorder   = null;
let camStream     = null;
let camRecorder   = null;
let camChunks     = [];
let emojiOpen     = false;
let lastActivity  = Date.now(); // última vez que houve mensagem nova
let activityTimer = null;

const PING_INTERVAL     = 8000;
const POLL_INTERVAL     = 2000;
const INACTIVITY_LIMIT  = 10000; // 10s sem mensagem nova → deslogar

// ── Elementos ─────────────────────────────────────────────────────────────
const $msgs      = document.getElementById('messages');
const $input     = document.getElementById('msg-input');
const $send      = document.getElementById('btn-send');
const $btnEmoji  = document.getElementById('btn-emoji');
const $btnImage  = document.getElementById('btn-image');
const $btnCamera = document.getElementById('btn-camera');
const $btnMic    = document.getElementById('btn-mic');
const $btnAudio  = document.getElementById('btn-audio');
const $fileImage = document.getElementById('file-image');
const $fileAudio = document.getElementById('file-audio');
const $emojiBox  = document.getElementById('emoji-picker-container');
const $emojiGrid = document.getElementById('emoji-grid');
const $camModal  = document.getElementById('camera-modal');
const $camPrev   = document.getElementById('camera-preview');
const $snapCv    = document.getElementById('snap-canvas');
const $btnSnap   = document.getElementById('btn-snap');
const $btnRecS   = document.getElementById('btn-rec-start');
const $btnRecE   = document.getElementById('btn-rec-stop');
const $btnCamX   = document.getElementById('btn-cam-close');
const $online    = document.getElementById('online-status');

// ── Emojis ────────────────────────────────────────────────────────────────
const EMOJIS = [
  '😀','😂','😍','😎','😢','😡','🤔','🥳','😴','🤩',
  '👍','👎','👏','🙏','💪','🤝','✌️','🫶','❤️','💔',
  '🔥','⭐','✅','❌','🎉','🎁','🚀','💡','📸','🎵',
  '😘','😋','🤣','😇','🥺','😏','😤','🤯','😱','🥰'
];

function buildEmojiPicker() {
  EMOJIS.forEach(e => {
    const s = document.createElement('span');
    s.textContent = e;
    s.addEventListener('click', () => {
      $input.value += e;
      $input.focus();
    });
    $emojiGrid.appendChild(s);
  });
}

// ── Formatar data ─────────────────────────────────────────────────────────
function fmtTime(ts) {
  const d = new Date(ts.replace(' ', 'T'));
  return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// ── Renderizar bolha ──────────────────────────────────────────────────────
function renderBubble(msg) {
  const mine = msg.username === CURRENT_USER;
  const div  = document.createElement('div');
  div.className = 'msg-bubble ' + (mine ? 'msg-mine' : 'msg-other');
  div.dataset.id = msg.id;

  let content = '';
  if (!mine) content += `<span class="msg-name">${escHtml(msg.username)}</span>`;

  if (msg.type === 'text') {
    content += escHtml(msg.content);
  } else if (msg.type === 'image') {
    content += `<img src="${escHtml(msg.content)}" loading="lazy"
                     onclick="window.open('${escHtml(msg.content)}','_blank')">`;
  } else if (msg.type === 'video') {
    content += `<video src="${escHtml(msg.content)}" controls playsinline></video>`;
  } else if (msg.type === 'audio') {
    content += `<audio src="${escHtml(msg.content)}" controls></audio>`;
  }

  content += `<span class="msg-time">${fmtTime(msg.created_at)}</span>`;
  div.innerHTML = content;
  $msgs.appendChild(div);
}

function escHtml(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Scroll para o fim ─────────────────────────────────────────────────────
function scrollBottom() {
  $msgs.scrollTop = $msgs.scrollHeight;
}

// ── Polling ───────────────────────────────────────────────────────────────
async function poll() {
  try {
    const r = await fetch(`api.php?action=get&since=${lastId}`);
    if (!r.ok) return;
    const text = await r.text();
    if (!text.trim().startsWith('[')) return;
    const msgs = JSON.parse(text);
    if (msgs.length) {
      msgs.forEach(m => { renderBubble(m); lastId = m.id; });
      scrollBottom();
      lastActivity = Date.now(); // resetar timer ao receber mensagem
    }
  } catch(e) { /* silencioso */ }
}

// ── Ping (mantém sessão + conta online) ───────────────────────────────────
let pingFails = 0;
async function ping() {
  try {
    const r = await fetch('api.php?action=ping');
    if (!r.ok) { pingFails++; if (pingFails >= 3) window.location = 'index.php'; return; }
    const text = await r.text();
    if (!text.trim().startsWith('{')) { pingFails++; if (pingFails >= 3) window.location = 'index.php'; return; }
    pingFails = 0;
    const d = JSON.parse(text);
    if (d.expired) { window.location = 'index.php'; return; }
    $online.textContent = d.online === 1 ? '🟡 Só você' :
                          d.online >= 2  ? '🟢 2 online' : '⚫ offline';
  } catch(e) { pingFails++; if (pingFails >= 3) window.location = 'index.php'; }
}

function startTimers() {
  ping();
  poll();
  pingTimer     = setInterval(ping, PING_INTERVAL);
  pollTimer     = setInterval(poll, POLL_INTERVAL);
  // verifica inatividade a cada segundo
  activityTimer = setInterval(() => {
    if (Date.now() - lastActivity > INACTIVITY_LIMIT) {
      clearInterval(pingTimer);
      clearInterval(pollTimer);
      clearInterval(activityTimer);
      fetch('logout.php').finally(() => { window.location = 'index.php'; });
    }
  }, 1000);
}

// ── Enviar texto ──────────────────────────────────────────────────────────
async function sendText() {
  const text = $input.value.trim();
  if (!text) return;
  $input.value = '';
  autoResize();
  lastActivity = Date.now(); // resetar timer ao enviar
  try {
    const fd = new FormData();
    fd.append('action', 'send');
    fd.append('text', text);
    await fetch('api.php', { method: 'POST', body: fd });
    poll();
  } catch(e) {}
}

// ── Upload genérico ───────────────────────────────────────────────────────
async function uploadFile(file) {
  lastActivity = Date.now();
  const fd = new FormData();
  fd.append('action', 'upload');
  fd.append('file', file);
  try {
    const r = await fetch('api.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) poll();
    else alert('Erro: ' + d.error);
  } catch(e) { alert('Falha no upload'); }
}

// ── Upload de blob com extensão ───────────────────────────────────────────
function uploadBlob(blob, filename) {
  uploadFile(new File([blob], filename, { type: blob.type }));
}

// ── Gravação de áudio (microfone) ─────────────────────────────────────────
async function toggleMic() {
  if (micRecorder && micRecorder.state === 'recording') {
    micRecorder.stop();
    return;
  }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const chunks = [];

    // Prefere webm/opus; fallback para ogg
    const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
      ? 'audio/webm;codecs=opus' : 'audio/ogg;codecs=opus';

    micRecorder = new MediaRecorder(stream, { mimeType });

    // Indicador visual
    const indicator = document.createElement('div');
    indicator.id = 'rec-indicator';
    indicator.innerHTML = '<span class="dot"></span> Gravando áudio... (toque no 🎙️ para parar)';
    $input.parentElement.parentElement.insertBefore(indicator, $input.parentElement);
    $btnMic.classList.add('active');

    micRecorder.ondataavailable = e => chunks.push(e.data);
    micRecorder.onstop = () => {
      stream.getTracks().forEach(t => t.stop());
      indicator.remove();
      $btnMic.classList.remove('active');
      const blob = new Blob(chunks, { type: mimeType });
      const ext  = mimeType.includes('ogg') ? 'ogg' : 'webm';
      uploadBlob(blob, `audio_${Date.now()}.${ext}`);
      micRecorder = null;
    };
    micRecorder.start();
  } catch(e) {
    alert('Microfone não disponível: ' + e.message);
  }
}

// ── Câmera ────────────────────────────────────────────────────────────────
async function openCamera() {
  try {
    camStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    $camPrev.srcObject = camStream;
    $camModal.classList.remove('hidden');
    $btnRecS.disabled = false;
    $btnRecE.disabled = true;
  } catch(e) {
    alert('Câmera não disponível: ' + e.message);
  }
}

function closeCamera() {
  if (camStream) { camStream.getTracks().forEach(t => t.stop()); camStream = null; }
  if (camRecorder && camRecorder.state !== 'inactive') camRecorder.stop();
  $camModal.classList.add('hidden');
  $camPrev.srcObject = null;
}

function snapPhoto() {
  const w = $camPrev.videoWidth  || 640;
  const h = $camPrev.videoHeight || 480;
  $snapCv.width  = w;
  $snapCv.height = h;
  $snapCv.getContext('2d').drawImage($camPrev, 0, 0, w, h);
  $snapCv.toBlob(blob => {
    uploadBlob(blob, `foto_${Date.now()}.jpg`);
    closeCamera();
  }, 'image/jpeg', 0.88);
}

function startCamRec() {
  camChunks = [];
  const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus')
    ? 'video/webm;codecs=vp9,opus' : 'video/webm';
  camRecorder = new MediaRecorder(camStream, { mimeType });
  camRecorder.ondataavailable = e => camChunks.push(e.data);
  camRecorder.onstop = () => {
    const blob = new Blob(camChunks, { type: mimeType });
    uploadBlob(blob, `video_${Date.now()}.webm`);
    closeCamera();
  };
  camRecorder.start();
  $btnRecS.disabled = true;
  $btnRecE.disabled = false;
  $btnSnap.disabled = true;
}

function stopCamRec() { camRecorder.stop(); }

// ── Auto-resize textarea ──────────────────────────────────────────────────
function autoResize() {
  $input.style.height = 'auto';
  $input.style.height = Math.min($input.scrollHeight, 100) + 'px';
}

// ── Eventos ───────────────────────────────────────────────────────────────
$send.addEventListener('click', sendText);
$input.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendText(); }
});
$input.addEventListener('input', autoResize);

$btnEmoji.addEventListener('click', () => {
  emojiOpen = !emojiOpen;
  $emojiBox.classList.toggle('hidden', !emojiOpen);
  if (emojiOpen) $btnEmoji.classList.add('active');
  else           $btnEmoji.classList.remove('active');
});

$btnImage.addEventListener('click', () => $fileImage.click());
$fileImage.addEventListener('change', () => {
  if ($fileImage.files[0]) uploadFile($fileImage.files[0]);
  $fileImage.value = '';
});

$btnAudio.addEventListener('click', () => $fileAudio.click());
$fileAudio.addEventListener('change', () => {
  if ($fileAudio.files[0]) uploadFile($fileAudio.files[0]);
  $fileAudio.value = '';
});

$btnMic.addEventListener('click', toggleMic);
$btnCamera.addEventListener('click', openCamera);
$btnCamX.addEventListener('click', closeCamera);
$btnSnap.addEventListener('click', snapPhoto);
$btnRecS.addEventListener('click', startCamRec);
$btnRecE.addEventListener('click', stopCamRec);

// Fechar emoji ao clicar fora
document.addEventListener('click', e => {
  if (emojiOpen && !$emojiBox.contains(e.target) && e.target !== $btnEmoji) {
    emojiOpen = false;
    $emojiBox.classList.add('hidden');
    $btnEmoji.classList.remove('active');
  }
});

// ── Init ──────────────────────────────────────────────────────────────────
buildEmojiPicker();
startTimers();
