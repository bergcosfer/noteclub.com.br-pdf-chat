# Chat Gepeto — Instruções de Publicação (Hostgator)

## Arquivos do projeto

```
chat-gepeto/
├── index.php          ← Página "isca" (dieta cetogênica) — entrada pública
├── login.php          ← Autenticação (senha: gepeto)
├── chat.php           ← Interface do chat (requer login)
├── logout.php         ← Encerra sessão
├── api.php            ← Backend (polling, envio, upload)
├── config.php         ← Configurações de banco (EDITE ANTES)
├── style.css          ← Estilos
├── chat.js            ← Frontend (polling, gravação, etc.)
├── chat.sql           ← SQL para criar as tabelas
├── .htaccess          ← Segurança e limites de upload
└── uploads/
    ├── .htaccess      ← Bloqueia PHP na pasta de uploads
    ├── images/
    ├── videos/
    └── audio/
```

---

## Passo a passo

### 1. Criar banco de dados no Hostgator

1. Acesse o **cPanel** → MySQL Databases
2. Crie um banco de dados (ex: `meusite_chat`)
3. Crie um usuário MySQL com senha forte
4. Adicione o usuário ao banco com **All Privileges**
5. Abra o **phpMyAdmin**, selecione o banco e cole o conteúdo de `chat.sql`

### 2. Editar config.php

Abra `config.php` e preencha:

```php
define('DB_HOST', 'localhost');        // geralmente "localhost" no Hostgator
define('DB_USER', 'meusite_usuario');  // usuário MySQL criado
define('DB_PASS', 'senha_forte_aqui'); // senha do usuário MySQL
define('DB_NAME', 'meusite_chat');     // nome do banco
```

### 3. Upload via FTP / Gerenciador de Arquivos

- Faça upload de **todos os arquivos** para a pasta `public_html/chat/`
  (ou a pasta que quiser — ex: `public_html/gepeto/`)
- Mantenha a estrutura de pastas, incluindo `uploads/images/`, `uploads/videos/`, `uploads/audio/`

### 4. Permissões de pasta (IMPORTANTE)

Via **Gerenciador de Arquivos do cPanel** ou FTP, defina:

| Pasta/Arquivo    | Permissão |
|------------------|-----------|
| `uploads/`       | **755**   |
| `uploads/images/`| **755**   |
| `uploads/videos/`| **755**   |
| `uploads/audio/` | **755**   |
| `config.php`     | **600**   |

### 5. Testar

1. Acesse `https://seusite.com/chat/` — verá a página sobre dieta cetogênica
2. Clique em **"Termos de Uso"** no rodapé → redireciona para `login.php`
3. Digite qualquer nome e a senha **gepeto**
4. O chat abre em `chat.php`

---

## Como funciona a sessão

- O chat faz um **ping** ao servidor a cada **8 segundos**
- Se nenhum dos dois usuários der ping por **10 segundos**, a sessão é considerada encerrada
- Ao reabrir o navegador ou ficar inativo, o login é solicitado novamente

## Funcionalidades

| Recurso               | Como usar                            |
|-----------------------|--------------------------------------|
| Texto                 | Digite e pressione Enter ou ➤        |
| Emoji                 | Botão 😊                             |
| Imagem / Vídeo        | Botão 📎 → selecionar arquivo        |
| Foto pela câmera      | Botão 📷 → 📸 Foto                   |
| Vídeo pela câmera     | Botão 📷 → ⏺ Gravar                 |
| Áudio do microfone    | Botão 🎙️ → toca/para gravação       |
| Arquivo de áudio MP3  | Botão 🎵 → selecionar arquivo        |

## Limite de upload

Padrão: **50 MB** por arquivo (ajustável em `config.php` → `MAX_FILE_MB` e `.htaccess`)

---

## Segurança

- Sessão PHP garante que apenas usuários autenticados acessem `chat.php` e `api.php`
- A pasta `uploads/` bloqueia execução de PHP via `.htaccess` próprio
- `config.php` com permissão 600 não é servido diretamente pelo Apache
- O IP do usuário identifica qual "lado" da conversa é seu
