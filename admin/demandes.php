<?php
require_once 'includes/header.php';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $demande_id = filter_input(INPUT_POST, 'demande_id', FILTER_VALIDATE_INT);
        if (!$demande_id) {
            throw new Exception("ID de demande invalide");
        }

        switch ($_POST['action']) {
            case 'valider':
                $stmt = $conn->prepare("UPDATE demandes SET 
                    statut = 'valide', 
                    date_traitement = NOW(), 
                    agent_traitant_id = ? 
                    WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $demande_id]);
                $_SESSION['success'] = "La demande a été validée avec succès.";
                break;
            
            case 'rejeter':
                $motif = filter_input(INPUT_POST, 'motif_rejet', FILTER_SANITIZE_STRING);
                if (empty($motif)) {
                    throw new Exception("Le motif de rejet est obligatoire");
                }
                $stmt = $conn->prepare("UPDATE demandes SET 
                    statut = 'rejete', 
                    date_traitement = NOW(), 
                    motif_rejet = ?, 
                    agent_traitant_id = ? 
                    WHERE id = ?");
                $stmt->execute([$motif, $_SESSION['user_id'], $demande_id]);
                $_SESSION['success'] = "La demande a été rejetée.";
                break;

            case 'modifier':
                $type_acte = filter_input(INPUT_POST, 'type_acte', FILTER_SANITIZE_STRING);
                $numero_acte = filter_input(INPUT_POST, 'numero_acte', FILTER_SANITIZE_STRING);
                $commentaire = filter_input(INPUT_POST, 'commentaire', FILTER_SANITIZE_STRING);
                $nombre_copies = filter_input(INPUT_POST, 'nombre_copies', FILTER_VALIDATE_INT);
                $montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);

                if (!$type_acte || !$numero_acte || !$nombre_copies || !$montant) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis");
                }

                $stmt = $conn->prepare("UPDATE demandes SET 
                    type_acte = ?, 
                    numero_acte = ?, 
                    commentaire = ?, 
                    nombre_copies = ?, 
                    montant = ? 
                    WHERE id = ?");
                $stmt->execute([$type_acte, $numero_acte, $commentaire, $nombre_copies, $montant, $demande_id]);
                $_SESSION['success'] = "Les informations de la demande ont été modifiées avec succès.";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: demandes.php');
    exit();
}

// Traitement des filtres
$type = isset($_GET['type']) ? $_GET['type'] : '';
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

// Construction de la requête SQL
$sql = "SELECT d.*, 
        CASE 
            WHEN d.type_acte = 'naissance' THEN an.nom
            WHEN d.type_acte = 'mariage' THEN am.nom_epoux
            WHEN d.type_acte = 'deces' THEN ad.nom_defunt
        END as nom,
        CASE 
            WHEN d.type_acte = 'naissance' THEN an.prenoms
            WHEN d.type_acte = 'mariage' THEN am.prenoms_epoux
            WHEN d.type_acte = 'deces' THEN ad.prenoms_defunt
        END as prenoms
        FROM demandes d
        LEFT JOIN actes_naissance an ON d.numero_acte = an.numero_acte AND d.type_acte = 'naissance'
        LEFT JOIN actes_mariage am ON d.numero_acte = am.numero_acte AND d.type_acte = 'mariage'
        LEFT JOIN actes_deces ad ON d.numero_acte = ad.numero_acte AND d.type_acte = 'deces'
        WHERE 1=1";

$params = [];

if ($type) {
    $sql .= " AND d.type_acte = ?";
    $params[] = $type;
}

if ($statut) {
    $sql .= " AND d.statut = ?";
    $params[] = $statut;
}

if ($date_debut) {
    $sql .= " AND d.date_demande >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $sql .= " AND d.date_demande <= ?";
    $params[] = $date_fin;
}

$sql .= " ORDER BY d.date_demande DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des demandes : " . $e->getMessage();
    $demandes = [];
}

