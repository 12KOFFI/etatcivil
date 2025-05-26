<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Récupération des paramètres de filtrage
$periode = isset($_GET['periode']) ? htmlspecialchars($_GET['periode']) : 'mois';
$date_debut = isset($_GET['date_debut']) ? htmlspecialchars($_GET['date_debut']) : date('Y-m-d', strtotime('-1 month'));
$date_fin = isset($_GET['date_fin']) ? htmlspecialchars($_GET['date_fin']) : date('Y-m-d');

// Validation des dates
if (strtotime($date_debut) > strtotime($date_fin)) {
    $date_debut = $date_fin;
}

// Limiter la période à 1 an maximum
$max_date = date('Y-m-d', strtotime('-1 year'));
if (strtotime($date_debut) < strtotime($max_date)) {
    $date_debut = $max_date;
}

// Statistiques générales
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN d.type_acte = 'naissance' THEN 1 END) as total_naissances,
        COUNT(CASE WHEN d.type_acte = 'mariage' THEN 1 END) as total_mariages,
        COUNT(CASE WHEN d.type_acte = 'deces' THEN 1 END) as total_deces,
        COUNT(CASE WHEN d.statut = 'valide' THEN 1 END) as total_valides,
        COUNT(CASE WHEN d.statut = 'en_cours' THEN 1 END) as total_en_cours,
        COUNT(CASE WHEN d.statut = 'en_attente' THEN 1 END) as total_en_attente,
        COALESCE(SUM(CASE WHEN p.montant IS NOT NULL THEN p.montant ELSE 0 END), 0) as total_paiements
    FROM demandes d
    LEFT JOIN paiements p ON d.numero_acte = p.numero_acte AND d.type_acte = p.type_acte
    WHERE d.date_demande BETWEEN ? AND ?
");
$stmt->execute([$date_debut, $date_fin]);
$stats = $stmt->fetch();

