<?php
require_once 'includes/header.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de demande non spécifié.";
    header('Location: demandes.php');
    exit();
}

$demande_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$demande_id) {
    $_SESSION['error'] = "ID de demande invalide.";
    header('Location: demandes.php');
    exit();
}

try {
    // Récupérer les informations de la demande avec les détails de l'acte
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
            END as prenoms,
            CASE 
                WHEN d.type_acte = 'naissance' THEN an.date_naissance
                WHEN d.type_acte = 'mariage' THEN am.date_mariage
                WHEN d.type_acte = 'deces' THEN ad.date_deces
            END as date_evenement,
            CASE 
                WHEN d.type_acte = 'naissance' THEN an.lieu_naissance
                WHEN d.type_acte = 'mariage' THEN am.lieu_mariage
                WHEN d.type_acte = 'deces' THEN ad.lieu_deces
            END as lieu_evenement,
            an.*, am.*, ad.*,
            u.nom as nom_utilisateur, u.prenoms as prenoms_utilisateur, u.email as email_utilisateur
            FROM demandes d
            LEFT JOIN actes_naissance an ON d.numero_acte = an.numero_acte AND d.type_acte = 'naissance'
            LEFT JOIN actes_mariage am ON d.numero_acte = am.numero_acte AND d.type_acte = 'mariage'
            LEFT JOIN actes_deces ad ON d.numero_acte = ad.numero_acte AND d.type_acte = 'deces'
            LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
            WHERE d.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$demande_id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        throw new Exception("Demande non trouvée.");
    }

    // Récupérer les documents associés
    $stmt = $conn->prepare("SELECT * FROM documents WHERE type_acte = ? AND numero_acte = ?");
    $stmt->execute([$demande['type_acte'], $demande['numero_acte']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: demandes.php');
    exit();
}

// Traitement de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
                    $nouveau_statut = $_POST['nouveau_statut'];
                    $commentaire = $_POST['commentaire'];

    try {
        $stmt = $conn->prepare("UPDATE demandes SET statut = ?, commentaire = ? WHERE id = ?");
        if ($stmt->execute([$nouveau_statut, $commentaire, $demande_id])) {
            $_SESSION['success'] = "Le statut de la demande a été mis à jour avec succès.";
            header("Location: voir_demande.php?id=" . $demande_id);
            exit();
        } else {
            $error = "Erreur lors de la mise à jour du statut.";
        }
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Inclure la sidebar
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-text me-2"></i>Détails de la demande</h1>
        <a href="demandes.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Retour à la liste
        </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="row">
        <!-- Informations de la demande -->
        <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Informations de la demande
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Numéro de demande :</label>
                        <p class="mb-0"><?php echo htmlspecialchars($demande['numero_demande']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Type d'acte :</label>
                        <span class="badge bg-<?php 
                            echo $demande['type_acte'] === 'naissance' ? 'primary' : 
                                ($demande['type_acte'] === 'mariage' ? 'success' : 'danger'); 
                        ?>">
                            <i class="bi bi-<?php 
                                echo $demande['type_acte'] === 'naissance' ? 'person-plus' : 
                                    ($demande['type_acte'] === 'mariage' ? 'heart' : 'person-x'); 
                            ?> me-1"></i>
                            <?php echo ucfirst($demande['type_acte'] ?? ''); ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Date de la demande :</label>
                        <p class="mb-0">
                            <i class="bi bi-calendar2 me-1"></i>
                            <?php echo date('d/m/Y', strtotime($demande['date_demande'])); ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Statut :</label>
                        <span class="badge bg-<?php 
                            echo $demande['statut'] === 'en_attente' ? 'warning' : 
                                ($demande['statut'] === 'en_cours' ? 'info' : 
                                ($demande['statut'] === 'valide' ? 'success' : 'danger')); 
                        ?>">
                            <i class="bi bi-<?php 
                                echo $demande['statut'] === 'en_attente' ? 'hourglass-split' : 
                                    ($demande['statut'] === 'en_cours' ? 'arrow-repeat' : 
                                    ($demande['statut'] === 'valide' ? 'check-circle' : 'x-circle')); 
                            ?> me-1"></i>
                            <?php echo ucfirst($demande['statut'] ?? ''); ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Paiement effectué :</label>
                        <span class="badge bg-<?php echo $demande['paiement_effectue'] ? 'success' : 'danger'; ?>">
                            <i class="bi bi-<?php echo $demande['paiement_effectue'] ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                            <?php echo $demande['paiement_effectue'] ? 'Oui' : 'Non'; ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Montant :</label>
                        <p class="mb-0"><?php echo number_format($demande['montant'], 0, ',', ' '); ?> FCFA</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de copies :</label>
                        <p class="mb-0"><?php echo $demande['nombre_copies']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Informations du demandeur -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person me-2"></i>
                        Informations du demandeur
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom :</label>
                        <p class="mb-0"><?php echo htmlspecialchars($demande['nom_utilisateur']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Prénoms :</label>
                        <p class="mb-0"><?php echo htmlspecialchars($demande['prenoms_utilisateur']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email :</label>
                        <p class="mb-0"><?php echo htmlspecialchars($demande['email_utilisateur']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails de l'acte -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-text me-2"></i>
                        Détails de l'acte
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($demande['type_acte'] === 'naissance'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom de l'enfant :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['nom'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prénoms de l'enfant :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['prenoms'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date de naissance :</label>
                            <p class="mb-0"><?php echo $demande['date_evenement'] ? date('d/m/Y', strtotime($demande['date_evenement'])) : ''; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lieu de naissance :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lieu_evenement'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom du père :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['nom_pere'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom de la mère :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['nom_mere'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Déclarant :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['declarant'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lien avec le déclarant :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lien_declarant'] ?? ''); ?></p>
                        </div>
                    <?php elseif ($demande['type_acte'] === 'mariage'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date du mariage :</label>
                            <p class="mb-0"><?php echo $demande['date_evenement'] ? date('d/m/Y', strtotime($demande['date_evenement'])) : ''; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lieu du mariage :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lieu_evenement'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom de l'époux :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['nom_epoux'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prénoms de l'époux :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['prenoms_epoux'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date de naissance de l'époux :</label>
                            <p class="mb-0"><?php echo $demande['date_naissance_epoux'] ? date('d/m/Y', strtotime($demande['date_naissance_epoux'])) : ''; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lieu de naissance de l'époux :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lieu_naissance_epoux'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom de l'épouse :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['nom_epouse'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prénoms de l'épouse :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['prenoms_epouse'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date de naissance de l'épouse :</label>
                            <p class="mb-0"><?php echo $demande['date_naissance_epouse'] ? date('d/m/Y', strtotime($demande['date_naissance_epouse'])) : ''; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lieu de naissance de l'épouse :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lieu_naissance_epouse'] ?? ''); ?></p>
                        </div>
                    <?php elseif ($demande['type_acte'] === 'deces'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom du défunt :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['nom'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prénoms du défunt :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['prenoms'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date du décès :</label>
                            <p class="mb-0"><?php echo $demande['date_evenement'] ? date('d/m/Y', strtotime($demande['date_evenement'])) : ''; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lieu du décès :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lieu_evenement'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date de naissance du défunt :</label>
                            <p class="mb-0"><?php echo $demande['date_naissance_defunt'] ? date('d/m/Y', strtotime($demande['date_naissance_defunt'])) : ''; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lieu de naissance du défunt :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lieu_naissance_defunt'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cause du décès :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['cause_deces'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Déclarant :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['declarant'] ?? ''); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lien avec le défunt :</label>
                            <p class="mb-0"><?php echo htmlspecialchars($demande['lien_declarant'] ?? ''); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents -->
    <div class="card shadow mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-file-earmark me-2"></i>
                Documents
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                    Aucun document associé
                </div>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($documents as $document): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    <?php echo htmlspecialchars($document['nom_fichier']); ?>
                                </div>
                                <div class="btn-group">
                                    <a href="../uploads/<?php echo $document['chemin_fichier']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       target="_blank"
                                       title="Voir le document">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="../uploads/<?php echo $document['chemin_fichier']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       download
                                       title="Télécharger">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <?php if ($demande['statut'] === 'en_attente'): ?>
        <div class="card shadow">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="demandes.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir valider cette demande ?');">
                        <input type="hidden" name="demande_id" value="<?php echo $demande['id']; ?>">
                        <input type="hidden" name="action" value="valider">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>
                            Valider la demande
                        </button>
                    </form>
                    <form method="POST" action="demandes.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette demande ?');">
                        <input type="hidden" name="demande_id" value="<?php echo $demande['id']; ?>">
                        <input type="hidden" name="action" value="rejeter">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-lg me-1"></i>
                            Rejeter la demande
                        </button>
                </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 