<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Utilisateur par défaut de WAMP
define('DB_PASS', ''); // Mot de passe par défaut de WAMP
define('DB_NAME', 'etat_civil_ci');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
} catch(PDOException $e) {
    // Log l'erreur dans un fichier au lieu de l'afficher
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données.");
}
?> 