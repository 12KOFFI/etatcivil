<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenoms = htmlspecialchars(trim($_POST['prenoms']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $cni = htmlspecialchars(trim($_POST['cni']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validation des données
    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    }

    if (empty($prenoms)) {
        $errors[] = "Les prénoms sont requis";
    }

    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    }

    if (empty($cni)) {
        $errors[] = "Le numéro CNI est requis";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Cet email est déjà utilisé";
    }

    // Vérifier si le CNI existe déjà
    $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE cni = ?");
    $stmt->execute([$cni]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Ce numéro CNI est déjà utilisé";
    }

    if (empty($errors)) {
        // Hashage du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insertion dans la base de données
        $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, prenoms, email, telephone, cni, mot_de_passe, role, date_inscription) VALUES (?, ?, ?, ?, ?, ?, 'citoyen', NOW())");
        if ($stmt->execute([$nom, $prenoms, $email, $telephone, $cni, $hashed_password])) {
            $_SESSION['success'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            header('Location: login.php');
            exit();
        } else {
            $errors[] = "Une erreur est survenue lors de l'inscription";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - État Civil CI</title>
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
</head>
<body>
    <!-- Navigation -->
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


    <!-- Register Section -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4" style="color: #FF8C00;">Inscription</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenoms" class="form-label">Prénoms</label>
                                <input type="text" class="form-control" id="prenoms" name="prenoms" value="<?php echo isset($_POST['prenoms']) ? htmlspecialchars($_POST['prenoms']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="cni" class="form-label">Numéro CNI</label>
                                <input type="text" class="form-control" id="cni" name="cni" value="<?php echo isset($_POST['cni']) ? htmlspecialchars($_POST['cni']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Le mot de passe doit contenir au moins 8 caractères</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">S'inscrire</button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p>Déjà inscrit ? <a href="login.php" style="color: #FF8C00;">Se connecter</a></p>
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
                <div class="col-md-12 text-center">
                    <p>&copy; <?php echo date('Y'); ?> État Civil CI. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 