-- Active: 1771419239767@@127.0.0.1@3306@trombinoscope
CREATE DATABASE IF NOT EXISTS trombinoscope CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE trombinoscope;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    specialite VARCHAR(150),
    promo VARCHAR(50),
    bio TEXT,
    avatar VARCHAR(255) DEFAULT 'default.svg',
    is_admin TINYINT(1) DEFAULT 0,
    banned_at DATETIME NULL,
    ban_reason VARCHAR(255) NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_is_admin (is_admin),
    INDEX idx_banned_at (banned_at),
    INDEX idx_last_login_ip (last_login_ip)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE,
    INDEX idx_utilisateur_id (utilisateur_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications (id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE,
    INDEX idx_publication_id (publication_id),
    INDEX idx_utilisateur_id (utilisateur_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE publication_reactions (
    publication_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    reaction ENUM('like', 'dislike') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (publication_id, utilisateur_id),
    FOREIGN KEY (publication_id) REFERENCES publications (id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE comment_reactions (
    comment_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    reaction ENUM('like', 'dislike') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (comment_id, utilisateur_id),
    FOREIGN KEY (comment_id) REFERENCES commentaires (id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs (id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE tentatives_connexion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_created (ip, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE ip_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    blocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    released_at DATETIME NULL,
    reason VARCHAR(255) NULL,
    INDEX idx_ip_blocked (ip, released_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE ip_unblock_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_unblock_ip (ip),
    FOREIGN KEY (user_id) REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE ip_unblock_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    handled_at DATETIME NULL,
    INDEX idx_ip_unblock_status (status),
    INDEX idx_ip_unblock_ip (ip),
    FOREIGN KEY (user_id) REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES utilisateurs (id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO utilisateurs (prenom, nom, email, password, promo, specialite, bio, avatar, is_admin)
SELECT
    'Admin',
    'Systeme',
    'admin@exemple.fr',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'Administrateur',
    'Compte administrateur du systeme',
    'default.svg',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM utilisateurs WHERE email = 'admin@exemple.fr'
);

UPDATE utilisateurs
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    is_admin = 1
WHERE email = 'admin@exemple.fr';

ALTER TABLE utilisateurs
  ADD COLUMN banned_at DATETIME NULL,
  ADD COLUMN ban_reason VARCHAR(255) NULL;

ALTER TABLE utilisateurs
  ADD COLUMN last_login_ip VARCHAR(45) NULL;




