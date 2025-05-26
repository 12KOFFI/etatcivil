<?php
require_once 'includes/header.php';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'update_frais':
                    $frais_naissance = $_POST['frais_naissance'];
                    $frais_mariage = $_POST['frais_mariage'];
                    $frais_deces = $_POST['frais_deces'];

                    $stmt = $conn->prepare("UPDATE parametres SET 
                        valeur = ? WHERE cle = 'frais_naissance'");
                    $stmt->execute([$frais_naissance]);

                    $stmt = $conn->prepare("UPDATE parametres SET 
                        valeur = ? WHERE cle = 'frais_mariage'");
                    $stmt->execute([$frais_mariage]);

                    $stmt = $conn->prepare("UPDATE parametres SET 
                        valeur = ? WHERE cle = 'frais_deces'");
                    $stmt->execute([$frais_deces]);

                    $_SESSION['success'] = "Les frais ont été mis à jour avec succès.";
                    break;

                case 'update_config':
                    $titre_site = $_POST['titre_site'];
                    $description = $_POST['description'];
                    $email_contact = $_POST['email_contact'];
                    $telephone = $_POST['telephone'];

                    $stmt = $conn->prepare("UPDATE parametres SET 
                        valeur = ? WHERE cle = 'titre_site'");
                    $stmt->execute([$titre_site]);

                    $stmt = $conn->prepare("UPDATE parametres SET 
                        valeur = ? WHERE cle = 'description'");
                    $stmt->execute([$description]);

                    $stmt = $conn->prepare("UPDATE parametres SET 
                        valeur = ? WHERE cle = 'email_contact'");
                    $stmt->execute([$email_contact]);

                    $stmt = $conn->prepare("UPDATE parametres SET 
                        valeur = ? WHERE cle = 'telephone'");
                    $stmt->execute([$telephone]);

                    $_SESSION['success'] = "La configuration a été mise à jour avec succès.";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
        
        header('Location: parametres.php');
        exit();
    }
}

// Récupérer les paramètres
try {
    $stmt = $conn->query("SELECT * FROM parametres");
    $parametres = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $parametres[$row['cle']] = $row['valeur'];
    }
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des paramètres : " . $e->getMessage();
}

// Inclure la sidebar
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <h1 class="mb-4">Paramètres</h1>

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

    <div class="row">
        <!-- Frais -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Frais des actes</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_frais">
                        
                        <div class="mb-3">
                            <label for="frais_naissance" class="form-label">Frais acte de naissance (FCFA)</label>
                            <input type="number" class="form-control" id="frais_naissance" name="frais_naissance" 
                                value="<?php echo htmlspecialchars($parametres['frais_naissance'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="frais_mariage" class="form-label">Frais acte de mariage (FCFA)</label>
                            <input type="number" class="form-control" id="frais_mariage" name="frais_mariage" 
                                value="<?php echo htmlspecialchars($parametres['frais_mariage'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="frais_deces" class="form-label">Frais acte de décès (FCFA)</label>
                            <input type="number" class="form-control" id="frais_deces" name="frais_deces" 
                                value="<?php echo htmlspecialchars($parametres['frais_deces'] ?? ''); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Mettre à jour les frais</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Configuration générale -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Configuration générale</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_config">
                        
                        <div class="mb-3">
                            <label for="titre_site" class="form-label">Titre du site</label>
                            <input type="text" class="form-control" id="titre_site" name="titre_site" 
                                value="<?php echo htmlspecialchars($parametres['titre_site'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($parametres['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="email_contact" class="form-label">Email de contact</label>
                            <input type="email" class="form-control" id="email_contact" name="email_contact" 
                                value="<?php echo htmlspecialchars($parametres['email_contact'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="telephone" name="telephone" 
                                value="<?php echo htmlspecialchars($parametres['telephone'] ?? ''); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Mettre à jour la configuration</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 