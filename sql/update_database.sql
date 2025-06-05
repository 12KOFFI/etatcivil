-- Ajout des colonnes pour la gestion des duplicatas
ALTER TABLE demandes
ADD COLUMN type_demande ENUM('originale', 'duplicata') DEFAULT 'originale',
ADD COLUMN motif_duplicata VARCHAR(255) NULL,
ADD COLUMN demande_originale_id INT NULL,
ADD FOREIGN KEY (demande_originale_id) REFERENCES demandes(id);

-- Mise à jour des demandes existantes
UPDATE demandes SET type_demande = 'originale' WHERE type_demande IS NULL; 