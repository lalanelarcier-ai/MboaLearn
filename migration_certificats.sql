-- Migration : Ajouter evaluation_id à la table certificats
USE l2_lms;

-- Ajouter la colonne evaluation_id (nullable pour les anciens certificats module)
ALTER TABLE certificats ADD COLUMN evaluation_id INT NULL AFTER etudiant_id;

-- Ajouter la clé étrangère
ALTER TABLE certificats ADD CONSTRAINT fk_certificats_evaluation 
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE SET NULL;

-- Rendre module_id nullable (pour les certificats par évaluation)
ALTER TABLE certificats MODIFY COLUMN module_id INT NULL;
