<?php
require_once __DIR__ . '/../../includes/notifications.php';

// Récupérer le nombre de notifications non lues
$notifications_non_lues = get_nombre_notifications_non_lues($conn);
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #FF8C00;">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Administration - État Civil CI</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="demandes.php">Gestion des demandes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="documents.php">Documents</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="rapports.php">Rapports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="utilisateurs.php">Utilisateurs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="parametres.php">Paramètres</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="notifications.php">
                        <i class="bi bi-bell"></i>
                        <?php if ($notifications_non_lues > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $notifications_non_lues; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../logout.php">Déconnexion</a>
                </li>
            </ul>
        </div>
    </div>
</nav> 