// Inclure la sidebar
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-tasks me-2"></i>Gestion des demandes</h1>
        <div class="text-muted">
            <i class="fas fa-calendar me-1"></i>
            <?php echo htmlspecialchars(date('d/m/Y')); ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card shadow mb-4">
        <div class="card-body">
            
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Type d'acte</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="naissance" <?php echo $type === 'naissance' ? 'selected' : ''; ?>>Acte de naissance</option>
                        <option value="mariage" <?php echo $type === 'mariage' ? 'selected' : ''; ?>>Acte de mariage</option>
                        <option value="deces" <?php echo $type === 'deces' ? 'selected' : ''; ?>>Acte de décès</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?php echo $statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="en_cours" <?php echo $statut === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="valide" <?php echo $statut === 'valide' ? 'selected' : ''; ?>>Validée</option>
                        <option value="rejete" <?php echo $statut === 'rejete' ? 'selected' : ''; ?>>Rejetée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date début</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?php echo $date_debut; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?php echo $date_fin; ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>
                        Filtrer
                    </button>
                    <a href="demandes.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i>
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des demandes -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>Prénoms</th>
                            <th>Date demande</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($demandes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted d-block mb-2"></i>
                                    Aucune demande trouvée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($demandes as $demande): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($demande['id']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo htmlspecialchars($demande['type_acte']) === 'naissance' ? 'primary' : 
                                                (htmlspecialchars($demande['type_acte']) === 'mariage' ? 'success' : 'danger'); 
                                        ?>">
                                            <i class="fas fa-<?php 
                                                echo htmlspecialchars($demande['type_acte']) === 'naissance' ? 'baby' : 
                                                    (htmlspecialchars($demande['type_acte']) === 'mariage' ? 'heart' : 'skull'); 
                                            ?> me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst($demande['type_acte'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($demande['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($demande['prenoms']); ?></td>
                                    <td>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($demande['date_demande']))); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo htmlspecialchars($demande['statut']) === 'en_attente' ? 'warning' : 
                                                (htmlspecialchars($demande['statut']) === 'en_cours' ? 'info' : 
                                                (htmlspecialchars($demande['statut']) === 'valide' ? 'success' : 'danger')); 
                                        ?>">
                                            <i class="fas fa-<?php 
                                                echo htmlspecialchars($demande['statut']) === 'en_attente' ? 'clock' : 
                                                    (htmlspecialchars($demande['statut']) === 'en_cours' ? 'spinner' : 
                                                    (htmlspecialchars($demande['statut']) === 'valide' ? 'check' : 'times')); 
                                            ?> me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst($demande['statut'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="voir_demande.php?id=<?php echo htmlspecialchars($demande['id']); ?>" 
                                               class="btn btn-sm btn-primary" 
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (htmlspecialchars($demande['statut']) === 'en_attente' || htmlspecialchars($demande['statut']) === 'en_cours'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#validerModal<?php echo htmlspecialchars($demande['id']); ?>"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Valider la demande">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejeterModal<?php echo htmlspecialchars($demande['id']); ?>"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Rejeter la demande">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modifierModal<?php echo htmlspecialchars($demande['id']); ?>"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Modifier la demande">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (htmlspecialchars($demande['statut']) === 'valide'): ?>
                                                <a href="../download_pdf.php?type=<?php echo htmlspecialchars($demande['type_acte']); ?>&id=<?php echo htmlspecialchars($demande['id']); ?>" 
                                                   class="btn btn-sm btn-success"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   title="Télécharger le PDF">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Modal de validation -->
                                        <div class="modal fade" id="validerModal<?php echo htmlspecialchars($demande['id']); ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Valider la demande</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Êtes-vous sûr de vouloir valider cette demande ?</p>
                                                        <p><strong>Type d'acte :</strong> <?php echo htmlspecialchars(ucfirst($demande['type_acte'])); ?></p>
                                                        <p><strong>Nom :</strong> <?php echo htmlspecialchars($demande['nom']); ?></p>
                                                        <p><strong>Prénoms :</strong> <?php echo htmlspecialchars($demande['prenoms']); ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form method="POST">
                                                            <input type="hidden" name="demande_id" value="<?php echo htmlspecialchars($demande['id']); ?>">
                                                            <input type="hidden" name="action" value="valider">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-success">Valider</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal de rejet -->
                                        <div class="modal fade" id="rejeterModal<?php echo htmlspecialchars($demande['id']); ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Rejeter la demande</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST">
                                                            <input type="hidden" name="demande_id" value="<?php echo htmlspecialchars($demande['id']); ?>">
                                                            <input type="hidden" name="action" value="rejeter">
                                                            
                                                            <div class="mb-3">
                                                                <label for="motif_rejet" class="form-label">Motif du rejet</label>
                                                                <textarea class="form-control" id="motif_rejet" name="motif_rejet" rows="3" required 
                                                                          placeholder="Veuillez indiquer le motif du rejet..."></textarea>
                                                            </div>

                                                            <div class="text-end">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-danger">Rejeter</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal de modification -->
                                        <div class="modal fade" id="modifierModal<?php echo htmlspecialchars($demande['id']); ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Modifier la demande</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST">
                                                            <input type="hidden" name="demande_id" value="<?php echo htmlspecialchars($demande['id']); ?>">
                                                            <input type="hidden" name="action" value="modifier">
                                                            
                                                            <div class="mb-3">
                                                                <label for="type_acte" class="form-label">Type d'acte</label>
                                                                <select class="form-select" id="type_acte" name="type_acte" required>
                                                                    <option value="naissance" <?php echo htmlspecialchars($demande['type_acte']) === 'naissance' ? 'selected' : ''; ?>>Acte de naissance</option>
                                                                    <option value="mariage" <?php echo htmlspecialchars($demande['type_acte']) === 'mariage' ? 'selected' : ''; ?>>Acte de mariage</option>
                                                                    <option value="deces" <?php echo htmlspecialchars($demande['type_acte']) === 'deces' ? 'selected' : ''; ?>>Acte de décès</option>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="numero_acte" class="form-label">Numéro d'acte</label>
                                                                <input type="text" class="form-control" id="numero_acte" name="numero_acte" 
                                                                       value="<?php echo htmlspecialchars($demande['numero_acte']); ?>" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="nombre_copies" class="form-label">Nombre de copies</label>
                                                                <input type="number" class="form-control" id="nombre_copies" name="nombre_copies" 
                                                                       value="<?php echo htmlspecialchars($demande['nombre_copies']); ?>" min="1" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="montant" class="form-label">Montant</label>
                                                                <input type="number" class="form-control" id="montant" name="montant" 
                                                                       value="<?php echo htmlspecialchars($demande['montant']); ?>" step="0.01" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="commentaire" class="form-label">Commentaire</label>
                                                                <textarea class="form-control" id="commentaire" name="commentaire" rows="3"
                                                                          placeholder="Ajouter un commentaire..."><?php echo htmlspecialchars($demande['commentaire'] ?? ''); ?></textarea>
                                                            </div>

                                                            <div class="text-end">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Style pour les boutons d'action */
    .btn-group .btn {
        padding: 0.4rem 0.6rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        margin: 0 3px; /* Ajout d'espacement entre les boutons */
    }

    .btn-group {
        display: flex;
        gap: 8px; /* Espacement uniforme entre les boutons */
        flex-wrap: nowrap;
    }

    .btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .btn-group .btn i {
        font-size: 1rem;
        transition: all 0.3s ease;
        padding: 0 2px; /* Espacement interne pour les icônes */
    }

    /* Styles spécifiques pour chaque type de bouton */
    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
        min-width: 38px; /* Largeur minimale pour uniformité */
    }

    .btn-success {
        background-color: #198754;
        border-color: #198754;
        min-width: 38px;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        min-width: 38px;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #000;
        min-width: 38px;
    }

    /* Style pour les badges de statut */
    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    .badge i {
        margin-right: 0.3rem;
    }

    /* Style pour les badges de type d'acte */
    .badge.bg-primary {
        background-color: #0d6efd !important;
    }

    .badge.bg-success {
        background-color: #198754 !important;
    }

    .badge.bg-danger {
        background-color: #dc3545 !important;
    }

    /* Animation pour les icônes */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .btn-group .btn:hover i {
        animation: pulse 0.5s ease;
    }

    /* Style pour les tooltips */
    .tooltip {
        font-size: 0.875rem;
    }

    .tooltip-inner {
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
    }
</style>

<script>
    // Initialisation des tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        })
    })
</script>

<?php require_once 'includes/footer.php'; ?> 