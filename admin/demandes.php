<?php
require_once 'includes/header.php';

// Ajouter cette fonction helper en haut du fichier, juste après require_once 'includes/header.php';
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

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
                $type_acte = htmlspecialchars(trim($_POST['type_acte'] ?? ''), ENT_QUOTES, 'UTF-8');
                $numero_acte = htmlspecialchars(trim($_POST['numero_acte'] ?? ''), ENT_QUOTES, 'UTF-8');
                $commentaire = htmlspecialchars(trim($_POST['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8');
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
    
    if (!headers_sent()) {
        header('Location: demandes.php');
        exit();
    } else {
        echo '<script>window.location.href = "demandes.php";</script>';
        exit();
    }
}

// Traitement des filtres
$type = isset($_GET['type']) ? $_GET['type'] : '';
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

// Construction de la requête SQL
$sql = "SELECT d.* FROM demandes d WHERE 1=1 AND (
    (d.type_acte = 'naissance' AND EXISTS (SELECT 1 FROM actes_naissance an WHERE d.numero_acte = an.numero_acte)) OR
    (d.type_acte = 'mariage' AND EXISTS (SELECT 1 FROM actes_mariage am WHERE d.numero_acte = am.numero_acte)) OR
    (d.type_acte = 'deces' AND EXISTS (SELECT 1 FROM actes_deces ad WHERE d.numero_acte = ad.numero_acte))
)";

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

// Ajout d'une sous-requête pour récupérer les noms et prénoms
$sql = "SELECT d.*,
    CASE 
        WHEN d.type_acte = 'naissance' THEN (SELECT nom FROM actes_naissance WHERE numero_acte = d.numero_acte)
        WHEN d.type_acte = 'mariage' THEN (SELECT nom_epoux FROM actes_mariage WHERE numero_acte = d.numero_acte)
        WHEN d.type_acte = 'deces' THEN (SELECT nom_defunt FROM actes_deces WHERE numero_acte = d.numero_acte)
    END as nom,
    CASE 
        WHEN d.type_acte = 'naissance' THEN (SELECT prenoms FROM actes_naissance WHERE numero_acte = d.numero_acte)
        WHEN d.type_acte = 'mariage' THEN (SELECT prenoms_epoux FROM actes_mariage WHERE numero_acte = d.numero_acte)
        WHEN d.type_acte = 'deces' THEN (SELECT prenoms_defunt FROM actes_deces WHERE numero_acte = d.numero_acte)
    END as prenoms
