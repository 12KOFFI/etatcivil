<?php
session_start();
require_once 'config/database.php';

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord approprié
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: citoyen/dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenoms'] = $user['prenoms'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirection en fonction du rôle
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    // Vérifier s'il y a une redirection demandée
                    if (isset($_GET['redirect'])) {
                        header('Location: ' . $_GET['redirect']);
                    } else {
                        header('Location: citoyen/dashboard.php');
                    }
                }
                exit();
            } else {
                $error = "Email ou mot de passe incorrect";
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - État Civil CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
        .nav-link i {
            font-size: 1.2rem;
        }
        .nav-link:hover {
            color: #fff !important;
        }
    </style>


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

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4" style="color: #FF8C00;">Connexion</h2>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Se connecter</button>
                                <a href="register.php" class="btn btn-outline-secondary">Créer un compte</a>
                            </div>
                        </form>
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