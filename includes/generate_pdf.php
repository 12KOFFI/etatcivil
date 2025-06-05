<?php
require_once __DIR__ . '/../TCPDF/tcpdf.php';
require_once __DIR__ . '/../config/database.php';

class PDFGenerator extends TCPDF {
    public function Header() {
        // Logo en haut à gauche
        $this->Image(__DIR__ . '/../assets/images/logo-district.png', 10, 10, 30);
        
        // En-tête officiel centré
        $this->SetFont('times', 'B', 14);
        $this->Cell(0, 10, 'RÉPUBLIQUE DE CÔTE D\'IVOIRE', 0, 1, 'C');
        $this->SetFont('times', '', 11);
        $this->Cell(0, 8, 'Union - Travail - Progrès', 0, 1, 'C');
        
        $this->Ln(5);
        
        // District/Commune
        $this->SetFont('times', 'B', 12);
        $this->Cell(0, 8, 'DISTRICT AUTONOME D\'ABIDJAN', 0, 1, 'C');
        $this->SetFont('times', 'B', 11);
        $this->Cell(0, 8, 'COMMUNE DE YOPOUGON', 0, 1, 'C');
       
       
        
        $this->Ln(15);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

function generateActePDF($type, $demande_id) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Récupérer les informations de la demande avec tous les champs nécessaires
    $stmt = $conn->prepare("
        SELECT d.*, 
               ad.nom_defunt,
               ad.prenoms_defunt,
               ad.date_naissance_defunt,
               ad.date_deces,
               ad.lieu_deces,
               ad.cause_deces,
               ad.declarant,
               ad.lien_declarant,
               am.nom_epoux,
               am.prenoms_epoux,
               am.date_naissance_epoux,
               am.nom_epouse,
               am.prenoms_epouse,
               am.date_naissance_epouse,
               am.date_mariage,
               am.lieu_mariage,
               an.nom as nom_enfant,
               an.prenoms as prenoms_enfant,
               an.date_naissance,
               an.lieu_naissance,
               an.nom_pere,
               an.nom_mere,
               an.declarant as declarant_naissance,
               an.lien_declarant as lien_declarant_naissance
        FROM demandes d
        LEFT JOIN actes_deces ad ON d.numero_acte = ad.numero_acte
        LEFT JOIN actes_mariage am ON d.numero_acte = am.numero_acte
        LEFT JOIN actes_naissance an ON d.numero_acte = an.numero_acte
        WHERE d.id = ? AND d.statut = 'valide'
    ");
    $stmt->execute([$demande_id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        return false;
    }

    // Créer le PDF
    $pdf = new PDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false, 0); // Empêche le saut automatique de page


    // Définir les métadonnées du document
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('État Civil CI');
    $pdf->SetTitle('Acte ' . ucfirst($type));

    // Définir les marges
    $pdf->SetMargins(20, 40, 20);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Ajouter une page
    $pdf->AddPage();

    // Ajouter le filigrane
    $pdf->SetAlpha(0.1);
    $pdf->Image(__DIR__ . '/../assets/images/logo-district.png', 50, 100, 100);
    $pdf->SetAlpha(1);

    // Contenu du PDF selon le type d'acte
    switch ($type) {
        case 'naissance':
            generateActeNaissance($pdf, $demande);
            break;
        case 'mariage':
            generateActeMariage($pdf, $demande);
            break;
        case 'deces':
            generateActeDeces($pdf, $demande);
            break;
    }

    // Générer le nom du fichier
    $filename = 'acte_' . $type . '_' . $demande_id . '.pdf';
    $filepath = __DIR__ . '/../pdfs/' . $filename;

    // Sauvegarder le PDF
    $pdf->Output($filepath, 'F');

    return $filename;
}

function generateActeNaissance($pdf, $demande) {
    $pdf->SetFont('times', 'B', 14);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'EXTRAIT ACTE DE NAISSANCE', 0, 1, 'C');
    $pdf->Ln(10);

    // Informations de l'enfant
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'INFORMATIONS DE L\'ENFANT', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Nom:', 0);
    $pdf->Cell(0, 10, $demande['nom_enfant'], 0, 1);
    
    $pdf->Cell(60, 10, 'Prénoms:', 0);
    $pdf->Cell(0, 10, $demande['prenoms_enfant'], 0, 1);
    
    $pdf->Cell(60, 10, 'Date de naissance:', 0);
    $pdf->Cell(0, 10, date('d/m/Y', strtotime($demande['date_naissance'])), 0, 1);
    
    $pdf->Cell(60, 10, 'Lieu de naissance:', 0);
    $pdf->Cell(0, 10, $demande['lieu_naissance'], 0, 1);

    // Informations des parents
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'INFORMATIONS DES PARENTS', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Nom du père:', 0);
    $pdf->Cell(0, 10, $demande['nom_pere'], 0, 1);
    
    $pdf->Cell(60, 10, 'Nom de la mère:', 0);
    $pdf->Cell(0, 10, $demande['nom_mere'], 0, 1);


    // Détails de la demande
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'DÉTAILS DE LA DEMANDE', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Numéro d\'acte:', 0);
    $pdf->Cell(0, 10, $demande['numero_acte'], 0, 1);
    
