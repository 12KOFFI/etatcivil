<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>État Civil Côte d'Ivoire - Plateforme de Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .nav-link i {
            font-size: 1.2rem;
        }
        .nav-link:hover {
            color: #fff !important;
        }
    </style>
</head>
<body>
    <?php
    // Démarrer la session
    session_start();

    // Vérifier si l'utilisateur est connecté
    if (isset($_SESSION['user_id'])) {
        // Rediriger vers le tableau de bord approprié selon le rôle
        if ($_SESSION['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: citoyen/dashboard.php');
        }
        exit();
    }

    require_once 'config/database.php';
    ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #FF8C00;">
        <div class="container">
            <a class="navbar-brand" href="index.php">État Civil CI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="citoyen/dashboard.php">Tableau de bord</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Connexion">
                                <i class="fas fa-sign-in-alt"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Inscription">
                                <i class="fas fa-user-plus"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section py-5" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold" style="color: #FF8C00;">Simplifiez vos démarches administratives</h1>
                    <p class="lead">Accédez à vos documents d'état civil en ligne, rapidement et en toute sécurité.</p>
                    <a href="services.php" class="btn btn-success btn-lg">Commencer</a>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/hero-image.jpg" alt="État Civil CI" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <div class="services-section py-5">
        <div class="container">
            <h2 class="text-center mb-5" style="color: #FF8C00;">Nos Services</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title" style="color: #28a745;">Acte de Naissance</h3>
                            <p class="card-text">Demandez votre acte de naissance en ligne et recevez-le rapidement.</p>
                            <a href="services.php#naissance" class="btn btn-outline-success">En savoir plus</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title" style="color: #28a745;">Acte de Mariage</h3>
                            <p class="card-text">Obtenez votre acte de mariage en quelques clics.</p>
                            <a href="services.php#mariage" class="btn btn-outline-success">En savoir plus</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title" style="color: #28a745;">Acte de Décès</h3>
                            <p class="card-text">Demandez un acte de décès en ligne simplement.</p>
                            <a href="services.php#deces" class="btn btn-outline-success">En savoir plus</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-4" style="background-color: #FF8C00; color: white;">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>État Civil CI</h5>
                    <p>Simplifiez vos démarches administratives</p>
                </div>
                <div class="col-md-4">
                    <h5>Liens Rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="services.php" class="text-white">Services</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                        <li><a href="mentions-legales.php" class="text-white">Mentions légales</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p>Email: contact@etatcivil.ci<br>
                    Tél: +225 XX XX XX XX</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialisation des tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html> 