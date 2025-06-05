<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification et des droits d'administration
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès non autorisé");
}

try {
    // Ajout des colonnes pour la gestion des duplicatas
    $conn->exec("ALTER TABLE demandes
        ADD COLUMN IF NOT EXISTS type_demande ENUM('originale', 'duplicata') DEFAULT 'originale',
        ADD COLUMN IF NOT EXISTS motif_duplicata VARCHAR(255) NULL,
        ADD COLUMN IF NOT EXISTS demande_originale_id INT NULL");

    // Ajout de la clé étrangère si elle n'existe pas déjà
    $conn->exec("
        SET @constraint_name = (
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'demandes' 
            AND COLUMN_NAME = 'demande_originale_id' 
            AND REFERENCED_TABLE_NAME = 'demandes'
            AND CONSTRAINT_SCHEMA = DATABASE()
        );
        
        SET @sql = IF(
            @constraint_name IS NULL,
            'ALTER TABLE demandes ADD FOREIGN KEY (demande_originale_id) REFERENCES demandes(id)',
            'SELECT \"La clé étrangère existe déjà\"'
        );
        
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    ");

    // Mise à jour des demandes existantes
    $conn->exec("UPDATE demandes SET type_demande = 'originale' WHERE type_demande IS NULL");

    echo "Base de données mise à jour avec succès !";
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la base de données : " . $e->getMessage());
}
?> 