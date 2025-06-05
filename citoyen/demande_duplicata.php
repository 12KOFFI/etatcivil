<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citoyen') {
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'ID de la demande originale est passé
$demande_id = isset($_GET['demande_id']) ? intval($_GET['demande_id']) : 0;

// Récupérer les informations de la demande originale si disponible
if ($demande_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM demandes WHERE id = ? AND utilisateur_id = ? AND statut = 'valide'");
    $stmt->execute([$demande_id, $_SESSION['user_id']]);
    $demande_originale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande_originale) {
        $_SESSION['error'] = "Demande introuvable ou non autorisée";
        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de duplicata - État Civil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #FF8C00;">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Espace Citoyen</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-copy me-2"></i>Demande de duplicata</h4>
                    </div>
                    <div class="card-body">
                        <form id="duplicataForm" action="process_duplicata.php" method="POST" onsubmit="return validateForm()">
                            <?php if (isset($demande_originale)): ?>
                                <input type="hidden" name="demande_originale_id" value="<?php echo $demande_id; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="numero_acte" class="form-label">Numéro de l'acte</label>
                                <input type="text" class="form-control" id="numero_acte" name="numero_acte" required
                                       value="<?php echo isset($demande_originale) ? htmlspecialchars($demande_originale['numero_acte']) : ''; ?>"
                                       <?php echo isset($demande_originale) ? 'readonly' : ''; ?>>
                                <div class="invalid-feedback">Veuillez entrer un numéro d'acte valide.</div>
                            </div>

                            <div class="mb-3">
                                <label for="type_acte" class="form-label">Type d'acte</label>
                                <?php if (isset($demande_originale)): ?>
                                    <input type="hidden" name="type_acte" value="<?php echo htmlspecialchars($demande_originale['type_acte']); ?>">
                                    <input type="text" class="form-control" value="<?php echo ucfirst($demande_originale['type_acte']); ?>" readonly>
                                <?php else: ?>
                                    <select class="form-select" id="type_acte" name="type_acte" required>
                                        <option value="">Sélectionnez un type</option>
                                        <option value="naissance">Acte de naissance</option>
                                        <option value="mariage">Acte de mariage</option>
                                        <option value="deces">Acte de décès</option>
                                    </select>
                                    <div class="invalid-feedback">Veuillez sélectionner un type d'acte.</div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="motif" class="form-label">Motif de la demande</label>
                                <select class="form-select" id="motif" name="motif" required>
                                    <option value="">Sélectionnez un motif</option>
                                    <option value="perte">Perte</option>
                                    <option value="vol">Vol</option>
                                    <option value="usure">Usure</option>
                                    <option value="autre">Autre</option>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner un motif.</div>
                            </div>

                            <div id="autre_motif_div" class="mb-3 d-none">
                                <label for="autre_motif" class="form-label">Précisez le motif</label>
                                <input type="text" class="form-control" id="autre_motif" name="autre_motif"
                                       minlength="5" maxlength="100">
                                <div class="invalid-feedback">Veuillez préciser le motif (5 caractères minimum).</div>
                            </div>

                            <div class="mb-3">
                                <label for="nombre_copies" class="form-label">Nombre de copies souhaitées</label>
                                <input type="number" class="form-control" id="nombre_copies" name="nombre_copies" 
                                       min="1" max="10" required value="1">
                                <div class="invalid-feedback">Veuillez entrer un nombre entre 1 et 10.</div>
                            </div>

                            <div class="text-end">
                                <a href="dashboard.php" class="btn btn-secondary me-2">Annuler</a>
                                <button type="submit" class="btn btn-primary">Continuer vers le paiement</button>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('duplicataForm');
        const motifSelect = document.getElementById('motif');
        const autreMotifDiv = document.getElementById('autre_motif_div');
        const autreMotifInput = document.getElementById('autre_motif');
        const numeroActeInput = document.getElementById('numero_acte');
        const typeActeSelect = document.getElementById('type_acte');

        // Gestion du motif "Autre"
        motifSelect.addEventListener('change', function() {
            if (this.value === 'autre') {
                autreMotifDiv.classList.remove('d-none');
                autreMotifInput.required = true;
            } else {
                autreMotifDiv.classList.add('d-none');
                autreMotifInput.required = false;
                autreMotifInput.value = '';
            }
        });

        // Auto-remplissage lors de la saisie du numéro d'acte
        if (!document.querySelector('input[name="demande_originale_id"]')) {
            numeroActeInput.addEventListener('blur', function() {
                if (this.value) {
                    fetch(`get_acte_info.php?numero_acte=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                typeActeSelect.value = data.type_acte;
                                typeActeSelect.classList.remove('is-invalid');
                            } else {
                                this.classList.add('is-invalid');
                                typeActeSelect.value = '';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            this.classList.add('is-invalid');
                        });
                }
            });
        }
    });

    // Validation du formulaire
    function validateForm() {
        const form = document.getElementById('duplicataForm');
        const motif = document.getElementById('motif').value;
        const autreMotif = document.getElementById('autre_motif');
        let isValid = true;

        // Réinitialiser les messages d'erreur
        form.querySelectorAll('.is-invalid').forEach(element => {
            element.classList.remove('is-invalid');
        });

        // Validation du motif "Autre"
        if (motif === 'autre') {
            if (!autreMotif.value || autreMotif.value.length < 5) {
                autreMotif.classList.add('is-invalid');
                isValid = false;
            }
        }

        // Validation du nombre de copies
        const nombreCopies = document.getElementById('nombre_copies');
        if (nombreCopies.value < 1 || nombreCopies.value > 10) {
            nombreCopies.classList.add('is-invalid');
            isValid = false;
        }

        return isValid;
    }
    </script>
</body>
</html> 