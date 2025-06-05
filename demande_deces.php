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
    $nom_defunt = htmlspecialchars($_POST['nom'], ENT_QUOTES, 'UTF-8');
    $prenoms_defunt = htmlspecialchars($_POST['prenoms'], ENT_QUOTES, 'UTF-8');
    $date_deces = $_POST['date_deces'];
    $lieu_deces = htmlspecialchars($_POST['lieu_deces'], ENT_QUOTES, 'UTF-8');
    $date_naissance_defunt = $_POST['date_naissance'];
    $lieu_naissance_defunt = htmlspecialchars($_POST['lieu_naissance'], ENT_QUOTES, 'UTF-8');
    $commune = htmlspecialchars($_POST['commune'], ENT_QUOTES, 'UTF-8');
    $cause_deces = htmlspecialchars($_POST['cause_deces'], ENT_QUOTES, 'UTF-8');
    $declarant = htmlspecialchars($_POST['declarant'], ENT_QUOTES, 'UTF-8');
    $lien_declarant = htmlspecialchars($_POST['lien_declarant'], ENT_QUOTES, 'UTF-8');
    $nombre_copies = max(2, intval($_POST['nombre_copies']));

    error_log("=== Début du traitement de la demande de décès ===");
    error_log("Données reçues : " . print_r($_POST, true));

    $errors = [];

    // Validation des données
    if (empty($nom_defunt)) $errors[] = "Le nom est requis";
    if (empty($prenoms_defunt)) $errors[] = "Les prénoms sont requis";
    if (empty($date_deces)) $errors[] = "La date du décès est requise";
    if (empty($lieu_deces)) $errors[] = "Le lieu du décès est requis";
    if (empty($date_naissance_defunt)) $errors[] = "La date de naissance est requise";
    if (empty($lieu_naissance_defunt)) $errors[] = "Le lieu de naissance est requis";
    if (empty($commune)) $errors[] = "La commune est requise";
    if (empty($cause_deces)) $errors[] = "La cause du décès est requise";
    if (empty($declarant)) $errors[] = "Le nom du déclarant est requis";
    if (empty($lien_declarant)) $errors[] = "Le lien avec le défunt est requis";
    if ($nombre_copies < 2) $errors[] = "Le nombre minimum de copies est de 2";

    if (empty($errors)) {
        try {
            // Début de la transaction
            $conn->beginTransaction();

            // Générer un numéro d'acte unique
            $numero_acte = 'D' . date('Ymd') . rand(1000, 9999);
            
            // Générer un numéro de demande unique
            $annee = date('Y');
            $mois = date('m');
            
            // Récupérer le dernier numéro de demande pour ce mois
            $stmt = $conn->prepare("
                SELECT MAX(CAST(SUBSTRING_INDEX(numero_demande, '-', -1) AS UNSIGNED)) as dernier_numero
                FROM demandes 
                WHERE numero_demande LIKE :prefix
            ");
            $prefix = "DEM-{$annee}{$mois}-";
            $stmt->execute(['prefix' => $prefix . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $nouveau_numero = ($result['dernier_numero'] ?? 0) + 1;
            $numero_demande = $prefix . str_pad($nouveau_numero, 4, '0', STR_PAD_LEFT);

            error_log("Numéro de demande généré : " . $numero_demande);

            // Insérer dans la table actes_deces
            $stmt = $conn->prepare("INSERT INTO actes_deces (
                numero_acte, nom_defunt, prenoms_defunt, date_deces, lieu_deces,
                date_naissance_defunt, lieu_naissance_defunt, commune, cause_deces,
                nombre_copies, date_etablissement, statut,
                declarant, lien_declarant
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'en_attente', ?, ?)");
            
            $stmt->execute([
                $numero_acte, $nom_defunt, $prenoms_defunt, $date_deces, $lieu_deces,
                $date_naissance_defunt, $lieu_naissance_defunt, $commune, $cause_deces,
                $nombre_copies, $declarant, $lien_declarant
            ]);

            error_log("Acte de décès inséré avec succès");

            // Insérer dans la table demandes
            $stmt = $conn->prepare("INSERT INTO demandes (
                numero_demande, numero_acte, type_acte, utilisateur_id, date_demande, 
                statut, paiement_effectue, montant, nombre_copies
            ) VALUES (?, ?, 'deces', ?, NOW(), 'en_attente', 0, ?, ?)");
            
            $montant = $nombre_copies * 500; // Prix par copie
            $stmt->execute([
                $numero_demande,
                $numero_acte,
                $_SESSION['user_id'],
                $montant,
                $nombre_copies
            ]);

            $demande_id = $conn->lastInsertId();
            error_log("Demande insérée avec succès - ID: " . $demande_id);

            if (!$demande_id) {
                throw new PDOException("Erreur : Impossible de récupérer l'ID de la demande.");
            }

            // Vérifier que la demande existe bien
            $verify_stmt = $conn->prepare("SELECT id FROM demandes WHERE id = ? AND utilisateur_id = ?");
            $verify_stmt->execute([$demande_id, $_SESSION['user_id']]);
            $demande_exists = $verify_stmt->fetch();

            if (!$demande_exists) {
                throw new PDOException("Erreur : La demande créée n'a pas été trouvée dans la base de données.");
            }

            // Validation de la transaction
            $conn->commit();

            // Stocker l'ID dans la session
            $_SESSION['temp_demande_id'] = $demande_id;
            $_SESSION['last_demande_numero'] = $numero_demande;

            error_log("Redirection vers la page de paiement...");
            error_log("demande_id: " . $demande_id);
            error_log("numero_acte: " . $numero_acte);
            error_log("nombre_copies: " . $nombre_copies);

            $_SESSION['success'] = "Votre demande d'acte de décès a été enregistrée avec succès. Numéro de demande : " . $numero_demande;
            
            // Redirection vers la page de paiement
            $redirect_url = sprintf(
                'paiement.php?numero_acte=%s&type_acte=deces&nombre_copies=%d&demande_id=%d',
                urlencode($numero_acte),
                $nombre_copies,
                $demande_id
            );

            error_log("URL de redirection : " . $redirect_url);
            header("Location: " . $redirect_url);
            exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Erreur lors de la création de la demande : " . $e->getMessage());
            error_log("Stack trace : " . $e->getTraceAsString());
            $errors[] = "Une erreur est survenue lors de la création de la demande : " . $e->getMessage();
        }
    } else {
        error_log("Erreurs de validation : " . print_r($errors, true));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'Acte de Décès - État Civil CI</title>
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
                        <h2 class="text-center mb-4" style="color: #FF8C00;">Demande d'acte de décès</h2>
                        
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
                                    <div class="step-label">Informations personnelles</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Informations du décès</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Informations complémentaires</div>
                                </div>
                            </div>

                            <!-- Étape 1: Informations personnelles -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations personnelles</h4>
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

                            <!-- Étape 2: Informations du décès -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations du décès</h4>
                                <div class="mb-3">
                                    <label for="date_deces" class="form-label">Date du décès</label>
                                    <input type="date" class="form-control" id="date_deces" name="date_deces" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lieu_deces" class="form-label">Lieu du décès</label>
                                    <input type="text" class="form-control" id="lieu_deces" name="lieu_deces" required>
                                </div>
                                <div class="mb-3">
                                    <label for="cause_deces" class="form-label">Cause du décès</label>
                                    <textarea class="form-control" id="cause_deces" name="cause_deces" rows="3" required></textarea>
                                </div>
                                <div class="form-navigation">
                                    <button type="button" class="btn btn-outline-secondary prev-step">Précédent</button>
                                    <button type="button" class="btn btn-success next-step">Suivant</button>
                                </div>
                            </div>

                            <!-- Étape 3: Informations complémentaires -->
                            <div class="form-step">
                                <h4 class="mb-4">Informations complémentaires</h4>
                                <div class="mb-3">
                                    <label for="commune" class="form-label">Commune</label>
                                    <input type="text" class="form-control" id="commune" name="commune" required>
                                </div>
                                <div class="mb-3">
                                    <label for="declarant" class="form-label">Nom du déclarant</label>
                                    <input type="text" class="form-control" id="declarant" name="declarant" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lien_declarant" class="form-label">Lien avec le défunt</label>
                                    <select class="form-select" id="lien_declarant" name="lien_declarant" required>
                                        <option value="">Sélectionnez un lien</option>
                                        <option value="conjoint">Conjoint(e)</option>
                                        <option value="enfant">Enfant</option>
                                        <option value="parent">Parent</option>
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