FROM (" . $sql . ") d
ORDER BY d.date_demande DESC";

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
            <?php echo safe_html(date('d/m/Y')); ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo safe_html($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php 
            echo safe_html($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo safe_html($error); ?>
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

    <!-- Tableau des demandes -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>N° Demande</th>
                            <th>Type de demande</th>
                            <th>Type d'acte</th>
                            <th>N° Acte</th>
                            <th>Nom et Prénoms</th>
                            <th>Date demande</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $demande): ?>
                            <tr>
                                <td><?php echo safe_html($demande['numero_demande']); ?></td>
                                <td>
                                    <?php if ($demande['type_demande'] === 'duplicata'): ?>
                                        <span class="badge bg-info">Duplicata</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Standard</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo safe_html(ucfirst($demande['type_acte'])); ?></td>
                                <td><?php echo safe_html($demande['numero_acte']); ?></td>
                                <td><?php echo safe_html($demande['nom'] . ' ' . $demande['prenoms']); ?></td>
                                <td><?php echo safe_html(date('d/m/Y', strtotime($demande['date_demande']))); ?></td>
                                <td>
                                    <?php
                                    $statut_class = [
                                        'en_attente' => 'bg-warning',
                                        'en_cours' => 'bg-primary',
                                        'valide' => 'bg-success',
                                        'rejete' => 'bg-danger'
                                    ];
                                    $statut_text = [
                                        'en_attente' => 'En attente',
                                        'en_cours' => 'En cours',
                                        'valide' => 'Validée',
                                        'rejete' => 'Rejetée'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statut_class[$demande['statut']]; ?>">
                                        <?php echo $statut_text[$demande['statut']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="voir_demande.php?id=<?php echo safe_html($demande['id']); ?>" 
                                           class="btn btn-sm btn-primary" 
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (safe_html($demande['statut']) === 'en_attente' || safe_html($demande['statut']) === 'en_cours'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#validerModal<?php echo safe_html($demande['id']); ?>"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Valider la demande">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#rejeterModal<?php echo safe_html($demande['id']); ?>"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Rejeter la demande">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modifierModal<?php echo safe_html($demande['id']); ?>"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Modifier la demande">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (safe_html($demande['statut']) === 'valide'): ?>
                                            <a href="../download_pdf.php?type=<?php echo safe_html($demande['type_acte']); ?>&id=<?php echo safe_html($demande['id']); ?>" 
                                               class="btn btn-sm btn-success"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Télécharger le PDF">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Modal de validation -->
                                    <div class="modal fade" id="validerModal<?php echo safe_html($demande['id']); ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Valider la demande</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Êtes-vous sûr de vouloir valider cette demande ?</p>
                                                    <p><strong>Type d'acte :</strong> <?php echo safe_html(ucfirst($demande['type_acte'])); ?></p>
                                                    <p><strong>Nom :</strong> <?php echo safe_html($demande['nom']); ?></p>
                                                    <p><strong>Prénoms :</strong> <?php echo safe_html($demande['prenoms']); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST">
                                                        <input type="hidden" name="demande_id" value="<?php echo safe_html($demande['id']); ?>">
                                                        <input type="hidden" name="action" value="valider">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-success">Valider</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal de rejet -->
                                    <div class="modal fade" id="rejeterModal<?php echo safe_html($demande['id']); ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Rejeter la demande</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="demande_id" value="<?php echo safe_html($demande['id']); ?>">
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
                                    <div class="modal fade" id="modifierModal<?php echo safe_html($demande['id']); ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Modifier la demande</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="demande_id" value="<?php echo safe_html($demande['id']); ?>">
                                                        <input type="hidden" name="action" value="modifier">
                                                        
                                                        <div class="mb-3">
                                                            <label for="type_acte" class="form-label">Type d'acte</label>
                                                            <select class="form-select" id="type_acte" name="type_acte" required>
                                                                <option value="naissance" <?php echo safe_html($demande['type_acte']) === 'naissance' ? 'selected' : ''; ?>>Acte de naissance</option>
                                                                <option value="mariage" <?php echo safe_html($demande['type_acte']) === 'mariage' ? 'selected' : ''; ?>>Acte de mariage</option>
                                                                <option value="deces" <?php echo safe_html($demande['type_acte']) === 'deces' ? 'selected' : ''; ?>>Acte de décès</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="numero_acte" class="form-label">Numéro d'acte</label>
                                                            <input type="text" class="form-control" id="numero_acte" name="numero_acte" 
                                                                   value="<?php echo safe_html($demande['numero_acte']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="nombre_copies" class="form-label">Nombre de copies</label>
                                                            <input type="number" class="form-control" id="nombre_copies" name="nombre_copies" 
                                                                   value="<?php echo safe_html($demande['nombre_copies']); ?>" min="1" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="montant" class="form-label">Montant</label>
                                                            <input type="number" class="form-control" id="montant" name="montant" 
                                                                   value="<?php echo safe_html($demande['montant']); ?>" step="0.01" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="commentaire" class="form-label">Commentaire</label>
                                                            <textarea class="form-control" id="commentaire" name="commentaire" rows="3"
                                                                      placeholder="Ajouter un commentaire..."><?php echo safe_html($demande['commentaire'] ?? ''); ?></textarea>
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