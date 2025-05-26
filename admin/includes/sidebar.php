<?php
// Déterminer la page active
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .sidebar {
        min-height: calc(100vh - 56px);
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
    }
    .sidebar .nav-link {
        color: #333;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        margin: 0.2rem 0;
    }
    .sidebar .nav-link:hover {
        background-color: #e9ecef;
    }
    .sidebar .nav-link.active {
        background-color: #FF8C00;
        color: white;
    }
    .sidebar .nav-link i {
        margin-right: 0.5rem;
    }
    .main-content {
        padding: 2rem;
    }
</style>

<div class="col-md-3 col-lg-2 px-0 sidebar">
    <div class="p-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Tableau de bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'demandes.php' ? 'active' : ''; ?>" href="demandes.php">
                    <i class="bi bi-file-text"></i> Gestion des demandes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'utilisateurs.php' ? 'active' : ''; ?>" href="utilisateurs.php">
                    <i class="bi bi-people"></i> Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'rapports.php' ? 'active' : ''; ?>" href="rapports.php">
                    <i class="bi bi-graph-up"></i> Rapports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'documents.php' ? 'active' : ''; ?>" href="documents.php">
                    <i class="bi bi-file-earmark"></i> Documents
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                    <i class="bi bi-bell"></i> Notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'parametres.php' ? 'active' : ''; ?>" href="parametres.php">
                    <i class="bi bi-gear"></i> Paramètres
                </a>
            </li>
        </ul>
    </div>
</div> 