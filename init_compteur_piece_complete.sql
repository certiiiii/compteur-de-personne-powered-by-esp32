CREATE DATABASE IF NOT EXISTS `compteur_piece`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `compteur_piece`;

DROP TABLE IF EXISTS `historique`;
DROP TABLE IF EXISTS `esp`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `presence`;

CREATE TABLE `presence` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `presence-name` VARCHAR(100) NOT NULL,
  `nb_personnes` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `esp` (
  `esp_id` VARCHAR(17) NOT NULL,
  `presence_id` INT NOT NULL,
  PRIMARY KEY (`esp_id`),
  KEY `idx_esp_presence_id` (`presence_id`),
  CONSTRAINT `fk_esp_presence`
    FOREIGN KEY (`presence_id`) REFERENCES `presence` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `historique` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `action` ENUM('plus','moins') NOT NULL,
  `valeur` INT NOT NULL,
  `date_action` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `esp_id` VARCHAR(17) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_historique_date` (`date_action`),
  KEY `idx_historique_esp_id` (`esp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `presence` (`presence-name`, `nb_personnes`) VALUES
('Salle 1', 0);

-- Compte admin par défaut
-- identifiant : admin
-- mot de passe : admin1234
INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$12$HF9YjHGg9RKiXAluCodZHuUhPoy2sxjz8PDinRRuUzlGk8Y9wxTdO');
