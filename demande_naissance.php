<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fonction de nettoyage des chaînes
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = sanitize_input($_POST['nom']);
    $prenoms = sanitize_input($_POST['prenoms']);
    $date_naissance = $_POST['date_naissance'];
    $lieu_naissance = sanitize_input($_POST['lieu_naissance']);
    $nom_pere = sanitize_input($_POST['nom_pere']);
    $nom_mere = sanitize_input($_POST['nom_mere']);
    $commune = sanitize_input($_POST['commune']);
    $declarant = sanitize_input($_POST['declarant']);
    $lien_declarant = sanitize_input($_POST['lien_declarant']);
    $nombre_copies = max(2, intval($_POST['nombre_copies']));

    $errors = [];

    // Validation des données
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenoms)) $errors[] = "Les prénoms sont requis";
    if (empty($date_naissance)) $errors[] = "La date de naissance est requise";
    if (empty($lieu_naissance)) $errors[] = "Le lieu de naissance est requis";
    if (empty($nom_pere)) $errors[] = "Le nom du père est requis";
    if (empty($nom_mere)) $errors[] = "Le nom de la mère est requis";
    if (empty($commune)) $errors[] = "La commune est requise";
    if (empty($declarant)) $errors[] = "Le nom du déclarant est requis";
    if (empty($lien_declarant)) $errors[] = "Le lien avec le déclarant est requis";
    if ($nombre_copies < 2) $errors[] = "Le nombre minimum de copies est de 2";

    if (empty($errors)) {
        // Générer un numéro d'acte unique
        $numero_acte = 'N' . date('Ymd') . rand(1000, 9999);

        try {
            $stmt = $conn->prepare("INSERT INTO actes_naissance (
                numero_acte, nom, prenoms, date_naissance, lieu_naissance,
                nom_pere, nom_mere, commune, declarant, lien_declarant,
                nombre_copies, date_etablissement
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            
            if ($stmt->execute([
                $numero_acte, $nom, $prenoms, $date_naissance, $lieu_naissance,
                $nom_pere, $nom_mere, $commune, $declarant, $lien_declarant,
                $nombre_copies
            ])) {
                // Créer l'entrée dans la table demandes
                $stmt = $conn->prepare("INSERT INTO demandes (
                    numero_demande, type_acte, numero_acte, utilisateur_id,
                    date_demande, statut, nombre_copies, montant
                ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
                
                $montant = $nombre_copies * 500; // 500 FCFA par copie
                
                if ($stmt->execute([
                    $numero_acte,
                    'naissance',
                    $numero_acte,
                    $_SESSION['user_id'],
                    'en_attente',
                    $nombre_copies,
                    $montant
                ])) {
                    // Récupérer l'ID de la demande
                    $stmt = $conn->prepare("SELECT id FROM demandes WHERE numero_demande = ?");
                    $stmt->execute([$numero_acte]);
                    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($demande) {
                        $_SESSION['temp_demande_id'] = $demande['id'];
                        $_SESSION['success'] = "Votre demande d'acte de naissance a été enregistrée avec succès. Numéro de demande : " . $numero_acte;
                        header('Location: paiement.php?numero_acte=' . $numero_acte . '&type_acte=naissance&demande_id=' . $demande['id']);
                        exit();
                    } else {
                        $errors[] = "Erreur : Impossible de récupérer l'ID de la demande";
                    }
                } else {
                    $errors[] = "Une erreur est survenue lors de l'enregistrement de la demande";
                }
            } else {
                $errors[] = "Une erreur est survenue lors de l'enregistrement de la demande";
            }
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'acte de naissance - État Civil CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #FF8C00;">
        <div class="container">
            <a class="navbar-brand" href="citoyen/dashboard.php">État Civil CI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="citoyen/dashboard.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Formulaire de demande -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4" style="color: #FF8C00;">Demande d'acte de naissance</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="multi-step-form">
                            <!-- Barre de progression -->
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>

                            <!-- Indicateurs d'étapes -->
                            <div class="step-indicator">
                                <div class="step">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Informations sur la personne concernée</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Informations des parents</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Informations du déclarant</div>
                                </div>
                            </div>

                            <!-- Étape 1: Informations sur la personne concernée -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations sur la personne concernée</h4>
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required>
                                </div>
                                <div class="mb-3">
                                    <label for="prenoms" class="form-label">Prénoms</label>
                                    <input type="text" class="form-control" id="prenoms" name="prenoms" required>
                                </div>
                                <div class="mb-3">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lieu_naissance" class="form-label">Lieu de naissance</label>
                                    <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" required>
                                </div>
                                <div class="form-navigation">
                                    <button type="button" class="btn btn-success next-step">Suivant</button>
                                </div>
                            </div>

                            <!-- Étape 2: Informations des parents -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations des parents</h4>
                                <div class="mb-3">
                                    <label for="nom_pere" class="form-label">Nom du père</label>
                                    <input type="text" class="form-control" id="nom_pere" name="nom_pere" required>
                                </div>
                                <div class="mb-3">
                                    <label for="nom_mere" class="form-label">Nom de la mère</label>
                                    <input type="text" class="form-control" id="nom_mere" name="nom_mere" required>
                                </div>
                                <div class="mb-3">
                                    <label for="commune" class="form-label">Commune de naissance</label>
                                    <input type="text" class="form-control" id="commune" name="commune" required>
                                </div>
                                <div class="form-navigation">
                                    <button type="button" class="btn btn-outline-secondary prev-step">Précédent</button>
                                    <button type="button" class="btn btn-success next-step">Suivant</button>
                                </div>
                            </div>

                            <!-- Étape 3: Informations du déclarant -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations du déclarant</h4>
                                <div class="mb-3">
                                    <label for="declarant" class="form-label">Nom du déclarant</label>
                                    <input type="text" class="form-control" id="declarant" name="declarant" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lien_declarant" class="form-label">Lien avec l'enfant</label>
                                    <select class="form-select" id="lien_declarant" name="lien_declarant" required>
                                        <option value="">Sélectionnez un lien</option>
                                        <option value="pere">Père</option>
                                        <option value="mere">Mère</option>
                                        <option value="tuteur">Tuteur légal</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nombre_copies" class="form-label">Nombre de copies (minimum 2)</label>
                                    <input type="number" class="form-control" id="nombre_copies" name="nombre_copies" 
                                           min="2" value="2" required>
                                    <div class="form-text">Prix : 500 FCFA par copie</div>
                                </div>
                                <div class="form-navigation">
                                    <button type="button" class="btn btn-outline-secondary prev-step">Précédent</button>
                                    <button type="submit" class="btn btn-success">Soumettre la demande</button>
                                </div>
                            </div>
                        </form>
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
    <script src="js/form-steps.js"></script>
</body>
</html> 