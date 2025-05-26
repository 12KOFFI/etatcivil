<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Services - État Civil CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .service-card {
            transition: transform 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .service-icon {
            font-size: 2.5rem;
            color: #FF8C00;
            margin-bottom: 1rem;
        }
        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: #FF8C00;
        }
        .doc-list {
            list-style: none;
            padding-left: 0;
        }
        .doc-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .doc-list li:last-child {
            border-bottom: none;
        }
        .doc-list li i {
            color: #28a745;
            margin-right: 10px;
        }
        .btn-service {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-service:hover {
            transform: scale(1.05);
        }
        .nav-link i {
            font-size: 1.2rem;
        }
        .nav-link:hover {
            color: #fff !important;
        }
    </style>
</head>
<body>
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
                        <a class="nav-link active" href="services.php">Services</a>
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

    <!-- Services Content -->
    <div class="container py-5">
        <h1 class="text-center section-title" style="color: #FF8C00;">Nos Services</h1>
        
        <!-- Acte de Naissance -->
        <section id="naissance" class="mb-5">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="service-card p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-baby service-icon"></i>
                            <h2 style="color: #FF8C00;">Acte de Naissance</h2>
                        </div>
                        <h3>Comment obtenir votre acte de naissance ?</h3>
                        <p>Pour obtenir votre acte de naissance, vous devez :</p>
                        <ul class="doc-list">
                            <li><i class="fas fa-check-circle"></i> Être connecté à votre compte</li>
                            <li><i class="fas fa-check-circle"></i> Remplir le formulaire de demande</li>
                            <li><i class="fas fa-check-circle"></i> Fournir les documents nécessaires</li>
                            <li><i class="fas fa-check-circle"></i> Effectuer le paiement des frais</li>
                        </ul>
                        <div class="text-center mt-4">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="demande_naissance.php" class="btn btn-success btn-service">
                                    <i class="fas fa-file-alt me-2"></i>Faire une demande
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=demande_naissance.php" class="btn btn-success btn-service">
                                    <i class="fas fa-user-plus me-2"></i>S'inscrire pour faire une demande
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="service-card p-4">
                        <h4 class="mb-4"><i class="fas fa-file-alt me-2"></i>Documents requis :</h4>
                        <ul class="doc-list">
                            <li><i class="fas fa-id-card"></i> Pièce d'identité valide</li>
                            <li><i class="fas fa-home"></i> Justificatif de domicile</li>
                            <li><i class="fas fa-file-signature"></i> Formulaire complété</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Acte de Mariage -->
        <section id="mariage" class="mb-5">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="service-card p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-ring service-icon"></i>
                            <h2 style="color: #FF8C00;">Acte de Mariage</h2>
                        </div>
                        <h3>Comment obtenir votre acte de mariage ?</h3>
                        <p>Pour obtenir votre acte de mariage, vous devez :</p>
                        <ul class="doc-list">
                            <li><i class="fas fa-check-circle"></i> Être connecté à votre compte</li>
                            <li><i class="fas fa-check-circle"></i> Remplir le formulaire de demande</li>
                            <li><i class="fas fa-check-circle"></i> Fournir les documents nécessaires</li>
                            <li><i class="fas fa-check-circle"></i> Effectuer le paiement des frais</li>
                        </ul>
                        <div class="text-center mt-4">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="demande_mariage.php" class="btn btn-success btn-service">
                                    <i class="fas fa-file-alt me-2"></i>Faire une demande
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=demande_mariage.php" class="btn btn-success btn-service">
                                    <i class="fas fa-user-plus me-2"></i>S'inscrire pour faire une demande
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="service-card p-4">
                        <h4 class="mb-4"><i class="fas fa-file-alt me-2"></i>Documents requis :</h4>
                        <ul class="doc-list">
                            <li><i class="fas fa-id-card"></i> Pièce d'identité des deux époux</li>
                            <li><i class="fas fa-home"></i> Justificatif de domicile</li>
                            <li><i class="fas fa-file-signature"></i> Formulaire complété</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Acte de Décès -->
        <section id="deces" class="mb-5">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="service-card p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-cross service-icon"></i>
                            <h2 style="color: #FF8C00;">Acte de Décès</h2>
                        </div>
                        <h3>Comment obtenir un acte de décès ?</h3>
                        <p>Pour obtenir un acte de décès, vous devez :</p>
                        <ul class="doc-list">
                            <li><i class="fas fa-check-circle"></i> Être connecté à votre compte</li>
                            <li><i class="fas fa-check-circle"></i> Remplir le formulaire de demande</li>
                            <li><i class="fas fa-check-circle"></i> Fournir les documents nécessaires</li>
                            <li><i class="fas fa-check-circle"></i> Effectuer le paiement des frais</li>
                        </ul>
                        <div class="text-center mt-4">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="demande_deces.php" class="btn btn-success btn-service">
                                    <i class="fas fa-file-alt me-2"></i>Faire une demande
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=demande_deces.php" class="btn btn-success btn-service">
                                    <i class="fas fa-user-plus me-2"></i>S'inscrire pour faire une demande
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="service-card p-4">
                        <h4 class="mb-4"><i class="fas fa-file-alt me-2"></i>Documents requis :</h4>
                        <ul class="doc-list">
                            <li><i class="fas fa-id-card"></i> Pièce d'identité du demandeur</li>
                            <li><i class="fas fa-home"></i> Justificatif de domicile</li>
                            <li><i class="fas fa-file-signature"></i> Formulaire complété</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
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