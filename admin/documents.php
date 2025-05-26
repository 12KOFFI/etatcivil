<?php
require_once 'includes/header.php';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'delete':
                    $document_id = $_POST['document_id'];
                    $stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
                    $stmt->execute([$document_id]);
                    $_SESSION['success'] = "Le document a été supprimé avec succès.";
                    break;

                case 'update':
                    $document_id = $_POST['document_id'];
                    $titre = $_POST['titre'];
                    $description = $_POST['description'];
                    $categorie = $_POST['categorie'];

                    $stmt = $conn->prepare("UPDATE documents SET 
                        titre = ?, description = ?, categorie = ? 
                        WHERE id = ?");
                    $stmt->execute([$titre, $description, $categorie, $document_id]);
                    $_SESSION['success'] = "Le document a été mis à jour avec succès.";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors du traitement : " . $e->getMessage();
        }
        
        header('Location: documents.php');
        exit();
    }
}

// Récupérer les documents
try {
    $stmt = $conn->query("SELECT * FROM documents ORDER BY date_upload DESC");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des documents : " . $e->getMessage();
}

// Inclure la sidebar
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestion des documents</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-upload"></i> Ajouter un document
        </button>
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

    <!-- Liste des documents -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Description</th>
                            <th>Date d'upload</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($document['titre']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $document['categorie'] === 'naissance' ? 'primary' : 
                                            ($document['categorie'] === 'mariage' ? 'success' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($document['categorie']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($document['description']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($document['date_upload'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo htmlspecialchars($document['chemin']); ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $document['id']; ?>"
                                                data-titre="<?php echo htmlspecialchars($document['titre']); ?>"
                                                data-description="<?php echo htmlspecialchars($document['description']); ?>"
                                                data-categorie="<?php echo $document['categorie']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

<!-- Modal d'ajout de document -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="titre" name="titre" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="categorie" class="form-label">Catégorie</label>
                        <select class="form-select" id="categorie" name="categorie" required>
                            <option value="naissance">Acte de naissance</option>
                            <option value="mariage">Acte de mariage</option>
                            <option value="deces">Acte de décès</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="document" class="form-label">Document</label>
                        <input type="file" class="form-control" id="document" name="document" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de modification -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="document_id" id="edit_document_id">
                    <div class="mb-3">
                        <label for="edit_titre" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="edit_titre" name="titre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_categorie" class="form-label">Catégorie</label>
                        <select class="form-select" id="edit_categorie" name="categorie" required>
                            <option value="naissance">Acte de naissance</option>
                            <option value="mariage">Acte de mariage</option>
                            <option value="deces">Acte de décès</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Script pour le modal de modification
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const titre = button.getAttribute('data-titre');
            const description = button.getAttribute('data-description');
            const categorie = button.getAttribute('data-categorie');

            editModal.querySelector('#edit_document_id').value = id;
            editModal.querySelector('#edit_titre').value = titre;
            editModal.querySelector('#edit_description').value = description;
            editModal.querySelector('#edit_categorie').value = categorie;
        });
    }
});
</script> 