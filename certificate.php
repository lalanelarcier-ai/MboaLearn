<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role('etudiant');

$user = get_user_info();
$etudiant_id = $_SESSION['user_id'];
$certificat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$certificat_id) {
    header('Location: certificats.php');
    exit();
}

// Récupérer le certificat avec toutes les infos
$stmt = $pdo->prepare("
    SELECT cert.*, 
           e.titre as eval_titre,
           l.titre as lecon_titre,
           c.titre as cours_titre,
           m.nom as module_nom
    FROM certificats cert
    LEFT JOIN evaluations e ON cert.evaluation_id = e.id
    LEFT JOIN lecons l ON e.lecon_id = l.id
    LEFT JOIN cours c ON l.cours_id = c.id
    LEFT JOIN modules m ON cert.module_id = m.id
    WHERE cert.id = ? AND cert.etudiant_id = ?
");
$stmt->execute([$certificat_id, $etudiant_id]);
$certificat = $stmt->fetch();

if (!$certificat) {
    header('Location: certificats.php');
    exit();
}

$page_title = "Certificat";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat - MboaLearn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f0f0f0; }
        
        .certificate-wrapper {
            max-width: 850px;
            margin: 2rem auto;
        }
        
        .certificate-actions {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .certificate {
            background: white;
            border: 3px solid #1e3a5f;
            border-radius: 4px;
            padding: 60px 70px;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            font-family: 'Inter', sans-serif;
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            bottom: 12px;
            border: 2px solid #c9a962;
            border-radius: 2px;
        }
        
        .certificate::after {
            content: '';
            position: absolute;
            top: 18px;
            left: 18px;
            right: 18px;
            bottom: 18px;
            border: 1px solid #d4b97a;
            border-radius: 2px;
        }
        
        .cert-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }
        
        .cert-header {
            font-family: 'Playfair Display', serif;
            font-size: 0.9rem;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 0.5rem;
        }
        
        .cert-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 0.3rem;
            line-height: 1.1;
        }
        
        .cert-subtitle {
            font-size: 1rem;
            color: #666;
            margin-bottom: 2rem;
            font-style: italic;
        }
        
        .cert-presented {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 0.5rem;
        }
        
        .cert-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            color: #1e3a5f;
            border-bottom: 2px solid #c9a962;
            display: inline-block;
            padding: 0 1rem 0.3rem;
            margin-bottom: 1.5rem;
            font-style: italic;
        }
        
        .cert-text {
            font-size: 1rem;
            color: #555;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .cert-course {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 0.5rem;
        }
        
        .cert-score {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 2rem;
        }
        
        .cert-score strong {
            color: #1e3a5f;
            font-size: 1.3rem;
        }
        
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .cert-signature {
            text-align: center;
        }
        
        .cert-signature-line {
            width: 180px;
            border-bottom: 1px solid #333;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            font-style: italic;
            color: #333;
            font-size: 1.1rem;
        }
        
        .cert-signature-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        .cert-date {
            text-align: center;
            font-size: 0.85rem;
            color: #666;
        }
        
        .cert-number {
            position: absolute;
            bottom: 25px;
            right: 30px;
            font-size: 0.75rem;
            color: #999;
        }
        
        .cert-seal {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #c9a962 0%, #dfc08a 50%, #c9a962 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #1e3a5f;
            font-size: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .certificate-actions { display: none !important; }
            .certificate-wrapper { margin: 0; }
            .certificate { box-shadow: none; border-width: 2px; }
        }
    </style>
</head>
<body>
    <div class="certificate-wrapper">
        <div class="certificate-actions no-print">
            <a href="resultats.php?eval=<?php echo $certificat['evaluation_id']; ?>" class="btn-secondary" style="margin-right: 1rem;">
                <i class="bi bi-arrow-left"></i> Retour aux résultats
            </a>
            <button onclick="window.print()" class="btn-primary">
                <i class="bi bi-download"></i> Télécharger / Imprimer (PDF)
            </button>
        </div>
        
        <div class="certificate">
            <div class="cert-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            
            <div class="cert-header">Plateforme d'Apprentissage</div>
            <div class="cert-title">Certificat de Réussite</div>
            <div class="cert-subtitle">MboaLearn</div>
            
            <div class="cert-presented">Ce certificat est décerné à</div>
            <div class="cert-name"><?php echo htmlspecialchars($user['nom']); ?></div>
            
            <div class="cert-text">
                pour avoir successfully complété et validé avec succès
            </div>
            
            <div class="cert-course">
                <?php echo htmlspecialchars($certificat['cours_titre']); ?>
            </div>
            <div style="font-size: 1rem; color: #666; margin-bottom: 0.5rem;">
                Leçon : <?php echo htmlspecialchars($certificat['lecon_titre']); ?>
            </div>
            
            <div class="cert-score">
                avec un score de <strong><?php echo number_format($certificat['score_moyen'], 1); ?>%</strong>
            </div>
            
            <div class="cert-footer">
                <div class="cert-signature">
                    <div class="cert-signature-line">MboaLearn</div>
                    <div class="cert-signature-label">Plateforme d'Apprentissage</div>
                </div>
                
                <div class="cert-seal">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                
                <div class="cert-date">
                    <div style="margin-bottom: 0.3rem;"><strong>Délivré le</strong></div>
                    <div><?php echo date('d/m/Y', strtotime($certificat['date_obtention'])); ?></div>
                </div>
            </div>
            
            <div class="cert-number">
                N° <?php echo htmlspecialchars($certificat['numero_certificat']); ?>
            </div>
        </div>
    </div>
</body>
</html>
