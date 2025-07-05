<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et validation des données
    $date_mariage = $_POST['date_mariage'];
    $lieu_mariage = htmlspecialchars($_POST['lieu_mariage'], ENT_QUOTES, 'UTF-8');
    $commune = htmlspecialchars($_POST['commune'], ENT_QUOTES, 'UTF-8');
    
    // Données de l'époux
    $nom_epoux = htmlspecialchars($_POST['nom_epoux'], ENT_QUOTES, 'UTF-8');
    $prenoms_epoux = htmlspecialchars($_POST['prenoms_epoux'], ENT_QUOTES, 'UTF-8');
    $date_naissance_epoux = $_POST['date_naissance_epoux'];
    $lieu_naissance_epoux = htmlspecialchars($_POST['lieu_naissance_epoux'], ENT_QUOTES, 'UTF-8');
    
    // Données de l'épouse
    $nom_epouse = htmlspecialchars($_POST['nom_epouse'], ENT_QUOTES, 'UTF-8');
    $prenoms_epouse = htmlspecialchars($_POST['prenoms_epouse'], ENT_QUOTES, 'UTF-8');
    $date_naissance_epouse = $_POST['date_naissance_epouse'];
    $lieu_naissance_epouse = htmlspecialchars($_POST['lieu_naissance_epouse'], ENT_QUOTES, 'UTF-8');
    $nombre_copies = isset($_POST['nombre_copies']) ? max(2, intval($_POST['nombre_copies'])) : 2;

    $errors = [];

    // Validation des données
    if (empty($date_mariage)) $errors[] = "La date du mariage est requise";
    if (empty($lieu_mariage)) $errors[] = "Le lieu du mariage est requis";
    if (empty($commune)) $errors[] = "La commune est requise";
    
    // Validation des données de l'époux
    if (empty($nom_epoux)) $errors[] = "Le nom de l'époux est requis";
    if (empty($prenoms_epoux)) $errors[] = "Les prénoms de l'époux sont requis";
    if (empty($date_naissance_epoux)) $errors[] = "La date de naissance de l'époux est requise";
    if (empty($lieu_naissance_epoux)) $errors[] = "Le lieu de naissance de l'époux est requis";
    
    // Validation des données de l'épouse
    if (empty($nom_epouse)) $errors[] = "Le nom de l'épouse est requis";
    if (empty($prenoms_epouse)) $errors[] = "Les prénoms de l'épouse sont requis";
    if (empty($date_naissance_epouse)) $errors[] = "La date de naissance de l'épouse est requise";
    if (empty($lieu_naissance_epouse)) $errors[] = "Le lieu de naissance de l'épouse est requis";
    if ($nombre_copies < 2) $errors[] = "Le nombre minimum de copies est de 2";

    if (empty($errors)) {
        // Générer un numéro d'acte unique
        $numero_acte = 'M' . date('Ymd') . rand(1000, 9999);
        // Générer un numéro de demande unique
        $numero_demande = 'DM' . date('YmdHis') . rand(100, 999);

        try {
            // Début de la transaction
            $conn->beginTransaction();

            // Insérer dans la table actes_mariage
            $stmt = $conn->prepare("INSERT INTO actes_mariage (
                numero_acte, date_mariage, lieu_mariage, commune,
                nom_epoux, prenoms_epoux, date_naissance_epoux, lieu_naissance_epoux,
                nom_epouse, prenoms_epouse, date_naissance_epouse, lieu_naissance_epouse,
                nombre_copies, date_etablissement, statut
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'en_attente')");
            
            $stmt->execute([
                $numero_acte, $date_mariage, $lieu_mariage, $commune,
                $nom_epoux, $prenoms_epoux, $date_naissance_epoux, $lieu_naissance_epoux,
                $nom_epouse, $prenoms_epouse, $date_naissance_epouse, $lieu_naissance_epouse,
                $nombre_copies
            ]);

            // Insérer dans la table demandes
            $stmt = $conn->prepare("INSERT INTO demandes (
                numero_demande, numero_acte, type_acte, utilisateur_id, date_demande, 
                statut, paiement_effectue, montant, nombre_copies
            ) VALUES (?, ?, 'mariage', ?, NOW(), 'en_attente', 0, ?, ?)");
            
            $montant = $nombre_copies * 500; // Prix par copie
            $stmt->execute([
                $numero_demande,
                $numero_acte,
                $_SESSION['user_id'],
                $montant,
                $nombre_copies
            ]);

            // Récupérer l'ID de la demande créée
            $stmt = $conn->prepare("SELECT id FROM demandes WHERE numero_demande = ?");
            $stmt->execute([$numero_demande]);
            $demande = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($demande) {
                $_SESSION['demande_id'] = $demande['id'];
            }

            // Validation de la transaction
            $conn->commit();

            // Récupérer l'ID de la demande
            $stmt = $conn->prepare("SELECT id FROM demandes WHERE numero_demande = ?");
            $stmt->execute([$numero_demande]);
            $demande = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($demande) {
                $_SESSION['temp_demande_id'] = $demande['id'];
                $_SESSION['success'] = "Votre demande d'acte de mariage a été enregistrée avec succès. Numéro de demande : " . $numero_demande;
                header('Location: paiement.php?numero_acte=' . $numero_acte . '&type_acte=mariage&nombre_copies=' . $nombre_copies . '&demande_id=' . $demande['id']);
                exit();
            } else {
                $errors[] = "Erreur : Impossible de récupérer l'ID de la demande";
            }
        } catch (PDOException $e) {
            // En cas d'erreur, annulation de la transaction
            $conn->rollBack();
            $errors[] = "Une erreur est survenue : " . $e->getMessage();
            error_log("Erreur lors de l'enregistrement de la demande de mariage : " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'Acte de Mariage - État Civil CI</title>
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
                        <h2 class="text-center mb-4" style="color: #FF8C00;">Demande d'acte de mariage</h2>
                        
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
                                    <div class="step-label">Informations du mariage</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Informations de l'époux</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Informations de l'épouse</div>
                                </div>
                            </div>

                            <!-- Étape 1: Informations du mariage -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations du mariage</h4>
                                <div class="mb-3">
                                    <label for="date_mariage" class="form-label">Date du mariage</label>
                                    <input type="date" class="form-control" id="date_mariage" name="date_mariage" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lieu_mariage" class="form-label">centre d'etat civil</label>
                                    <input type="text" class="form-control" id="lieu_mariage" name="lieu_mariage" required>
                                </div>
                                <div class="mb-3">
                                    <label for="commune" class="form-label">Commune</label>
                                    <input type="text" class="form-control" id="commune" name="commune" required value="Yopougon">
                                </div>
                                <div class="form-navigation">
                                    <button type="button" class="btn btn-success next-step">Suivant</button>
                                </div>
                            </div>

                            <!-- Étape 2: Informations de l'époux -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations de l'époux</h4>
                                <div class="mb-3">
                                    <label for="nom_epoux" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom_epoux" name="nom_epoux" required>
                                </div>
                                <div class="mb-3">
                                    <label for="prenoms_epoux" class="form-label">Prénoms</label>
                                    <input type="text" class="form-control" id="prenoms_epoux" name="prenoms_epoux" required>
                                </div>
                                <div class="mb-3">
                                    <label for="date_naissance_epoux" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance_epoux" name="date_naissance_epoux" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lieu_naissance_epoux" class="form-label">Lieu de naissance</label>
                                    <input type="text" class="form-control" id="lieu_naissance_epoux" name="lieu_naissance_epoux" required>
                                </div>
                                <div class="form-navigation">
                                    <button type="button" class="btn btn-outline-secondary prev-step">Précédent</button>
                                    <button type="button" class="btn btn-success next-step">Suivant</button>
                                </div>
                            </div>

                            <!-- Étape 3: Informations de l'épouse -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations de l'épouse</h4>
                                <div class="mb-3">
                                    <label for="nom_epouse" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="nom_epouse" name="nom_epouse" required>
                                </div>
                                <div class="mb-3">
                                    <label for="prenoms_epouse" class="form-label">Prénoms</label>
                                    <input type="text" class="form-control" id="prenoms_epouse" name="prenoms_epouse" required>
                                </div>
                                <div class="mb-3">
                                    <label for="date_naissance_epouse" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance_epouse" name="date_naissance_epouse" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lieu_naissance_epouse" class="form-label">Lieu de naissance</label>
                                    <input type="text" class="form-control" id="lieu_naissance_epouse" name="lieu_naissance_epouse" required>
                                </div>
                                <div class="mb-3">
                                    <label for="nombre_copies" class="form-label">Nombre de copies</label>
                                    <input type="number" class="form-control" id="nombre_copies" name="nombre_copies" min="2" value="2" required>
                                    <small class="text-muted">Minimum 2 copies</small>
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