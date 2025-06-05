<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Débogage des paramètres reçus
error_log("=== Début du traitement paiement.php ===");
error_log("Session ID: " . session_id());
error_log("GET parameters: " . print_r($_GET, true));
error_log("Session data: " . print_r($_SESSION, true));

// Vérification des paramètres requis
$required_params = ['numero_acte', 'type_acte'];
$missing_params = [];

foreach ($required_params as $param) {
    if (!isset($_GET[$param]) || empty($_GET[$param])) {
        $missing_params[] = $param;
    }
}

// Récupération de l'ID de la demande
$demande_id = null;

// D'abord essayer depuis l'URL
if (isset($_GET['demande_id']) && !empty($_GET['demande_id'])) {
    $demande_id = trim($_GET['demande_id']);
    error_log("ID de la demande trouvé dans l'URL : " . $demande_id);
}
// Sinon, essayer depuis la session
elseif (isset($_SESSION['temp_demande_id'])) {
    $demande_id = $_SESSION['temp_demande_id'];
    error_log("ID de la demande récupéré depuis la session : " . $demande_id);
}

if (!$demande_id) {
    error_log("Aucun ID de demande trouvé (ni dans l'URL, ni dans la session)");
    $missing_params[] = 'demande_id';
}

if (!empty($missing_params)) {
    error_log("Paramètres manquants dans paiement.php : " . implode(', ', $missing_params));
    $_SESSION['error'] = "Paramètres manquants : " . implode(', ', $missing_params) . ". Veuillez recommencer le processus de demande.";
    header('Location: citoyen/dashboard.php');
    exit();
}

$numero_acte = trim($_GET['numero_acte']);
$type_acte = trim($_GET['type_acte']);
$nombre_copies = isset($_GET['nombre_copies']) ? max(2, intval($_GET['nombre_copies'])) : 2;

