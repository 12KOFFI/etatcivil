-- Création de la base de données
CREATE DATABASE IF NOT EXISTS etat_civil_ci
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE etat_civil_ci;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenoms VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telephone VARCHAR(20),
    cni VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'agent', 'citoyen') DEFAULT 'citoyen',
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME,
    statut ENUM('actif', 'inactif', 'bloque') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_cni (cni),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des actes de naissance
CREATE TABLE IF NOT EXISTS actes_naissance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acte VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenoms VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    lieu_naissance VARCHAR(100) NOT NULL,
    nom_pere VARCHAR(100) NOT NULL,
    nom_mere VARCHAR(100) NOT NULL,
    commune VARCHAR(100) NOT NULL,
    declarant VARCHAR(100) NOT NULL,
    lien_declarant VARCHAR(50) NOT NULL,
    nombre_copies INT NOT NULL DEFAULT 2,
    date_etablissement DATETIME NOT NULL,
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
    utilisateur_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_numero_acte (numero_acte),
    INDEX idx_date_naissance (date_naissance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des actes de mariage
CREATE TABLE IF NOT EXISTS actes_mariage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acte VARCHAR(50) NOT NULL UNIQUE,
    nom_epoux VARCHAR(100) NOT NULL,
    prenoms_epoux VARCHAR(100) NOT NULL,
    date_naissance_epoux DATE NOT NULL,
    lieu_naissance_epoux VARCHAR(100) NOT NULL,
    nom_epouse VARCHAR(100) NOT NULL,
    prenoms_epouse VARCHAR(100) NOT NULL,
    date_naissance_epouse DATE NOT NULL,
    lieu_naissance_epouse VARCHAR(100) NOT NULL,
    date_mariage DATE NOT NULL,
    lieu_mariage VARCHAR(100) NOT NULL,
    commune VARCHAR(100) NOT NULL,
    nombre_copies INT NOT NULL DEFAULT 2,
    date_etablissement DATETIME NOT NULL,
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
    utilisateur_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_numero_acte (numero_acte),
    INDEX idx_date_mariage (date_mariage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des actes de décès
CREATE TABLE IF NOT EXISTS actes_deces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acte VARCHAR(50) NOT NULL UNIQUE,
    nom_defunt VARCHAR(100) NOT NULL,
    prenoms_defunt VARCHAR(100) NOT NULL,
    date_naissance_defunt DATE NOT NULL,
    lieu_naissance_defunt VARCHAR(100) NOT NULL,
    date_deces DATE NOT NULL,
    lieu_deces VARCHAR(100) NOT NULL,
    cause_deces VARCHAR(255),
    declarant VARCHAR(100) NOT NULL,
    lien_declarant VARCHAR(50) NOT NULL,
    commune VARCHAR(100) NOT NULL,
    nombre_copies INT NOT NULL DEFAULT 2,
    date_etablissement DATETIME NOT NULL,
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
    utilisateur_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_numero_acte (numero_acte),
    INDEX idx_date_deces (date_deces)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paiements
CREATE TABLE IF NOT EXISTS paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_transaction VARCHAR(50) NOT NULL UNIQUE,
    montant DECIMAL(10,2) NOT NULL,
    type_acte ENUM('naissance', 'mariage', 'deces') NOT NULL,
    numero_acte VARCHAR(50) NOT NULL,
    date_paiement DATETIME NOT NULL,
    statut ENUM('en_attente', 'valide', 'annule') DEFAULT 'en_attente',
    mode_paiement VARCHAR(50) NOT NULL,
    utilisateur_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_numero_transaction (numero_transaction),
    INDEX idx_date_paiement (date_paiement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des demandes
CREATE TABLE IF NOT EXISTS demandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_demande VARCHAR(50) NOT NULL UNIQUE,
    type_acte ENUM('naissance', 'mariage', 'deces') NOT NULL,
    numero_acte VARCHAR(50) NOT NULL,
    date_demande DATETIME NOT NULL,
    date_traitement DATETIME,
    statut ENUM('en_attente', 'en_cours', 'valide', 'rejete', 'annule') DEFAULT 'en_attente',
    motif_rejet TEXT,
    commentaire TEXT,
    nombre_copies INT NOT NULL DEFAULT 2,
    montant DECIMAL(10,2) NOT NULL,
    paiement_effectue TINYINT(1) DEFAULT 0,
    utilisateur_id INT,
    agent_traitant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (agent_traitant_id) REFERENCES utilisateurs(id),
    INDEX idx_numero_demande (numero_demande),
    INDEX idx_type_acte (type_acte),
    INDEX idx_date_demande (date_demande),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    lien VARCHAR(255),
    date_creation DATETIME NOT NULL,
    lu TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_date_creation (date_creation),
    INDEX idx_lu (lu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion d'un administrateur par défaut
INSERT INTO utilisateurs (nom, prenoms, email, mot_de_passe, role, date_inscription)
VALUES ('Admin', 'System', 'admin@etatcivil.ci', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()); 