// Évolution des demandes par type
$stmt = $conn->prepare("
    WITH RECURSIVE dates AS (
        SELECT ? as date_value
        UNION ALL
        SELECT DATE_ADD(date_value, INTERVAL 1 DAY)
        FROM dates
        WHERE date_value < ?
    )
    SELECT 
        dates.date_value,
        COUNT(CASE WHEN d.type_acte = 'naissance' THEN 1 END) as naissances,
        COUNT(CASE WHEN d.type_acte = 'mariage' THEN 1 END) as mariages,
        COUNT(CASE WHEN d.type_acte = 'deces' THEN 1 END) as deces
    FROM dates
    LEFT JOIN demandes d ON DATE(d.date_demande) = dates.date_value
    GROUP BY dates.date_value
    ORDER BY dates.date_value
");
$stmt->execute([$date_debut, $date_fin]);
$evolution = $stmt->fetchAll();

// Distribution des statuts
$stmt = $conn->prepare("
    SELECT 
        type_acte,
        statut,
        COUNT(*) as total
    FROM demandes
    WHERE date_demande BETWEEN ? AND ?
    GROUP BY type_acte, statut
    ORDER BY type_acte, statut
");
$stmt->execute([$date_debut, $date_fin]);
$statuts = $stmt->fetchAll();

// Paiements par mode de paiement
$stmt = $conn->prepare("
    SELECT 
        mode_paiement,
        COUNT(*) as nombre,
        SUM(montant) as montant_total,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM paiements WHERE date_paiement BETWEEN ? AND ?), 2) as pourcentage
    FROM paiements
    WHERE date_paiement BETWEEN ? AND ?
    GROUP BY mode_paiement
    ORDER BY nombre DESC
");
$stmt->execute([$date_debut, $date_fin, $date_debut, $date_fin]);
$paiements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">Rapports et statistiques</h1>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="periode" class="form-label">Période</label>
                                <select class="form-select" id="periode" name="periode" onchange="updateDates()">
                                    <option value="semaine" <?php echo $periode === 'semaine' ? 'selected' : ''; ?>>Cette semaine</option>
                                    <option value="mois" <?php echo $periode === 'mois' ? 'selected' : ''; ?>>Ce mois</option>
                                    <option value="trimestre" <?php echo $periode === 'trimestre' ? 'selected' : ''; ?>>Ce trimestre</option>
                                    <option value="annee" <?php echo $periode === 'annee' ? 'selected' : ''; ?>>Cette année</option>
                                    <option value="personnalise" <?php echo $periode === 'personnalise' ? 'selected' : ''; ?>>Personnalisé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_debut" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_fin" class="form-label">Date de fin</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistiques générales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Naissances</h5>
                                <p class="card-text h3"><?php echo number_format($stats['total_naissances']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Mariages</h5>
                                <p class="card-text h3"><?php echo number_format($stats['total_mariages']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Décès</h5>
                                <p class="card-text h3"><?php echo number_format($stats['total_deces']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Paiements</h5>
                                <p class="card-text h3"><?php echo number_format($stats['total_paiements'], 0, ',', ' '); ?> FCFA</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Évolution des demandes</h5>
                                <canvas id="evolutionChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Distribution des statuts</h5>
                                <canvas id="statutsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des paiements -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Paiements par mode de paiement</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mode de paiement</th>
                                        <th>Nombre de transactions</th>
                                        <th>Montant total</th>
                                        <th>Pourcentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paiements as $p): ?>
                                    <tr>
                                        <td><?php echo ucfirst($p['mode_paiement']); ?></td>
                                        <td><?php echo number_format($p['nombre']); ?></td>
                                        <td><?php echo number_format($p['montant_total'], 0, ',', ' '); ?> FCFA</td>
                                        <td><?php echo $p['pourcentage']; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique d'évolution
        const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($evolution, 'date_value')); ?>,
                datasets: [{
                    label: 'Naissances',
                    data: <?php echo json_encode(array_column($evolution, 'naissances')); ?>,
                    borderColor: 'rgb(13, 110, 253)',
                    tension: 0.1
                }, {
                    label: 'Mariages',
                    data: <?php echo json_encode(array_column($evolution, 'mariages')); ?>,
                    borderColor: 'rgb(25, 135, 84)',
                    tension: 0.1
                }, {
                    label: 'Décès',
                    data: <?php echo json_encode(array_column($evolution, 'deces')); ?>,
                    borderColor: 'rgb(220, 53, 69)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Évolution des demandes par type'
                    }
                }
            }
        });

        // Graphique des statuts
        const statutsCtx = document.getElementById('statutsChart').getContext('2d');
        new Chart(statutsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Validés', 'En cours', 'En attente'],
                datasets: [{
                    data: [
                        <?php echo $stats['total_valides']; ?>,
                        <?php echo $stats['total_en_cours']; ?>,
                        <?php echo $stats['total_en_attente']; ?>
                    ],
                    backgroundColor: [
                        'rgb(25, 135, 84)',
                        'rgb(13, 110, 253)',
                        'rgb(255, 193, 7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribution des statuts'
                    }
                }
            }
        });

        // Mise à jour des dates selon la période sélectionnée
        function updateDates() {
            const periode = document.getElementById('periode').value;
            const dateFin = new Date();
            let dateDebut = new Date();

            switch(periode) {
                case 'semaine':
                    dateDebut.setDate(dateDebut.getDate() - 7);
                    break;
                case 'mois':
                    dateDebut.setMonth(dateDebut.getMonth() - 1);
                    break;
                case 'trimestre':
                    dateDebut.setMonth(dateDebut.getMonth() - 3);
                    break;
                case 'annee':
                    dateDebut.setFullYear(dateDebut.getFullYear() - 1);
                    break;
                case 'personnalise':
                    return; // Ne rien faire pour la période personnalisée
            }

            document.getElementById('date_debut').value = dateDebut.toISOString().split('T')[0];
            document.getElementById('date_fin').value = dateFin.toISOString().split('T')[0];
        }
    </script>
</body>
</html> 