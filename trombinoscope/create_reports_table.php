<?php
require_once 'config.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT NOT NULL,
        target_type ENUM('publication', 'commentaire') NOT NULL,
        target_id INT NOT NULL,
        reason TEXT,
        status ENUM('pending', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        resolved_at DATETIME NULL,
        resolved_by INT NULL,
        FOREIGN KEY (reporter_id) REFERENCES utilisateurs (id) ON DELETE CASCADE,
        FOREIGN KEY (resolved_by) REFERENCES utilisateurs (id) ON DELETE SET NULL,
        INDEX idx_reporter_id (reporter_id),
        INDEX idx_target (target_type, target_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS moderation_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_type ENUM('publication', 'commentaire') NOT NULL,
        target_id INT NOT NULL,
        publication_id INT NULL,
        author_id INT NOT NULL,
        target_content TEXT NOT NULL,
        target_created_at DATETIME NULL,
        status ENUM('pending', 'kept_deleted', 'restored') NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        handled_at DATETIME NULL,
        handled_by INT NULL,
        UNIQUE KEY uq_moderation_target (target_type, target_id),
        INDEX idx_moderation_status (status),
        INDEX idx_moderation_author (author_id),
        FOREIGN KEY (author_id) REFERENCES utilisateurs (id) ON DELETE CASCADE,
        FOREIGN KEY (handled_by) REFERENCES utilisateurs (id) ON DELETE SET NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Tables 'reports' et 'moderation_queue' creees avec succes !";
} catch (Exception $e) {
    echo "Erreur lors de la creation des tables : " . $e->getMessage();
}
