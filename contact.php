<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - État Civil Côte d'Ivoire</title>
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
    session_start();
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
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact</a>
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

    <!-- Contact Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-6">
                <h2 class="mb-4" style="color: #FF8C00;">Contactez-nous</h2>
                <form action="traitement_contact.php" method="POST">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="sujet" class="form-label">Sujet</label>
                        <input type="text" class="form-control" id="sujet" name="sujet" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Envoyer</button>
                </form>
            </div>
            <div class="col-md-6">
                <h2 class="mb-4" style="color: #FF8C00;">Informations de contact</h2>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Adresse</h5>
                        <p>Ministère de l'Intérieur et de la Sécurité<br>
                        Direction de l'État Civil<br>
                        Abidjan, Côte d'Ivoire</p>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Téléphone</h5>
                        <p>+225 XX XX XX XX</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5>Email</h5>
                        <p>contact@etatcivil.ci</p>
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