// Vérifier que la demande existe dans la base de données
try {
    $verify_stmt = $conn->prepare("SELECT id, numero_demande FROM demandes WHERE id = ? AND utilisateur_id = ? AND type_acte = ?");
    $verify_stmt->execute([$demande_id, $_SESSION['user_id'], $type_acte]);
    $demande = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        error_log("Demande non trouvée en base de données. ID: " . $demande_id . ", User: " . $_SESSION['user_id'] . ", Type: " . $type_acte);
        throw new Exception("Demande non trouvée. Veuillez recommencer le processus de demande.");
    }

    error_log("Demande trouvée en base de données : " . print_r($demande, true));
} catch (Exception $e) {
    error_log("Erreur lors de la vérification de la demande : " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: citoyen/dashboard.php');
    exit();
}

// Nettoyage de la variable temporaire de session
if (isset($_SESSION['temp_demande_id'])) {
    unset($_SESSION['temp_demande_id']);
}

error_log("Paramètres validés :");
error_log("numero_acte: " . $numero_acte);
error_log("type_acte: " . $type_acte);
error_log("demande_id: " . $demande_id);
error_log("nombre_copies: " . $nombre_copies);

// Prix de base par copie
$prix_base = 500;

// Calculer le montant total
$montant = $prix_base * $nombre_copies;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mode_paiement = $_POST['mode_paiement'];
    $errors = [];

    // Débogage : Afficher les données POST et GET
    error_log("Mode de paiement : " . $mode_paiement);
    error_log("POST data : " . print_r($_POST, true));
    error_log("GET data : " . print_r($_GET, true));
    error_log("Session data : " . print_r($_SESSION, true));

    if ($mode_paiement == 'carte') {
        // Validation du paiement par carte
        $numero_carte = $_POST['numero_carte'];
        $date_expiration = $_POST['date_expiration'];
        $cvv = $_POST['cvv'];
        $titulaire = $_POST['titulaire'];

        if (strlen($numero_carte) != 16) $errors[] = "Le numéro de carte doit contenir 16 chiffres";
        if (strlen($cvv) != 3) $errors[] = "Le code CVV doit contenir 3 chiffres";
        if (empty($titulaire)) $errors[] = "Le nom du titulaire est requis";

        // Simulation de validation de la carte
        if (empty($errors) && substr($numero_carte, 0, 1) != '4') {
            $errors[] = "Carte invalide ou refusée par la banque";
        }
    } else {
        // Validation du paiement Mobile Money
        $operateur = $_POST['operateur'];
        $numero_momo = $_POST['numero_momo'];

        if (empty($operateur)) $errors[] = "Veuillez sélectionner un opérateur";
        if (strlen($numero_momo) != 10) $errors[] = "Le numéro Mobile Money doit contenir 10 chiffres";
    }

    // Traitement du paiement si pas d'erreurs
    if (empty($errors)) {
        try {
            // Début de la transaction
            $conn->beginTransaction();

            // Générer le numéro de transaction
            $numero_transaction = 'PAY' . date('YmdHis');

            // Vérifier si la demande existe déjà
            $stmt = $conn->prepare("SELECT id FROM demandes WHERE id = ? AND type_acte = ? AND utilisateur_id = ?");
            $stmt->execute([$demande_id, $type_acte, $_SESSION['user_id']]);
            $demande = $stmt->fetch();

            if (!$demande) {
                throw new Exception("Demande non trouvée. Veuillez recommencer le processus de demande.");
            }

            // Mettre à jour la demande existante
            $stmt = $conn->prepare("UPDATE demandes SET 
                statut = 'en_cours',
                paiement_effectue = 1,
                date_traitement = NOW(),
                montant = ?,
                nombre_copies = ?
                WHERE id = ? AND type_acte = ? AND utilisateur_id = ?");
            
            $stmt->execute([
                $montant,
                $nombre_copies,
                $demande_id,
                $type_acte,
                $_SESSION['user_id']
            ]);

            // Enregistrer le paiement
            $stmt = $conn->prepare("INSERT INTO paiements (
                numero_transaction, montant, type_acte, numero_acte, date_paiement, 
                statut, mode_paiement, utilisateur_id
            ) VALUES (?, ?, ?, ?, NOW(), 'valide', ?, ?)");
            
            $stmt->execute([
                $numero_transaction,
                $montant,
                $type_acte,
                $numero_acte,
                $mode_paiement,
                $_SESSION['user_id']
            ]);

            // Mettre à jour le statut de l'acte
            $table = 'actes_' . $type_acte;
            $stmt = $conn->prepare("UPDATE $table SET statut = 'en_cours', nombre_copies = ? WHERE numero_acte = ?");
            $stmt->execute([$nombre_copies, $numero_acte]);

            // Validation de la transaction
            $conn->commit();

            error_log("Paiement réussi - Transaction: " . $numero_transaction);
            $_SESSION['success'] = "Paiement effectué avec succès. Référence : " . $numero_transaction;
            header('Location: citoyen/dashboard.php');
            exit();
        } catch (PDOException $e) {
            // En cas d'erreur, annulation de la transaction
            $conn->rollBack();
            $errors[] = "Une erreur est survenue lors du traitement du paiement : " . $e->getMessage();
            error_log("Erreur de paiement : " . $e->getMessage());
            error_log("Stack trace : " . $e->getTraceAsString());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation de Paiement - État Civil CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
                        <a class="nav-link" href="citoyen/dashboard.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Formulaire de paiement -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Paiement</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h5>Détails de la demande</h5>
                            <p><strong>Type d'acte :</strong> <?php echo ucfirst($type_acte); ?></p>
                            <p><strong>Numéro d'acte :</strong> <?php echo $numero_acte; ?></p>
                            <p><strong>Prix par copie :</strong> 500 FCFA</p>
                            <div class="mb-3">
                                <label for="nombre_copies" class="form-label">Nombre de copies</label>
                                <input type="number" class="form-control" id="nombre_copies" name="nombre_copies" 
                                       min="2" value="<?php echo $nombre_copies; ?>" onchange="updateMontant()">
                            </div>
                            <p><strong>Montant total :</strong> <span id="montant_total"><?php echo number_format($montant); ?> FCFA</span></p>
                        </div>

                        <form method="POST" action="" id="paiement-form">
                            <div class="mb-4">
                                <h5>Mode de paiement</h5>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="mode_carte" 
                                           value="carte" checked onchange="togglePaiementForm('carte')">
                                    <label class="form-check-label" for="mode_carte">
                                        Paiement par carte bancaire
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="mode_momo" 
                                           value="momo" onchange="togglePaiementForm('momo')">
                                    <label class="form-check-label" for="mode_momo">
                                        Mobile Money
                                    </label>
                                </div>
                            </div>

                            <!-- Formulaire de paiement par carte -->
                            <div id="carte-form">
                                <div class="mb-3">
                                    <label for="numero_carte" class="form-label">Numéro de carte</label>
                                    <input type="text" class="form-control" id="numero_carte" name="numero_carte" 
                                           maxlength="16" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_expiration" class="form-label">Date d'expiration</label>
                                            <input type="text" class="form-control" id="date_expiration" 
                                                   name="date_expiration" placeholder="MM/AA" maxlength="5" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control" id="cvv" name="cvv" 
                                                   maxlength="3" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="titulaire" class="form-label">Nom du titulaire</label>
                                    <input type="text" class="form-control" id="titulaire" name="titulaire" required>
                                </div>
                            </div>

                            <!-- Formulaire de paiement Mobile Money -->
                            <div id="momo-form" style="display: none;">
                                <div class="mb-3">
                                    <label for="operateur" class="form-label">Opérateur</label>
                                    <select class="form-select" id="operateur" name="operateur" required>
                                        <option value="">Sélectionnez un opérateur</option>
                                        <option value="moov">Moov Money</option>
                                        <option value="mtn">MTN Mobile Money</option>
                                        <option value="orange">Orange Money</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="numero_momo" class="form-label">Numéro Mobile Money</label>
                                    <input type="text" class="form-control" id="numero_momo" name="numero_momo" 
                                           maxlength="10" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submit-btn">Payer</button>
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
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        // Validation du formulaire
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Formatage automatique de la date d'expiration
        document.getElementById('date_expiration').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2,4);
            }
            e.target.value = value;
        });

        function updateMontant() {
            const nombreCopies = document.getElementById('nombre_copies').value;
            const montant = nombreCopies * 500;
            document.getElementById('montant_total').textContent = montant.toLocaleString() + ' FCFA';
        }

        function togglePaiementForm(mode) {
            const carteForm = document.getElementById('carte-form');
            const momoForm = document.getElementById('momo-form');
            
            if (mode === 'carte') {
                carteForm.style.display = 'block';
                momoForm.style.display = 'none';
                // Mettre à jour les champs requis
                document.querySelectorAll('#carte-form input').forEach(input => input.required = true);
                document.querySelectorAll('#momo-form input, #momo-form select').forEach(input => input.required = false);
            } else {
                carteForm.style.display = 'none';
                momoForm.style.display = 'block';
                // Mettre à jour les champs requis
                document.querySelectorAll('#carte-form input').forEach(input => input.required = false);
                document.querySelectorAll('#momo-form input, #momo-form select').forEach(input => input.required = true);
            }
        }

        // Initialiser l'affichage au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const modePaiement = document.querySelector('input[name="mode_paiement"]:checked').value;
            togglePaiementForm(modePaiement);
            updateMontant();

            // Ajouter la validation du formulaire
            document.getElementById('paiement-form').addEventListener('submit', function(e) {
                const modePaiement = document.querySelector('input[name="mode_paiement"]:checked').value;
                let isValid = true;

                if (modePaiement === 'carte') {
                    const numeroCarte = document.getElementById('numero_carte').value;
                    const dateExpiration = document.getElementById('date_expiration').value;
                    const cvv = document.getElementById('cvv').value;
                    const titulaire = document.getElementById('titulaire').value;

                    if (numeroCarte.length !== 16) {
                        alert('Le numéro de carte doit contenir 16 chiffres');
                        isValid = false;
                    }
                    if (!dateExpiration.match(/^(0[1-9]|1[0-2])\/([0-9]{2})$/)) {
                        alert('Format de date d\'expiration invalide (MM/AA)');
                        isValid = false;
                    }
                    if (cvv.length !== 3) {
                        alert('Le code CVV doit contenir 3 chiffres');
                        isValid = false;
                    }
                    if (!titulaire) {
                        alert('Le nom du titulaire est requis');
                        isValid = false;
                    }
                } else {
                    const operateur = document.getElementById('operateur').value;
                    const numeroMomo = document.getElementById('numero_momo').value;

                    if (!operateur) {
                        alert('Veuillez sélectionner un opérateur');
                        isValid = false;
                    }
                    if (numeroMomo.length !== 10) {
                        alert('Le numéro Mobile Money doit contenir 10 chiffres');
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 