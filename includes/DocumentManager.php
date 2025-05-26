<?php
class DocumentManager {
    private $conn;
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->uploadDir = __DIR__ . '/../uploads/documents/';
        $this->allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg'
        ];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB

        // Créer le répertoire d'upload s'il n'existe pas
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function uploadDocument($type_acte, $numero_acte, $file, $type_document, $description = '') {
        try {
            // Vérifier le type de fichier
            if (!in_array($file['type'], $this->allowedTypes)) {
                throw new Exception("Type de fichier non autorisé. Types acceptés : PDF, JPEG, PNG");
            }

            // Vérifier la taille du fichier
            if ($file['size'] > $this->maxFileSize) {
                throw new Exception("Le fichier est trop volumineux. Taille maximale : 5MB");
            }

            // Générer un nom de fichier unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nom_fichier = uniqid() . '_' . $type_acte . '_' . $numero_acte . '.' . $extension;
            $chemin_fichier = $this->uploadDir . $nom_fichier;

            // Déplacer le fichier
            if (!move_uploaded_file($file['tmp_name'], $chemin_fichier)) {
                throw new Exception("Erreur lors de l'upload du fichier");
            }

            // Enregistrer dans la base de données
            $stmt = $this->conn->prepare("
                INSERT INTO documents (
                    type_acte, numero_acte, nom_fichier, type_document,
                    chemin_fichier, date_upload, taille_fichier, mime_type, description
                ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)
            ");

            $stmt->execute([
                $type_acte,
                $numero_acte,
                $nom_fichier,
                $type_document,
                $chemin_fichier,
                $file['size'],
                $file['type'],
                $description
            ]);

            return $this->conn->lastInsertId();
        } catch (Exception $e) {
            // Supprimer le fichier en cas d'erreur
            if (isset($chemin_fichier) && file_exists($chemin_fichier)) {
                unlink($chemin_fichier);
            }
            throw $e;
        }
    }

    public function getDocuments($type_acte, $numero_acte) {
        $stmt = $this->conn->prepare("
            SELECT * FROM documents 
            WHERE type_acte = ? AND numero_acte = ?
            ORDER BY date_upload DESC
        ");
        $stmt->execute([$type_acte, $numero_acte]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDocument($id) {
        try {
            // Récupérer les informations du document
            $stmt = $this->conn->prepare("SELECT chemin_fichier FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                throw new Exception("Document non trouvé");
            }

            // Supprimer le fichier physique
            if (file_exists($document['chemin_fichier'])) {
                unlink($document['chemin_fichier']);
            }

            // Supprimer l'enregistrement de la base de données
            $stmt = $this->conn->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$id]);

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getDocument($id) {
        $stmt = $this->conn->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateDocumentDescription($id, $description) {
        $stmt = $this->conn->prepare("
            UPDATE documents 
            SET description = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$description, $id]);
    }
} 