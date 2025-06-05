<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citoyen') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération des données du formulaire
        $numero_acte = $_POST['numero_acte'];
        $type_acte = $_POST['type_acte'];
        $motif = $_POST['motif'];
        $nombre_copies = intval($_POST['nombre_copies']);
        $autre_motif = isset($_POST['autre_motif']) ? $_POST['autre_motif'] : '';
        $motif_final = $motif === 'autre' ? $autre_motif : $motif;

        // Calcul du montant
        $tarif_unitaire = 1000; // 1000 FCFA par copie
        $montant = $nombre_copies * $tarif_unitaire;

        // Génération d'un numéro de demande unique
        $annee = date('Y');
        $mois = date('m');
        
        // Récupérer le dernier numéro de demande pour ce mois
        $stmt = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(numero_demande, '-', -1) AS UNSIGNED)) as dernier_numero
            FROM demandes 
            WHERE numero_demande LIKE :prefix
        ");
        $prefix = "DUP-{$annee}{$mois}-";
        $stmt->execute(['prefix' => $prefix . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nouveau_numero = ($result['dernier_numero'] ?? 0) + 1;
        $numero_demande = $prefix . str_pad($nouveau_numero, 4, '0', STR_PAD_LEFT);

        // Insertion de la nouvelle demande
        $stmt = $conn->prepare("
            INSERT INTO demandes (
                utilisateur_id, type_acte, numero_acte, nombre_copies,
                montant, commentaire, statut, date_demande,
                type_demande, motif_duplicata, numero_demande,
                demande_originale_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 'en_attente', NOW(),
                'duplicata', ?, ?, ?
            )
        ");

        // Récupérer l'ID de la demande originale si elle est fournie
        $demande_originale_id = isset($_POST['demande_originale_id']) ? intval($_POST['demande_originale_id']) : null;

        $stmt->execute([
            $_SESSION['user_id'],
            $type_acte,
            $numero_acte,
            $nombre_copies,
            $montant,
            "Demande de duplicata",
            $motif_final,
            $numero_demande,
            $demande_originale_id
        ]);

        $demande_id = $conn->lastInsertId();
        
        // Ajout du message de succès dans la session
        $_SESSION['success'] = "Votre demande de duplicata a été enregistrée avec succès. Numéro de demande : " . $numero_demande;
        
        // Redirection vers la page de paiement avec les paramètres requis
        header("Location: ../paiement.php?numero_acte=" . urlencode($numero_acte) . 
               "&type_acte=" . urlencode($type_acte) . 
               "&nombre_copies=" . urlencode($nombre_copies) . 
               "&demande_id=" . urlencode($demande_id));
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la création de la demande : " . $e->getMessage();
        header("Location: demande_duplicata.php");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
} 