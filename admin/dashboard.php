<?php
require_once 'includes/header.php';

// Récupérer les statistiques
try {
    // Statistiques générales
    $stmt = $conn->query("SELECT 
        (SELECT COUNT(*) FROM demandes WHERE type_acte = 'naissance') as total_naissances,
        (SELECT COUNT(*) FROM demandes WHERE type_acte = 'mariage') as total_mariages,
        (SELECT COUNT(*) FROM demandes WHERE type_acte = 'deces') as total_deces,
        (SELECT COUNT(*) FROM demandes WHERE statut = 'en_attente') as demandes_en_attente,
        (SELECT COUNT(*) FROM demandes WHERE statut = 'en_cours') as demandes_en_cours,
        (SELECT COUNT(*) FROM demandes WHERE statut = 'valide') as demandes_validees,
        (SELECT COUNT(*) FROM demandes WHERE statut = 'rejete') as demandes_rejetees");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Demandes récentes (toutes confondues)
    $stmt = $conn->prepare("
        SELECT 
            id,
            type_acte as type,
            numero_acte,
            date_demande,
            statut
        FROM demandes 
        WHERE statut = 'en_attente'
        ORDER BY date_demande DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $demandes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données : " . $e->getMessage();
}

// Inclure la sidebar
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</h1>
        <div class="text-muted">
            <i class="bi bi-calendar3 me-1"></i>
            <?php echo date('d/m/Y'); ?>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Actes de naissance</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['total_naissances']; ?></h2>
                        </div>
                        <i class="bi bi-person-plus-fill fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Actes de mariage</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['total_mariages']; ?></h2>
                        </div>
                        <i class="bi bi-heart-fill fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Actes de décès</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['total_deces']; ?></h2>
                        </div>
                        <i class="bi bi-person-x-fill fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">En attente</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['demandes_en_attente']; ?></h2>
                        </div>
                        <i class="bi bi-clock-fill fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- État des demandes -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        État des demandes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning rounded-circle p-2 me-3">
                                    <i class="bi bi-hourglass-split text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">En attente</h6>
                                    <h4 class="mb-0"><?php echo $stats['demandes_en_attente']; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info rounded-circle p-2 me-3">
                                    <i class="bi bi-arrow-repeat text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">En cours</h6>
                                    <h4 class="mb-0"><?php echo $stats['demandes_en_cours']; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success rounded-circle p-2 me-3">
                                    <i class="bi bi-check-circle text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Validées</h6>
                                    <h4 class="mb-0"><?php echo $stats['demandes_validees']; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-danger rounded-circle p-2 me-3">
                                    <i class="bi bi-x-circle text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Rejetées</h6>
                                    <h4 class="mb-0"><?php echo $stats['demandes_rejetees']; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demandes récentes -->
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-clock-history me-2"></i>
                Demandes récentes en attente
            </h5>
            <a href="demandes.php" class="btn btn-primary btn-sm">
                <i class="bi bi-list-ul me-1"></i>
                Voir toutes les demandes
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Numéro d'acte</th>
                            <th>Date de demande</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($demandes_recentes)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                    Aucune demande en attente
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($demandes_recentes as $demande): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $demande['type'] === 'naissance' ? 'primary' : 
                                                ($demande['type'] === 'mariage' ? 'success' : 'danger'); 
                                        ?>">
                                            <i class="bi bi-<?php 
                                                echo $demande['type'] === 'naissance' ? 'person-plus' : 
                                                    ($demande['type'] === 'mariage' ? 'heart' : 'person-x'); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst($demande['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($demande['numero_acte']); ?></td>
                                    <td>
                                        <i class="bi bi-calendar2 me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($demande['date_demande'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-hourglass-split me-1"></i>
                                            En attente
                                        </span>
                                    </td>
                                    <td>
                                        <a href="voir_demande.php?id=<?php echo $demande['id']; ?>" 
                                           class="btn btn-sm btn-primary" 
                                           title="Voir les détails">
                                            <i class="bi bi-eye"></i>
                                        </a>
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

<?php require_once 'includes/footer.php'; ?> 