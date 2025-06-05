<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            
            if (!$user_id) {
                throw new Exception("ID utilisateur invalide");
            }

            // Vérifier si l'utilisateur existe et n'est pas un admin
            $stmt = $conn->prepare("SELECT role FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Utilisateur non trouvé");
            }

            if ($user['role'] === 'admin') {
                throw new Exception("Impossible de modifier un administrateur");
            }
            
            switch ($_POST['action']) {
                case 'desactiver':
                    $stmt = $conn->prepare("UPDATE utilisateurs SET statut = 'inactif' WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['success'] = "L'utilisateur a été désactivé avec succès.";
                    break;
                    
                case 'activer':
                    $stmt = $conn->prepare("UPDATE utilisateurs SET statut = 'actif' WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['success'] = "L'utilisateur a été activé avec succès.";
                    break;
                    
                case 'supprimer':
                    // Commencer une transaction
                    $conn->beginTransaction();
                    
                    try {
                        // Supprimer les références dans actes_naissance
                        $stmt = $conn->prepare("DELETE FROM actes_naissance WHERE utilisateur_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Supprimer les références dans actes_mariage
                        $stmt = $conn->prepare("DELETE FROM actes_mariage WHERE utilisateur_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Supprimer les références dans actes_deces
                        $stmt = $conn->prepare("DELETE FROM actes_deces WHERE utilisateur_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Supprimer les paiements associés
                        $stmt = $conn->prepare("DELETE FROM paiements WHERE utilisateur_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Supprimer les demandes associées
                        $stmt = $conn->prepare("DELETE FROM demandes WHERE utilisateur_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Supprimer les notifications de l'utilisateur
                        $stmt = $conn->prepare("DELETE FROM notifications WHERE utilisateur_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Finalement, supprimer l'utilisateur
                        $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Valider la transaction
                        $conn->commit();
                        $_SESSION['success'] = "L'utilisateur et toutes ses données associées ont été supprimés avec succès.";
                    } catch (Exception $e) {
                        // En cas d'erreur, annuler toutes les modifications
                        $conn->rollBack();
                        throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: utilisateurs.php');
        exit();
    }
}

// Récupérer la liste des utilisateurs
try {
    $stmt = $conn->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs : " . $e->getMessage();
}

// Inclure la sidebar
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <h1 class="mb-4">Gestion des utilisateurs</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <style>
        /* Style pour les boutons d'action */
        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .btn-group .btn {
            padding: 0.4rem 0.6rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin: 0 3px;
            min-width: 38px;
        }

        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .btn-group .btn i {
            font-size: 1rem;
            transition: all 0.3s ease;
            padding: 0 2px;
        }

        /* Styles spécifiques pour chaque type de bouton */
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .btn-warning:hover {
            background-color: #ffca2c;
            border-color: #ffc720;
            color: #000;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }

        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }

        /* Style pour les badges */
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .badge i {
            margin-right: 0.3rem;
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

    <!-- Liste des utilisateurs -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénoms</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Date d'inscription</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($utilisateurs)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Aucun utilisateur trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                <tr>
                                    <td><?php echo $utilisateur['id']; ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['prenoms']); ?></td>
                                    <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $utilisateur['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($utilisateur['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $utilisateur['statut'] === 'actif' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($utilisateur['statut']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($utilisateur['date_inscription'])); ?></td>
                                    <td><?php echo $utilisateur['derniere_connexion'] ? date('d/m/Y H:i', strtotime($utilisateur['derniere_connexion'])) : 'Jamais'; ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($utilisateur['role'] !== 'admin'): ?>
                                                <?php if ($utilisateur['statut'] === 'actif'): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur ?');">
                                                        <input type="hidden" name="action" value="desactiver">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($utilisateur['id']); ?>">
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-warning" 
                                                                data-bs-toggle="tooltip"
                                                                data-bs-placement="top"
                                                                title="Désactiver l'utilisateur">
                                                            <i class="fas fa-user-slash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="activer">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($utilisateur['id']); ?>">
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-success" 
                                                                data-bs-toggle="tooltip"
                                                                data-bs-placement="top"
                                                                title="Activer l'utilisateur">
                                                            <i class="fas fa-user-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                                                    <input type="hidden" name="action" value="supprimer">
                                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($utilisateur['id']); ?>">
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            title="Supprimer l'utilisateur">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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