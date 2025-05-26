<?php
require_once 'includes/header.php';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'mark_read':
                    $notification_id = $_POST['notification_id'];
                    $stmt = $conn->prepare("UPDATE notifications SET lu = 1 WHERE id = ?");
                    $stmt->execute([$notification_id]);
                    $_SESSION['success'] = "La notification a été marquée comme lue.";
                    break;

                case 'delete':
                    $notification_id = $_POST['notification_id'];
                    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
                    $stmt->execute([$notification_id]);
                    $_SESSION['success'] = "La notification a été supprimée.";
                    break;

                case 'mark_all_read':
                    $stmt = $conn->prepare("UPDATE notifications SET lu = 1 WHERE lu = 0");
                    $stmt->execute();
                    $_SESSION['success'] = "Toutes les notifications ont été marquées comme lues.";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors du traitement : " . $e->getMessage();
        }
        
        header('Location: notifications.php');
        exit();
    }
}

// Récupérer les notifications
try {
    $stmt = $conn->query("SELECT * FROM notifications ORDER BY date_creation DESC");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des notifications : " . $e->getMessage();
}

// Inclure la sidebar
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Notifications</h1>
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-check-all"></i> Tout marquer comme lu
            </button>
        </form>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <p class="text-muted text-center">Aucune notification</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item list-group-item-action <?php echo $notification['lu'] ? '' : 'list-group-item-primary'; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['titre']); ?></h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($notification['date_creation'])); ?>
                                    </small>
                                </div>
                                <div class="btn-group">
                                    <?php if (!$notification['lu']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette notification ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 