    $pdf->Cell(60, 10, 'Date d\'établissement:', 0);
    

   // Zone de certification
$pdf->Ln(30); // Espace avant la zone
$pdf->SetFont('times', '', 12);
$pdf->Cell(0, 10, 'Délivré à Yopougon, le ' . date('d/m/Y', strtotime($demande['date_demande'])), 0, 1, 'R');

// Texte d'accompagnement
$pdf->Cell(0, 10, 'Signature de l\'officier d\'état civil', 0, 1, 'R');

// Positionner la signature juste en dessous de la ligne précédente
// On récupère la position actuelle après le texte
$ySignature = $pdf->GetY() - 10; // Ajouter un espace de 10 mm

// Définir le chemin vers l'image
$signaturePath = __DIR__ . '/../assets/images/signature.png';

// Ajustement de la position de l’image
// X = 150 pour bien coller à droite, Y = position actuelle ($ySignature)
$signatureWidth = 50; // Largeur de l'image en mm
$pdf->Image($signaturePath, 150, $ySignature, $signatureWidth);

// Optionnel : ajouter un petit texte ou une ligne en dessous
$pdf->Ln(30); // Espace après la signature
}

function generateActeMariage($pdf, $demande) {
    $pdf->SetFont('times', 'B', 14);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'EXTRAIT ACTE DE MARIAGE', 0, 1, 'C');
    $pdf->Ln(10);

    // Informations du mari
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'INFORMATIONS DU MARI', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);


    $pdf->Cell(60, 10, 'Nom:', 0);
    $pdf->Cell(0, 10, $demande['nom_epoux'], 0, 1);
    
    $pdf->Cell(60, 10, 'Prénoms:', 0);
    $pdf->Cell(0, 10, $demande['prenoms_epoux'], 0, 1);
    
    $pdf->Cell(60, 10, 'Date de naissance:', 0);
    $pdf->Cell(0, 10, date('d/m/Y', strtotime($demande['date_naissance_epoux'])), 0, 1);

    // Informations de la mariée
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'INFORMATIONS DE LA MARIÉE', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Nom:', 0);
    $pdf->Cell(0, 10, $demande['nom_epouse'], 0, 1);
    
    $pdf->Cell(60, 10, 'Prénoms:', 0);
    $pdf->Cell(0, 10, $demande['prenoms_epouse'], 0, 1);
    
    $pdf->Cell(60, 10, 'Date de naissance:', 0);
    $pdf->Cell(0, 10, date('d/m/Y', strtotime($demande['date_naissance_epouse'])), 0, 1);

    // Informations du mariage
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'INFORMATIONS DU MARIAGE', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Date du mariage:', 0);
    $pdf->Cell(0, 10, date('d/m/Y', strtotime($demande['date_mariage'])), 0, 1);
    
    $pdf->Cell(60, 10, 'Lieu du mariage:', 0);
    $pdf->Cell(0, 10, $demande['lieu_mariage'], 0, 1);

    // Détails de la demande
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'DÉTAILS DE LA DEMANDE', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Numéro d\'acte:', 0);
    $pdf->Cell(0, 10, $demande['numero_acte'], 0, 1);
    
    $pdf->Cell(60, 10, 'Nombre de copies:', 0);
    $pdf->Cell(0, 10, $demande['nombre_copies'], 0, 1);
    
  
   
    
   
   
// Zone de certification
$pdf->Ln(5); // Espace avant la zone
$pdf->SetFont('times', '', 12);
$pdf->Cell(0, 10, 'Délivré à Yopougon, le ' . date('d/m/Y', strtotime($demande['date_demande'])), 0, 1, 'R');

