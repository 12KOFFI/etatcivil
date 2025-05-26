<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citoyen') {
    header('Location: ../login.php');
    exit();
}

// Récupération des informations de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupération des demandes de l'utilisateur
$stmt = $conn->prepare("SELECT d.*, 
    CASE 
        WHEN d.type_acte = 'naissance' THEN an.nom
        WHEN d.type_acte = 'mariage' THEN am.nom_epoux
        WHEN d.type_acte = 'deces' THEN ad.nom_defunt
    END as nom,
    CASE 
        WHEN d.type_acte = 'naissance' THEN an.prenoms
        WHEN d.type_acte = 'mariage' THEN am.prenoms_epoux
        WHEN d.type_acte = 'deces' THEN ad.prenoms_defunt
    END as prenoms,
    p.numero_transaction,
    p.date_paiement
    FROM demandes d
    LEFT JOIN actes_naissance an ON d.numero_acte = an.numero_acte AND d.type_acte = 'naissance'
    LEFT JOIN actes_mariage am ON d.numero_acte = am.numero_acte AND d.type_acte = 'mariage'
    LEFT JOIN actes_deces ad ON d.numero_acte = ad.numero_acte AND d.type_acte = 'deces'
    LEFT JOIN paiements p ON d.numero_acte = p.numero_acte AND d.type_acte = p.type_acte
    WHERE d.utilisateur_id = ? 
    ORDER BY d.date_demande DESC");
$stmt->execute([$_SESSION['user_id']]);
$demandes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Espace Citoyen</title>
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

    <!-- Dashboard Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Menu</h5>
                        <div class="list-group">
                            <a href="dashboard.php" class="list-group-item list-group-item-action active">Tableau de bord</a>
                            <a href="../demande_naissance.php" class="list-group-item list-group-item-action">Demande d'acte de naissance</a>
                            <a href="../demande_mariage.php" class="list-group-item list-group-item-action">Demande d'acte de mariage</a>
                            <a href="../demande_deces.php" class="list-group-item list-group-item-action">Demande d'acte de décès</a>
                            <a href="profile.php" class="list-group-item list-group-item-action">Mon profil</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Bienvenue, <?php echo htmlspecialchars($user['prenoms'] . ' ' . $user['nom']); ?></h4>
                        
                        <!-- Statistiques -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Demandes en cours</h5>
                                        <p class="card-text h3">
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) FROM demandes WHERE utilisateur_id = ? AND statut = 'en_cours'");
                                            $stmt->execute([$_SESSION['user_id']]);
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Demandes validées</h5>
                                        <p class="card-text h3">
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) FROM demandes WHERE utilisateur_id = ? AND statut = 'valide'");
                                            $stmt->execute([$_SESSION['user_id']]);
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Demandes en attente</h5>
                                        <p class="card-text h3">
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) FROM demandes WHERE utilisateur_id = ? AND statut = 'en_attente'");
                                            $stmt->execute([$_SESSION['user_id']]);
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dernières demandes -->
                        <h5 class="mb-3">Dernières demandes</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Date de demande</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($demandes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Aucune demande trouvée</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($demandes as $demande): ?>
                                        <tr>
                                            <td><?php echo ucfirst($demande['type_acte']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($demande['date_demande'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $demande['statut'] === 'valide' ? 'success' : 
                                                        ($demande['statut'] === 'en_cours' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($demande['statut']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="details_demande.php?id=<?php echo $demande['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Détails
                                                </a>
                                                <?php if ($demande['statut'] === 'valide'): ?>
                                                    <a href="../download_pdf.php?type=<?php echo $demande['type_acte']; ?>&id=<?php echo $demande['id']; ?>" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-download"></i> Télécharger
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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