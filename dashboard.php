<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les statistiques des actes
$stats = [
    'naissance' => $conn->query("SELECT COUNT(*) FROM actes_naissance WHERE statut = 'valide'")->fetchColumn(),
    'mariage' => $conn->query("SELECT COUNT(*) FROM actes_mariage WHERE statut = 'valide'")->fetchColumn(),
    'deces' => $conn->query("SELECT COUNT(*) FROM actes_deces WHERE statut = 'valide'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - État Civil CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #FF8C00;">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">État Civil CI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Mon profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2 class="mb-4" style="color: #FF8C00;">Bienvenue, <?php echo htmlspecialchars($user['name']); ?></h2>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Actes de Naissance</h5>
                        <p class="card-text display-4"><?php echo $stats['naissance']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Actes de Mariage</h5>
                        <p class="card-text display-4"><?php echo $stats['mariage']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Actes de Décès</h5>
                        <p class="card-text display-4"><?php echo $stats['deces']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-4" style="color: #FF8C00;">Actions rapides</h3>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text display-4 mb-3" style="color: #FF8C00;"></i>
                        <h5 class="card-title">Demander un acte de naissance</h5>
                        <p class="card-text">Faites une demande d'acte de naissance en ligne</p>
                        <a href="demande_naissance.php" class="btn btn-success">Commencer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-heart-fill display-4 mb-3" style="color: #FF8C00;"></i>
                        <h5 class="card-title">Demander un acte de mariage</h5>
                        <p class="card-text">Faites une demande d'acte de mariage en ligne</p>
                        <a href="demande_mariage.php" class="btn btn-success">Commencer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-x display-4 mb-3" style="color: #FF8C00;"></i>
                        <h5 class="card-title">Demander un acte de décès</h5>
                        <p class="card-text">Faites une demande d'acte de décès en ligne</p>
                        <a href="demande_deces.php" class="btn btn-success">Commencer</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-4" style="background-color: #FF8C00; color: white;">
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