<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citoyen') {
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'ID de la demande est fourni
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$demande_id = $_GET['id'];

// Récupérer les détails de la demande
$stmt = $conn->prepare("SELECT d.*, u.nom, u.prenoms 
                       FROM demandes d 
                       JOIN utilisateurs u ON d.utilisateur_id = u.id 
                       WHERE d.id = ? AND d.utilisateur_id = ?");
$stmt->execute([$demande_id, $_SESSION['user_id']]);
$demande = $stmt->fetch();

if (!$demande) {
    header('Location: dashboard.php');
    exit();
}

// Récupérer les détails spécifiques selon le type d'acte
$table = 'actes_' . $demande['type_acte'];
$stmt = $conn->prepare("SELECT * FROM $table WHERE numero_acte = ?");
$stmt->execute([$demande['numero_acte']]);
$details = $stmt->fetch();

// Récupérer les informations de paiement
$stmt = $conn->prepare("SELECT * FROM paiements WHERE numero_acte = ? AND type_acte = ?");
$stmt->execute([$demande['numero_acte'], $demande['type_acte']]);
$paiement = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la demande - État Civil CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #FF8C00;">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Espace Citoyen</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Menu</h5>
                        <div class="list-group">
                            <a href="dashboard.php" class="list-group-item list-group-item-action">Tableau de bord</a>
                            <a href="../demande_naissance.php" class="list-group-item list-group-item-action">Demande d'acte de naissance</a>
                            <a href="../demande_mariage.php" class="list-group-item list-group-item-action">Demande d'acte de mariage</a>
                            <a href="../demande_deces.php" class="list-group-item list-group-item-action">Demande d'acte de décès</a>
                            <a href="profile.php" class="list-group-item list-group-item-action">Mon profil</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails de la demande -->
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Détails de la demande</h4>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Retour au tableau de bord</a>
                        </div>

                        <!-- Informations générales -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Informations générales</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Numéro de demande :</strong> <?php echo htmlspecialchars($demande['numero_acte']); ?></p>
                                        <p><strong>Type d'acte :</strong> <?php echo ucfirst($demande['type_acte']); ?></p>
                                        <p><strong>Date de demande :</strong> <?php echo date('d/m/Y', strtotime($demande['date_demande'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Statut :</strong> 
                                            <span class="badge bg-<?php 
                                                echo $demande['statut'] === 'valide' ? 'success' : 
                                                    ($demande['statut'] === 'en_cours' ? 'primary' : 
                                                    ($demande['statut'] === 'en_attente' ? 'warning' : 'secondary')); 
                                            ?>">
                                                <?php 
                                                switch($demande['statut']) {
                                                    case 'valide':
                                                        echo 'Validé';
                                                        break;
                                                    case 'en_cours':
                                                        echo 'En cours de traitement';
                                                        break;
                                                    case 'en_attente':
                                                        echo 'En attente de paiement';
                                                        break;
                                                    default:
                                                        echo ucfirst($demande['statut']);
                                                }
                                                ?>
                                            </span>
                                        </p>
                                        <?php if ($demande['date_traitement']): ?>
                                            <p><strong>Date de traitement :</strong> <?php echo date('d/m/Y', strtotime($demande['date_traitement'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Détails spécifiques -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Détails de l'acte</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($details): ?>
                                    <?php if ($demande['type_acte'] === 'naissance'): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nom :</strong> <?php echo htmlspecialchars($details['nom'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Prénoms :</strong> <?php echo htmlspecialchars($details['prenoms'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Date de naissance :</strong> <?php echo isset($details['date_naissance']) ? date('d/m/Y', strtotime($details['date_naissance'])) : 'Non spécifiée'; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Lieu de naissance :</strong> <?php echo htmlspecialchars($details['lieu_naissance'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Commune :</strong> <?php echo htmlspecialchars($details['commune'] ?? 'Non spécifiée'); ?></p>
                                                <p><strong>Nombre de copies :</strong> <?php echo $details['nombre_copies'] ?? '0'; ?></p>
                                            </div>
                                        </div>
                                    <?php elseif ($demande['type_acte'] === 'mariage'): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nom de l'époux :</strong> <?php echo htmlspecialchars($details['nom_epoux'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Prénoms de l'époux :</strong> <?php echo htmlspecialchars($details['prenoms_epoux'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Nom de l'épouse :</strong> <?php echo htmlspecialchars($details['nom_epouse'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Prénoms de l'épouse :</strong> <?php echo htmlspecialchars($details['prenoms_epouse'] ?? 'Non spécifié'); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Date du mariage :</strong> <?php echo isset($details['date_mariage']) ? date('d/m/Y', strtotime($details['date_mariage'])) : 'Non spécifiée'; ?></p>
                                                <p><strong>Lieu du mariage :</strong> <?php echo htmlspecialchars($details['lieu_mariage'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Nombre de copies :</strong> <?php echo $details['nombre_copies'] ?? '0'; ?></p>
                                            </div>
                                        </div>
                                    <?php elseif ($demande['type_acte'] === 'deces'): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nom :</strong> <?php echo htmlspecialchars($details['nom'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Prénoms :</strong> <?php echo htmlspecialchars($details['prenoms'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Date du décès :</strong> <?php echo isset($details['date_deces']) ? date('d/m/Y', strtotime($details['date_deces'])) : 'Non spécifiée'; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Lieu du décès :</strong> <?php echo htmlspecialchars($details['lieu_deces'] ?? 'Non spécifié'); ?></p>
                                                <p><strong>Cause du décès :</strong> <?php echo htmlspecialchars($details['cause_deces'] ?? 'Non spécifiée'); ?></p>
                                                <p><strong>Nombre de copies :</strong> <?php echo $details['nombre_copies'] ?? '0'; ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        Les détails de l'acte ne sont pas encore disponibles.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Informations de paiement -->
                        <?php if ($paiement): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Informations de paiement</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Numéro de transaction :</strong> <?php echo htmlspecialchars($paiement['numero_transaction']); ?></p>
                                        <p><strong>Montant payé :</strong> <?php echo number_format($paiement['montant'], 0, ',', ' '); ?> FCFA</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Date de paiement :</strong> <?php echo date('d/m/Y', strtotime($paiement['date_paiement'])); ?></p>
                                        <p><strong>Mode de paiement :</strong> <?php echo ucfirst($paiement['mode_paiement']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-body">
                                <?php if ($demande['statut'] === 'en_attente' && !$demande['paiement_effectue']): ?>
                                    <a href="../paiement.php?numero_acte=<?php echo urlencode($demande['numero_acte']); ?>&type_acte=<?php echo urlencode($demande['type_acte']); ?>&nombre_copies=<?php echo urlencode($demande['nombre_copies']); ?>&demande_id=<?php echo urlencode($demande['id']); ?>" 
                                       class="btn btn-primary">
                                        Procéder au paiement
                                    </a>
                                <?php elseif ($demande['statut'] === 'valide'): ?>
                                    <a href="../download_pdf.php?id=<?php echo urlencode($demande['id']); ?>" 
                                       class="btn btn-success" target="_blank">
                                        Télécharger l'acte
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-4 mt-5" style="background-color: #FF8C00; color: white;">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; <?php echo date('Y'); ?> État Civil CI. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 