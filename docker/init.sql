-- ============================================
-- Base de données pour le MboaLearn
-- Mini Learning Management System
-- ============================================

CREATE DATABASE IF NOT EXISTS l2_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE l2_lms;

-- ============================================
-- Table des utilisateurs
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('etudiant', 'enseignant', 'promoteur') DEFAULT 'etudiant',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table des modules
-- ============================================
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table des cours
-- ============================================
CREATE TABLE cours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    module_id INT,
    enseignant_id INT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    FOREIGN KEY (enseignant_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Table des lecons
-- ============================================
CREATE TABLE lecons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cours_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    contenu TEXT,
    type_contenu ENUM('pdf', 'video') NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    duree_video INT DEFAULT 0,
    ordre INT DEFAULT 0,
    FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Table des evaluations
-- ============================================
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecon_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    FOREIGN KEY (lecon_id) REFERENCES lecons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Table des questions QCM
-- ============================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    bonne_reponse ENUM('A', 'B', 'C', 'D') NOT NULL,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Table des reponses des etudiants
-- ============================================
CREATE TABLE reponses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    evaluation_id INT NOT NULL,
    question_id INT NOT NULL,
    reponse ENUM('A', 'B', 'C', 'D'),
    date_reponse TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Table des resultats
-- ============================================
CREATE TABLE resultats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    evaluation_id INT NOT NULL,
    note DECIMAL(5,2) NOT NULL,
    nombre_bonnes_reponses INT DEFAULT 0,
    nombre_total_questions INT DEFAULT 0,
    date_passage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Table de progression
-- ============================================
CREATE TABLE progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    lecon_id INT NOT NULL,
    statut ENUM('non_commence', 'en_cours', 'termine') DEFAULT 'non_commence',
    evaluation_reussie BOOLEAN DEFAULT FALSE,
    score_evaluation DECIMAL(5,2) DEFAULT 0,
    date_mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lecon_id) REFERENCES lecons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_etudiant_lecon (etudiant_id, lecon_id)
) ENGINE=InnoDB;

-- ============================================
-- Table des certificats
-- ============================================
CREATE TABLE certificats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    evaluation_id INT NULL,
    module_id INT NULL,
    score_moyen DECIMAL(5,2),
    pourcentage_reussite DECIMAL(5,2),
    date_obtention TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    numero_certificat VARCHAR(50) UNIQUE,
    FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE SET NULL,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Donnees de test
-- ============================================

-- Promoteur par defaut
INSERT INTO users (nom, email, password, role) VALUES
('Admin', 'admin@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'promoteur');

-- Enseignant de test
INSERT INTO users (nom, email, password, role) VALUES
('Dr. Dupont', 'dupont@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant');

-- Etudiant de test
INSERT INTO users (nom, email, password, role) VALUES
('Jean Etudiant', 'jean@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant');

-- Module de test
INSERT INTO modules (nom, description) VALUES
('Developpement Web', 'Apprenez les bases du developpement web avec HTML, CSS et PHP'),
('Bases de Donnees', 'Maitrisez MySQL et la conception de bases de donnees');

-- Cours de test
INSERT INTO cours (titre, description, module_id, enseignant_id) VALUES
('Introduction a HTML5', 'Apprenez les bases du HTML5', 1, 2),
('PHP pour debutants', 'Initiation a la programmation PHP', 1, 2),
('MySQL fondamentaux', 'Les bases de donnees MySQL', 2, 2);

-- Lecons de test
INSERT INTO lecons (cours_id, titre, contenu, type_contenu, fichier, ordre) VALUES
(1, 'Les balises HTML', 'Premiere lecon sur les balises HTML', 'pdf', 'lecon1.pdf', 1),
(1, 'Les formulaires HTML', 'Apprenez a creer des formulaires', 'pdf', 'lecon2.pdf', 2),
(2, 'Syntaxe PHP', 'Les bases de la syntaxe PHP', 'pdf', 'php_syntaxe.pdf', 1),
(3, 'Creer une base de donnees', 'Comment creer votre premiere BDD', 'pdf', 'mysql_creation.pdf', 1);

-- Evaluations de test
INSERT INTO evaluations (lecon_id, titre, description) VALUES
(1, 'QCM Balises HTML', 'Evaluation sur les balises HTML de base'),
(2, 'QCM Formulaires', 'Evaluation sur les formulaires HTML'),
(3, 'QCM Syntaxe PHP', 'Evaluation sur la syntaxe PHP'),
(4, 'QCM MySQL', 'Evaluation sur les bases MySQL');

-- Questions QCM de test
INSERT INTO questions (evaluation_id, question, option_a, option_b, option_c, option_d, bonne_reponse) VALUES
(1, 'Quelle balise utilise-t-on pour un titre principal ?', '<h6>', '<h1>', '<head>', '<title>', 'B'),
(1, 'Quelle balise cree un lien ?', '<link>', '<href>', '<a>', '<url>', 'C'),
(1, 'Quelle balise insere une image ?', '<image>', '<img>', '<picture>', '<src>', 'B'),
(2, 'Quelle balise cree un formulaire ?', '<form>', '<input>', '<field>', '<submit>', 'A'),
(2, 'Quel type d''input pour une case a cocher ?', 'text', 'radio', 'checkbox', 'select', 'C'),
(3, 'Comment on ecrit un commentaire en PHP ?', '//', '#', '/* */', 'Toutes ces reponses', 'D'),
(3, 'Quelle variable contient les donnees d''un formulaire ?', '$_GET', '$_POST', '$_FORM', '$_REQUEST', 'B'),
(4, 'Quelle commande cree une base de donnees ?', 'MAKE DATABASE', 'CREATE DATABASE', 'NEW DATABASE', 'BUILD DATABASE', 'B'),
(4, 'Quelle commande affiche les tables ?', 'SHOW TABLES', 'LIST TABLES', 'GET TABLES', 'DISPLAY TABLES', 'A');
