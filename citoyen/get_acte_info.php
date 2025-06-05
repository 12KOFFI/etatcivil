<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citoyen') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

header('Content-Type: application/json');

$numero_acte = isset($_GET['numero_acte']) ? $_GET['numero_acte'] : '';
$response = ['success' => false];

if (!empty($numero_acte)) {
    // Vérifier dans les différentes tables d'actes
    $tables = [
        'naissance' => 'actes_naissance',
        'mariage' => 'actes_mariage',
        'deces' => 'actes_deces'
    ];

    foreach ($tables as $type => $table) {
        $stmt = $conn->prepare("SELECT 1 FROM $table WHERE numero_acte = ?");
        $stmt->execute([$numero_acte]);
        
        if ($stmt->fetchColumn()) {
            $response = [
                'success' => true,
                'type_acte' => $type
            ];
            break;
        }
    }
}

echo json_encode($response); 