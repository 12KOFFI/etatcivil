<?php
// Connexion à MySQL sans sélectionner de base de données
try {
    $conn = new PDO("mysql:host=localhost", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Création de la base de données si elle n'existe pas
    $sql = "CREATE DATABASE IF NOT EXISTS etat_civil_ci CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    
    // Sélection de la base de données
    $conn->exec("USE etat_civil_ci");
    
    // Lecture et exécution du fichier SQL
    $sql = file_get_contents('../sql/etat_civil_ci.sql');
    $conn->exec($sql);
    
    echo "Base de données initialisée avec succès !";
} catch(PDOException $e) {
    die("Erreur d'initialisation : " . $e->getMessage());
}
?> 