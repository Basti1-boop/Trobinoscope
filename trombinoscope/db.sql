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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Donnees fictives (profils + publications + commentaires)
-- Mot de passe pour tous les comptes fictifs: "password"
INSERT INTO utilisateurs (prenom, nom, email, password, specialite, promo, bio, avatar) VALUES
  ('Alice', 'Martin', 'alice.martin@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Developpeuse Web', 'BUT1 2024',
   'Passionnee par le developpement front-end et les interfaces accessibles. Je cherche un stage pour juin 2025.',
   'https://api.dicebear.com/7.x/personas/svg?seed=Alice&backgroundColor=b6e3f4'),
  ('Lucas', 'Bernard', 'lucas.bernard@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Designer UI', 'BUT1 2024', '',
   'https://api.dicebear.com/7.x/personas/svg?seed=Lucas&backgroundColor=ffdfbf'),
  ('Sofia', 'Dupont', 'sofia.dupont@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Data Analyst', 'BUT2 2023', '',
   'https://api.dicebear.com/7.x/personas/svg?seed=Sofia&backgroundColor=d1f4d1'),
  ('Karim', 'Ndiaye', 'karim.ndiaye@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DevOps', 'BUT2 2023', '',
   'https://api.dicebear.com/7.x/personas/svg?seed=Karim&backgroundColor=ffd5dc'),
  ('Emma', 'Leroy', 'emma.leroy@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Product Manager', 'BUT3 2022', '',
   'https://api.dicebear.com/7.x/personas/svg?seed=Emma&backgroundColor=e8d5ff'),
  ('Noah', 'Girard', 'noah.girard@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Securite Reseau', 'BUT3 2022', '',
   'https://api.dicebear.com/7.x/personas/svg?seed=Noah&backgroundColor=fff3b0'),
  ('Yasmine', 'Benali', 'yasmine.benali@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Developpeuse Mobile', 'BUT1 2024', '',
   'https://api.dicebear.com/7.x/personas/svg?seed=Yasmine&backgroundColor=c0f0f0'),
  ('Tom', 'Faure', 'tom.faure@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur Sys.', 'BUT2 2023', '',
   'https://api.dicebear.com/7.x/personas/svg?seed=Tom&backgroundColor=ffd5b0')
ON DUPLICATE KEY UPDATE
  prenom = VALUES(prenom),
  nom = VALUES(nom),
  password = VALUES(password),
  specialite = VALUES(specialite),
  promo = VALUES(promo),
  bio = VALUES(bio),
  avatar = VALUES(avatar);

INSERT INTO publications (utilisateur_id, contenu, created_at)
SELECT u.id,
       'Je viens de finir mon projet de BUT1 sur les APIs REST. Si quelqu''un veut un retour croise, n''hesitez pas !',
       '2026-03-23 08:30:00'
FROM utilisateurs u
WHERE u.email = 'alice.martin@example.test'
  AND NOT EXISTS (
    SELECT 1 FROM publications p
    WHERE p.utilisateur_id = u.id
      AND p.contenu = 'Je viens de finir mon projet de BUT1 sur les APIs REST. Si quelqu''un veut un retour croise, n''hesitez pas !'
  );

INSERT INTO publications (utilisateur_id, contenu, created_at)
SELECT u.id,
       'Quelqu''un a des ressources sur Docker pour debutants ? Je commence a m''y mettre serieusement.',
       '2026-03-20 18:15:00'
FROM utilisateurs u
WHERE u.email = 'alice.martin@example.test'
  AND NOT EXISTS (
    SELECT 1 FROM publications p
    WHERE p.utilisateur_id = u.id
      AND p.contenu = 'Quelqu''un a des ressources sur Docker pour debutants ? Je commence a m''y mettre serieusement.'
  );

INSERT INTO commentaires (publication_id, utilisateur_id, contenu, created_at)
SELECT p.id, u.id,
       'Super ! Je suis partant pour un echange de code review.',
       '2026-03-23 09:00:00'
FROM publications p
JOIN utilisateurs a ON a.id = p.utilisateur_id
JOIN utilisateurs u ON u.email = 'lucas.bernard@example.test'
WHERE a.email = 'alice.martin@example.test'
  AND p.contenu = 'Je viens de finir mon projet de BUT1 sur les APIs REST. Si quelqu''un veut un retour croise, n''hesitez pas !'
  AND NOT EXISTS (
    SELECT 1 FROM commentaires c
    WHERE c.publication_id = p.id
      AND c.utilisateur_id = u.id
      AND c.contenu = 'Super ! Je suis partant pour un echange de code review.'
  );

INSERT INTO commentaires (publication_id, utilisateur_id, contenu, created_at)
SELECT p.id, u.id,
       'Moi aussi, tu peux regarder le mien en echange ?',
       '2026-03-23 09:12:00'
FROM publications p
JOIN utilisateurs a ON a.id = p.utilisateur_id
JOIN utilisateurs u ON u.email = 'sofia.dupont@example.test'
WHERE a.email = 'alice.martin@example.test'
  AND p.contenu = 'Je viens de finir mon projet de BUT1 sur les APIs REST. Si quelqu''un veut un retour croise, n''hesitez pas !'
  AND NOT EXISTS (
    SELECT 1 FROM commentaires c
    WHERE c.publication_id = p.id
      AND c.utilisateur_id = u.id
      AND c.contenu = 'Moi aussi, tu peux regarder le mien en echange ?'
  );

INSERT INTO commentaires (publication_id, utilisateur_id, contenu, created_at)
SELECT p.id, u.id,
       'La doc officielle de Docker est vraiment bien faite pour les debutants.',
       '2026-03-20 19:00:00'
FROM publications p
JOIN utilisateurs a ON a.id = p.utilisateur_id
JOIN utilisateurs u ON u.email = 'karim.ndiaye@example.test'
WHERE a.email = 'alice.martin@example.test'
  AND p.contenu = 'Quelqu''un a des ressources sur Docker pour debutants ? Je commence a m''y mettre serieusement.'
  AND NOT EXISTS (
    SELECT 1 FROM commentaires c
    WHERE c.publication_id = p.id
      AND c.utilisateur_id = u.id
      AND c.contenu = 'La doc officielle de Docker est vraiment bien faite pour les debutants.'
  );
