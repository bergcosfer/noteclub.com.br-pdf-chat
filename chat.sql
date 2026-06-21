-- Banco de dados para Chat Gepeto
-- Executar no phpMyAdmin antes de publicar

CREATE TABLE IF NOT EXISTS `messages` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(30) NOT NULL,
  `ip`         VARCHAR(45) NOT NULL,
  `type`       ENUM('text','image','video','audio') NOT NULL DEFAULT 'text',
  `content`    TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users_online` (
  `ip`         VARCHAR(45) PRIMARY KEY,
  `username`   VARCHAR(30) NOT NULL,
  `last_seen`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
