<?php
/**
 * Fonctions de gestion des notifications
 */

/**
 * Crée une nouvelle notification
 * 
 * @param PDO $conn Connexion à la base de données
 * @param string $titre Titre de la notification
 * @param string $message Message de la notification
 * @param string $type Type de notification (info, warning, success, danger)
 * @param string $lien Lien optionnel vers une page
 * @return bool True si la notification a été créée avec succès
 */
function creer_notification($conn, $titre, $message, $type = 'info', $lien = null) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (titre, message, type, lien, date_creation, lu) 
            VALUES (?, ?, ?, ?, NOW(), 0)
        ");
        return $stmt->execute([$titre, $message, $type, $lien]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de la notification : " . $e->getMessage());
        return false;
    }
}

/**
 * Crée une notification pour une nouvelle demande
 * 
 * @param PDO $conn Connexion à la base de données
 * @param string $type_acte Type d'acte (naissance, mariage, deces)
 * @param string $numero_acte Numéro de l'acte
 * @param string $nom Nom de la personne concernée
 * @return bool True si la notification a été créée avec succès
 */
function notifier_nouvelle_demande($conn, $type_acte, $numero_acte, $nom) {
    $titre = "Nouvelle demande d'acte";
    $message = "Une nouvelle demande d'acte de " . $type_acte . " a été soumise pour " . $nom;
    $lien = "voir_demande.php?type=" . $type_acte . "&numero=" . $numero_acte;
    
    return creer_notification($conn, $titre, $message, 'info', $lien);
}

/**
 * Crée une notification pour un changement de statut
 * 
 * @param PDO $conn Connexion à la base de données
 * @param string $type_acte Type d'acte (naissance, mariage, deces)
 * @param string $numero_acte Numéro de l'acte
 * @param string $ancien_statut Ancien statut
 * @param string $nouveau_statut Nouveau statut
 * @return bool True si la notification a été créée avec succès
 */
function notifier_changement_statut($conn, $type_acte, $numero_acte, $ancien_statut, $nouveau_statut) {
    $titre = "Changement de statut";
    $message = "Le statut de la demande d'acte de " . $type_acte . " n°" . $numero_acte . " est passé de " . 
               $ancien_statut . " à " . $nouveau_statut;
    $lien = "voir_demande.php?type=" . $type_acte . "&numero=" . $numero_acte;
    
    return creer_notification($conn, $titre, $message, 'warning', $lien);
}

/**
 * Crée une notification pour un nouveau paiement
 * 
 * @param PDO $conn Connexion à la base de données
 * @param string $type_acte Type d'acte (naissance, mariage, deces)
 * @param string $numero_acte Numéro de l'acte
 * @param float $montant Montant du paiement
 * @param string $mode_paiement Mode de paiement
 * @return bool True si la notification a été créée avec succès
 */
function notifier_paiement($conn, $type_acte, $numero_acte, $montant, $mode_paiement) {
    $titre = "Nouveau paiement reçu";
    $message = "Un paiement de " . number_format($montant, 0, ',', ' ') . " FCFA a été reçu pour l'acte de " . 
               $type_acte . " n°" . $numero_acte . " (" . $mode_paiement . ")";
    $lien = "voir_demande.php?type=" . $type_acte . "&numero=" . $numero_acte;
    
    return creer_notification($conn, $titre, $message, 'success', $lien);
}

/**
 * Récupère le nombre de notifications non lues
 * 
 * @param PDO $conn Connexion à la base de données
 * @return int Nombre de notifications non lues
 */
function get_nombre_notifications_non_lues($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM notifications WHERE lu = 0");
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du nombre de notifications : " . $e->getMessage());
        return 0;
    }
}

/**
 * Récupère les dernières notifications
 * 
 * @param PDO $conn Connexion à la base de données
 * @param int $limite Nombre maximum de notifications à récupérer
 * @return array Tableau des notifications
 */
function get_dernieres_notifications($conn, $limite = 5) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            ORDER BY date_creation DESC 
            LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des notifications : " . $e->getMessage());
        return [];
    }
} 