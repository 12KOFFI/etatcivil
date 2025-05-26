<?php
session_start();
require_once 'includes/generate_pdf.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si les paramètres nécessaires sont présents
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$type = $_GET['type'];
$demande_id = $_GET['id'];

// Vérifier si le type est valide
if (!in_array($type, ['naissance', 'mariage', 'deces'])) {
    header('Location: index.php');
    exit();
}

// Générer le PDF
$filename = generateActePDF($type, $demande_id);

if ($filename) {
    // Télécharger le PDF
    downloadPDF($filename);
} else {
    // Rediriger vers la page d'erreur
    header('Location: error.php?message=Impossible de générer le PDF');
    exit();
}
?> 