// Texte d'accompagnement
$pdf->Cell(0, 10, 'Signature de l\'officier d\'état civil', 0, 1, 'R');

// Positionner la signature juste en dessous de la ligne précédente
// On récupère la position actuelle après le texte
$ySignature = $pdf->GetY() - 10; // Ajouter un espace de 10 mm

// Définir le chemin vers l'image
$signaturePath = __DIR__ . '/../assets/images/signature.png';

// Ajustement de la position de l’image
// X = 150 pour bien coller à droite, Y = position actuelle ($ySignature)
$signatureWidth = 50; // Largeur de l'image en mm
$pdf->Image($signaturePath, 150, $ySignature, $signatureWidth);

// Optionnel : ajouter un petit texte ou une ligne en dessous
$pdf->Ln(30); // Espace après la signature

}

function generateActeDeces($pdf, $demande) {
    $pdf->SetFont('times', 'B', 14);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'EXTRAIT ACTE DE DÉCÈS', 0, 1, 'C');
    $pdf->Ln(10);

    // Informations du défunt
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'INFORMATIONS DU DÉFUNT', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Nom:', 0);
    $pdf->Cell(0, 10, $demande['nom_defunt'], 0, 1);
    
    $pdf->Cell(60, 10, 'Prénoms:', 0);
    $pdf->Cell(0, 10, $demande['prenoms_defunt'], 0, 1);
    
    $pdf->Cell(60, 10, 'Date de naissance:', 0);
    $pdf->Cell(0, 10, date('d/m/Y', strtotime($demande['date_naissance_defunt'])), 0, 1);
    
    $pdf->Cell(60, 10, 'Date du décès:', 0);
    $pdf->Cell(0, 10, date('d/m/Y', strtotime($demande['date_deces'])), 0, 1);
    
    $pdf->Cell(60, 10, 'Lieu du décès:', 0);
    $pdf->Cell(0, 10, $demande['lieu_deces'], 0, 1);
    
    $pdf->Cell(60, 10, 'Cause du décès:', 0);
    $pdf->Cell(0, 10, $demande['cause_deces'], 0, 1);

    // Informations du déclarant
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'INFORMATIONS DU DÉCLARANT', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Nom du déclarant:', 0);
    $pdf->Cell(0, 10, $demande['declarant'], 0, 1);
    
    $pdf->Cell(60, 10, 'Lien avec le défunt:', 0);
    $pdf->Cell(0, 10, $demande['lien_declarant'], 0, 1);

    // Détails de la demande
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 10, 'DÉTAILS DE LA DEMANDE', 0, 1, 'L');
    $pdf->SetFont('times', '', 12);
    $pdf->Ln(5);

    $pdf->Cell(60, 10, 'Numéro d\'acte:', 0);
    $pdf->Cell(0, 10, $demande['numero_acte'], 0, 1);
    
    

  // Zone de certification
$pdf->Ln(5); // Espace avant la zone
$pdf->SetFont('times', '', 12);
$pdf->Cell(0, 10, 'Délivré à Yopougon, le ' . date('d/m/Y', strtotime($demande['date_demande'])), 0, 1, 'R');

// Texte d'accompagnement
$pdf->Cell(0, 10, 'Signature de l\'officier d\'état civil', 0, 1, 'R');

// Positionner la signature juste en dessous de la ligne précédente
// On récupère la position actuelle après le texte
$ySignature = $pdf->GetY() - 10; // Ajouter un espace de 10 mm

// Définir le chemin vers l'image
$signaturePath = __DIR__ . '/../assets/images/signature.png';

// Ajustement de la position de l’image
// X = 150 pour bien coller à droite, Y = position actuelle ($ySignature)
$signatureWidth = 50; // Largeur de l'image en mm
$pdf->Image($signaturePath, 150, $ySignature, $signatureWidth);

// Optionnel : ajouter un petit texte ou une ligne en dessous
$pdf->Ln(30); // Espace après la signature
}

// Fonction pour télécharger le PDF
function downloadPDF($filename) {
    $filepath = __DIR__ . '/../pdfs/' . $filename;
    
    if (file_exists($filepath)) {
        // Supprimer tout output précédent
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filepath);
        exit;
    }
    
    return false;
}
?> 