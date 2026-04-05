<?php

namespace App\Service;

use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Fill;
use PhpOffice\PhpPresentation\Style\Border;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Slide\Layout;

/**
 * Génère un fichier PowerPoint .pptx pour la Fiche Journalière BCC.
 */
class SlideExportService
{
    // Palette BCC
    private const BCC_BLUE_DARK = '1e4b7a';
    private const BCC_BLUE = '2d6da6';
    private const BCC_WHITE = 'FFFFFF';
    private const BCC_LIGHT = 'f0f6ff';
    private const BCC_GOLD = 'FFD700';

    private const SIGNAL_COLORS = [
        'green' => ['bg' => 'd4edda', 'fg' => '155724', 'border' => '38a169', 'label' => 'ðŸŸ¢ Zone Stable'],
        'yellow' => ['bg' => 'fff3cd', 'fg' => '856404', 'border' => 'ecc94b', 'label' => 'ðŸŸ¡ Zone de Vigilance'],
        'orange' => ['bg' => 'ffe5cc', 'fg' => '8a4700', 'border' => 'dd6b20', 'label' => 'ðŸŸ  Zone d\'Alerte'],
        'red' => ['bg' => 'f8d7da', 'fg' => '721c24', 'border' => 'c53030', 'label' => 'ðŸ”´ Zone Critique'],
        'secondary' => ['bg' => 'e9ecef', 'fg' => '6c757d', 'border' => 'adb5bd', 'label' => 'âšª Sans données'],
    ];

    /**
     * Génère le PPTX et retourne le chemin vers le fichier temporaire.
     */
    public function generate(array $data): string
    {
        $prs = new PhpPresentation();
        $prs->getDocumentProperties()
            ->setTitle('Fiche Journalière BCC')
            ->setDescription('Fiche Quotidienne de Décision â€” Stabilité Monétaire')
            ->setCompany('Banque Centrale du Congo');

        // Supprimer la diapositive vide créée automatiquement
        $prs->removeSlideByIndex(0);

        $this->buildSlide1Cover($prs, $data);
        $this->buildSlide2Pillars($prs, $data);
        $this->buildSlide3Synthesis($prs, $data);

        $tmpFile = tempnam(sys_get_temp_dir(), 'bcc_pptx_') . '.pptx';

        $writer = IOFactory::createWriter($prs, 'PowerPoint2007');
        $writer->save($tmpFile);

        return $tmpFile;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // SLIDE 1 â€” Couverture
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function buildSlide1Cover(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();

        $this->setSlideBackgroundColor($slide, self::BCC_BLUE_DARK);

        // Logo BCC
        $logoPath = __DIR__ . '/../../public/images/bcc-logo.png';
        if (file_exists($logoPath)) {
            $logo = new \PhpOffice\PhpPresentation\Shape\Drawing\File();
            $logo->setPath($logoPath)
                ->setOffsetX(380)
                ->setOffsetY(40)
                ->setHeight(120);
            $slide->addShape($logo);
        }

        // Organisation label
        $this->addTextBox(
            $slide,
            'BANQUE CENTRALE DU CONGO â€” Cabinet',
            50,
            180,
            860,
            40,
            14,
            self::BCC_WHITE,
            false,
            false,
            'center'
        )->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Main title
        $tb = $this->addTextBox(
            $slide,
            'Note Journalière de Conjoncture â€” Stabilité Monétaire',
            50,
            200,
            860,
            80,
            32,
            self::BCC_WHITE,
            true,
            false,
            'center'
        );
        $tb->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Date
        $dateStr = isset($data['date']) ? $data['date']->format('d/m/Y') : 'N/D';
        $this->addTextBox(
            $slide,
            'Situation au ' . $dateStr,
            50,
            270,
            860,
            50,
            18,
            self::BCC_GOLD,
            true,
            false,
            'center'
        )->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Divider
        $this->addFilledRect($slide, 300, 310, 360, 4, 'FFFFFF');

        // Scenario box
        $scenario = $data['scenario'] ?? ['scenario' => 1, 'label' => 'N/D', 'justification' => ''];
        $scenarioBg = match ($scenario['scenario']) { 3 => 'fff5f5', 2 => 'fffaf0', default => 'f0fff4'};
        $scenarioBorder = match ($scenario['scenario']) { 3 => 'c53030', 2 => 'dd6b20', default => '38a169'};

        $this->addFilledRect($slide, 150, 335, 660, 95, $scenarioBg, $scenarioBorder);

        $this->addTextBox(
            $slide,
            'Scénario recommandé : ' . ($scenario['label'] ?? 'N/D'),
            175,
            348,
            610,
            35,
            15,
            self::BCC_BLUE_DARK,
            true
        );
        if (!empty($scenario['justification'])) {
            $this->addTextBox(
                $slide,
                $scenario['justification'],
                175,
                383,
                610,
                35,
                11,
                '555555',
                false,
                true
            );
        }

        // Cabinet phrase
        $phrase = $data['phraseCabinet'] ?? '';
        $this->addFilledRect($slide, 50, 445, 860, 105, self::BCC_LIGHT, self::BCC_BLUE);
        $this->addTextBox(
            $slide,
            'Synthèse Cabinet',
            75,
            452,
            810,
            22,
            10,
            self::BCC_BLUE,
            true
        );
        $this->addTextBox(
            $slide,
            $phrase,
            75,
            470,
            810,
            70,
            11,
            self::BCC_BLUE_DARK,
            false,
            true
        );

        // Footer
        $this->addTextBox(
            $slide,
            'Document confidentiel â€” Usage interne Cabinet du Gouverneur · BCC-Flex · Reproduction interdite · ' . (new \DateTime())->format('d/m/Y'),
            50,
            560,
            860,
            25,
            9,
            'aaaaaa',
            false,
            false,
            'center'
        )->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // SLIDE 2 â€” 4 Piliers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function buildSlide2Pillars(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, 'f8f9fa');

        // Header banner
        $this->addFilledRect($slide, 0, 0, 960, 85, self::BCC_BLUE_DARK);
        $dateStr = isset($data['date']) ? $data['date']->format('d/m/Y') : 'N/D';
        $this->addTextBox(
            $slide,
            'Tableau de Bord â€” 4 Piliers de Stabilité Monétaire',
            20,
            14,
            700,
            40,
            18,
            self::BCC_WHITE,
            true
        );
        $this->addTextBox(
            $slide,
            'Situation au ' . $dateStr,
            20,
            50,
            700,
            25,
            12,
            self::BCC_GOLD,
            false
        );

        // 4 pillar cards
        $pillars = [
            [
                'title' => 'Pilier I â€” Marché des Changes & Parités',
                'value' => $data['ecartPct'] !== null ? number_format($data['ecartPct'], 2, ',', ' ') . ' %' : 'N/D',
                'sub' => 'Ã‰cart moyen indicatif / parallèle',
                'signal' => $data['signalChange'] ?? 'secondary',
                'details' => [
                    ['Taux indicatif', $data['marche'] ? number_format((float) $data['marche']->getCoursIndicatif(), 4, ',', ' ') . ' CDF' : 'N/D'],
                    ['Parallèle A/V', $data['marche'] ? number_format((float) $data['marche']->getParalleleAchat(), 2, ',') . ' / ' . number_format((float) $data['marche']->getParalleleVente(), 2, ',') : 'N/D'],
                    ['Ã‰cart absolu', $data['marche'] ? number_format((float) $data['marche']->getEcartIndicParallele(), 2, ',') . ' CDF' : 'N/D'],
                    ['Ã‰cart max %', $data['ecartMaxPct'] !== null ? number_format($data['ecartMaxPct'], 2, ',') . ' %' : 'N/D'],
                    ['Spread parallèle', $data['spreadPct'] !== null ? number_format($data['spreadPct'], 2, ',') . ' %' : 'N/D'],
                ],
            ],
            [
                'title' => 'Pilier II â€” Position Extérieure & Réserves',
                'value' => ($data['reserves'] && $data['reserves']->getReservesInternationalesUsd())
                    ? number_format((float) $data['reserves']->getReservesInternationalesUsd() / 1000, 2, ',', ' ') . ' Md$'
                    : 'N/D',
                'sub' => 'Réserves internationales',
                'signal' => $data['signalReserves'] ?? 'secondary',
                'details' => [
                    ['Réserves int. (Mios $)', $data['reserves'] ? number_format((float) $data['reserves']->getReservesInternationalesUsd(), 2, ',', ' ') : 'N/D'],
                    ['Avoirs ext. (Mios $)', $data['reserves'] ? number_format((float) $data['reserves']->getAvoirsExternesUsd(), 2, ',', ' ') : 'N/D'],
                    [
                        'Couverture / 5 Md$',
                        ($data['reserves'] && $data['reserves']->getReservesInternationalesUsd())
                        ? number_format((float) $data['reserves']->getReservesInternationalesUsd() / 5000 * 100, 0) . ' %'
                        : 'N/D'
                    ],
                ],
            ],
            [
                'title' => 'Pilier III â€” Liquidité Bancaire & Stérilisation',
                'value' => ($data['reserves'] && $data['reserves']->getAvoirsLibresCdf())
                    ? number_format((float) $data['reserves']->getAvoirsLibresCdf(), 0, ',', ' ')
                    : 'N/D',
                'sub' => 'Avoirs libres (Mds CDF)',
                'signal' => $data['signalLiquidite'] ?? 'secondary',
                'details' => [
                    ['Rés. banques (CDF)', $data['reserves'] ? number_format((float) $data['reserves']->getReservesBanquesCdf(), 0, ',', ' ') : 'N/D'],
                    ['Encours OT-BCC', $data['encours'] ? number_format((float) $data['encours']->getEncoursOtBcc(), 2, ',') : 'N/D'],
                    ['Encours B-BCC', $data['encours'] ? number_format((float) $data['encours']->getEncoursBBcc(), 2, ',') : 'N/D'],
                    ['Ratio stérilisation', $data['ratioSteri'] !== null ? number_format($data['ratioSteri'], 2, ',') : 'N/D'],
                    ['Solde CGT (J)', isset($data['soldeCgt']) && $data['soldeCgt'] !== null ? number_format((float)$data['soldeCgt'], 0, ',', ' ') . ' Mds' : 'N/D'],
                    ['Variation CGT J-1', isset($data['variationCgt']) && $data['variationCgt'] !== null
                        ? (($data['variationCgt'] > 10 ? '\u25b2 Absorption ' : ($data['variationCgt'] < -10 ? '\u25bc Injection ' : '\u2248 Stable ')) . number_format(abs((float)$data['variationCgt']), 0, ',') . ' Mds')
                        : 'N/D'],
                ],
            ],
            [
                'title' => 'Pilier IV â€” Finances Publiques & Trésorerie',
                'value' => ($data['tresorerie'] && $data['tresorerie']->getSoldeAvantFin())
                    ? number_format((float) $data['tresorerie']->getSoldeAvantFin(), 0, ',', ' ')
                    : 'N/D',
                'sub' => 'Solde avant fin (Mds CDF)',
                'signal' => $data['signalTresorerie'] ?? 'secondary',
                'details' => [
                    ['Recettes totales', $data['finances'] ? number_format((float) $data['finances']->getRecettesTotales(), 2, ',', ' ') : 'N/D'],
                    ['Dépenses totales', $data['finances'] ? number_format((float) $data['finances']->getDepensesTotales(), 2, ',', ' ') : 'N/D'],
                    ['Solde après fin', $data['tresorerie'] ? number_format((float) $data['tresorerie']->getSoldeApresFin(), 2, ',', ' ') : 'N/D'],
                    ['Paie exécutée', $data['tauxPaie'] !== null ? number_format($data['tauxPaie'], 1, ',') . ' %' : 'N/D'],
                    ['Reste à payer', $data['paie'] ? number_format((float) $data['paie']->getMontantRestant(), 2, ',', ' ') . ' Mds' : 'N/D'],
                ],
            ],
        ];

        $cardW = 218;
        $cardX = [18, 252, 486, 720];
        $cardY = 100;
        $cardH = 450;

        foreach ($pillars as $i => $pillar) {
            $this->buildPillarCard($slide, $cardX[$i], $cardY, $cardW, $cardH, $pillar);
        }
    }

    private function buildPillarCard($slide, int $x, int $y, int $w, int $h, array $pillar): void
    {
        $signal = $pillar['signal'];
        $colors = self::SIGNAL_COLORS[$signal] ?? self::SIGNAL_COLORS['secondary'];

        // Card background
        $this->addFilledRect($slide, $x, $y, $w, $h, 'FFFFFF', $colors['border'], 4);

        // Colored top bar (signal)
        $this->addFilledRect($slide, $x, $y, $w, 5, $colors['border']);

        // Signal badge
        $this->addFilledRect($slide, $x + 5, $y + 10, $w - 10, 20, $colors['bg']);
        $this->addTextBox($slide, $colors['label'], $x + 7, $y + 12, $w - 14, 17, 9, $colors['fg'], true);

        // Pillar title
        $this->addTextBox($slide, strtoupper($pillar['title']), $x + 7, $y + 35, $w - 14, 28, 8, '777777', true);

        // Main value
        $this->addTextBox($slide, $pillar['value'], $x + 7, $y + 65, $w - 14, 38, 22, self::BCC_BLUE_DARK, true);

        // Sub label
        $this->addTextBox($slide, $pillar['sub'], $x + 7, $y + 103, $w - 14, 22, 8, '999999', false, true);

        // Divider
        $this->addFilledRect($slide, $x + 7, $y + 127, $w - 14, 1, 'e0e0e0');

        // Details table
        $rowY = $y + 134;
        foreach ($pillar['details'] as [$label, $val]) {
            $this->addTextBox($slide, $label, $x + 7, $rowY, ($w - 14) * 0.58, 22, 8, '666666');
            $this->addTextBox($slide, $val, $x + 7 + ($w - 14) * 0.58, $rowY, ($w - 14) * 0.42, 22, 8, '222222', true, false, 'right');
            $rowY += 22;
            if ($rowY > $y + $h - 15)
                break;
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // SLIDE 3 â€” Synthèse & Seuils
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function buildSlide3Synthesis(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, 'FFFFFF');

        // Header
        $this->addFilledRect($slide, 0, 0, 960, 75, self::BCC_BLUE);
        $this->addTextBox(
            $slide,
            'Alertes Opérationnelles & Seuils Calibrés de Référence',
            20,
            12,
            700,
            36,
            18,
            self::BCC_WHITE,
            true
        );
        $dateStr = isset($data['date']) ? $data['date']->format('d/m/Y') : 'N/D';
        $this->addTextBox($slide, 'Situation au ' . $dateStr, 20, 46, 700, 22, 11, self::BCC_GOLD);

        // Attention points
        $this->addTextBox($slide, 'POINTS D\'ATTENTION IMMÃ‰DIATS', 20, 88, 450, 20, 9, '888888', true);

        $attnItems = [];

        $signalChange = $data['signalChange'] ?? 'secondary';
        $signalLiquidite = $data['signalLiquidite'] ?? 'secondary';
        $signalTresorerie = $data['signalTresorerie'] ?? 'secondary';
        $signalPaie = $data['signalPaie'] ?? 'secondary';

        if (in_array($signalChange, ['orange', 'red'])) {
            $ecartStr = $data['ecartPct'] !== null ? number_format($data['ecartPct'], 1, ',') . ' %' : 'N/D';
            $attnItems[] = ['ðŸŸ  Désalignement du Taux de Change', "Ã‰cart indicatif/parallèle de $ecartStr â€” dépasse le seuil de vigilance. Vérifier la cohérence du taux directeur et envisager une intervention.", 'ffe5cc', '8a4700'];
        }
        if (in_array($signalLiquidite, ['orange', 'red'])) {
            $avStr = ($data['reserves'] && $data['reserves']->getAvoirsLibresCdf())
                ? number_format((float) $data['reserves']->getAvoirsLibresCdf(), 0, ',', ' ') . ' Mds CDF'
                : 'N/D';
            $attnItems[] = ['ðŸ’§ Excès Structurel de Liquidité Bancaire', "Avoirs libres à $avStr â€” exposition au risque de change. Intensifier les opérations d'absorption (B-BCC/OT-BCC).", 'cfe2ff', '084298'];
        }
        if (in_array($signalTresorerie, ['yellow', 'orange', 'red'])) {
            $soldeStr = ($data['tresorerie'] && $data['tresorerie']->getSoldeAvantFin())
                ? number_format((float) $data['tresorerie']->getSoldeAvantFin(), 0, ',', ' ') . ' Mds'
                : 'N/D';
            $attnItems[] = ['ðŸ›ï¸ Tension de Trésorerie de l\'Ã‰tat', "Solde de trésorerie à $soldeStr â€” risque de monétisation. Coordonner le calendrier de paiements avec la BCC.", 'fff3cd', '856404'];
        }
        if (in_array($signalPaie, ['orange', 'red'])) {
            $resteStr = $data['paie'] ? number_format((float) $data['paie']->getMontantRestant(), 0, ',', ' ') . ' Mds' : 'N/D';
            $attnItems[] = ['ðŸ’¼ Pression Salariale Différée â€” Risque de Liquidité', "Arriérés de paie de $resteStr non décaissés â€” risque d'injection monétaire non stérilisée à anticiper.", 'f8d7da', '721c24'];
        }
        if (empty($attnItems)) {
            $attnItems[] = ['âœ… Indicateurs dans les Normes â€” Maintien de la Vigilance', 'L\'ensemble des indicateurs se situent dans les zones de référence. La discipline monétaire en vigueur peut être maintenue sous surveillance continue.', 'd4edda', '155724'];
        }

        $itemY = 112;
        foreach ($attnItems as [$title, $body, $bg, $fg]) {
            $this->addFilledRect($slide, 20, $itemY, 450, 50, $bg);
            $this->addTextBox($slide, $title, 28, $itemY + 5, 434, 20, 10, $fg, true);
            $this->addTextBox($slide, $body, 28, $itemY + 24, 434, 22, 9, $fg, false, true);
            $itemY += 58;
            if ($itemY > 490)
                break;
        }

        // Thresholds table (right column)
        $this->addTextBox($slide, 'SEUILS D\'ALERTE DE RÃ‰FÃ‰RENCE', 490, 88, 450, 20, 9, '888888', true);

        $rows = [
            ['Ã‰cart moyen indic./parallèle', 'â‰¤ 2%', '2â€“3%', '3â€“5%', '> 5%'],
            ['Ã‰cart max (borne haute)', 'â‰¤ 3%', 'â€”', '3â€“6%', '> 6%'],
            ['Avoirs libres (Mds CDF)', '< 800', 'â€”', '800â€“1 200', '> 1 200'],
            ['Solde trésorerie avant fin', '> 0', '0 à âˆ’100', 'â€”', '< âˆ’100'],
            ['Reste paie / total', 'â€”', '< 20%', '20â€“50%', '> 50%'],
        ];

        $headers = ['Indicateur', 'ðŸŸ¢ Normal', 'ðŸŸ¡ Vigil.', 'ðŸŸ  Alerte', 'ðŸ”´ Critique'];
        $colW = [160, 60, 65, 75, 80];
        // Build X offset for each column: first starts at 490, each next adds previous width
        $colX = [490];
        foreach ($colW as $k => $cw) {
            if ($k < count($colW) - 1) {
                $colX[] = $colX[$k] + $cw;
            }
        }

        // Header row
        $rowY = 113;
        foreach ($headers as $j => $h) {
            $this->addFilledRect($slide, $colX[$j], $rowY, $colW[$j], 24, self::BCC_BLUE_DARK);
            $this->addTextBox($slide, $h, $colX[$j] + 3, $rowY + 4, $colW[$j] - 6, 18, 8, self::BCC_WHITE, true, false, $j > 0 ? 'center' : 'left');
        }
        $rowY += 24;

        foreach ($rows as $ri => $row) {
            $rowBg = $ri % 2 === 0 ? 'FFFFFF' : 'f4f7fb';
            foreach ($row as $j => $cell) {
                $this->addFilledRect($slide, $colX[$j], $rowY, $colW[$j], 24, $rowBg, 'e0e0e0', 1);
                $this->addTextBox($slide, $cell, $colX[$j] + 3, $rowY + 4, $colW[$j] - 6, 18, 8, '333333', false, false, $j > 0 ? 'center' : 'left');
            }
            $rowY += 24;
        }

        // Global signal
        $worstSignal = $data['worstSignal'] ?? 'secondary';
        $ws = self::SIGNAL_COLORS[$worstSignal] ?? self::SIGNAL_COLORS['secondary'];
        $this->addFilledRect($slide, 490, $rowY + 12, 440, 38, $ws['bg'], $ws['border'], 2);
        $this->addTextBox(
            $slide,
            'Signal global : ' . $ws['label'],
            498,
            $rowY + 20,
            425,
            24,
            13,
            $ws['fg'],
            true,
            false,
            'center'
        )->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Footer
        $this->addFilledRect($slide, 0, 565, 960, 25, 'eeeeee');
        $this->addTextBox(
            $slide,
            'BCC-Flex · Document généré le ' . (new \DateTime())->format('d/m/Y à H:i') . ' · Document confidentiel â€” Usage interne Cabinet du Gouverneur',
            20,
            569,
            920,
            18,
            8,
            '999999',
            false,
            false,
            'center'
        )->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function setSlideBackgroundColor($slide, string $hex): void
    {
        $bg = new \PhpOffice\PhpPresentation\Slide\Background\Color();
        $bg->setColor(new Color('FF' . ltrim($hex, '#')));
        $slide->setBackground($bg);
    }

    private function addFilledRect(
        $slide,
        int $x,
        int $y,
        int $w,
        int $h,
        string $fillColor,
        ?string $borderColor = null,
        int $borderWidth = 0
    ) {
        // Use a rectangle via RichText with background fill
        $shape = new \PhpOffice\PhpPresentation\Shape\RichText();
        $shape->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($h);

        $fill = new Fill();
        $fill->setFillType(Fill::FILL_SOLID);
        $fill->setStartColor(new Color('FF' . ltrim($fillColor, '#')));
        $shape->getFill()->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('FF' . ltrim($fillColor, '#')));

        if ($borderColor && $borderWidth > 0) {
            $shape->getBorder()->setLineStyle(Border::LINE_SINGLE)
                ->setLineWidth($borderWidth)
                ->setColor(new Color('FF' . ltrim($borderColor, '#')));
        }

        $slide->addShape($shape);
        return $shape;
    }

    private function addTextBox(
        $slide,
        string $text,
        int $x,
        int $y,
        int $w,
        int $h,
        int $fontSize = 11,
        string $colorHex = '000000',
        bool $bold = false,
        bool $italic = false,
        string $align = 'left'
    ) {
        $shape = new RichText();
        $shape->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($h);
        $shape->getActiveParagraph()->getAlignment()
            ->setHorizontal(match ($align) {
                'center' => Alignment::HORIZONTAL_CENTER,
                'right' => Alignment::HORIZONTAL_RIGHT,
                default => Alignment::HORIZONTAL_LEFT,
            });

        $run = $shape->createTextRun($text);
        $run->getFont()->setName('Calibri')->setSize($fontSize)->setBold($bold)->setItalic($italic);
        $run->getFont()->setColor(new Color('FF' . ltrim($colorHex, '#')));

        $shape->getFill()->setFillType(Fill::FILL_NONE);
        $shape->getBorder()->setLineStyle(Border::LINE_NONE);

        $slide->addShape($shape);
        return $shape;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // POLITIQUE MONÃ‰TAIRE â€” Indicateurs de Période
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Génère un PPTX de 4 slides "Politique Monétaire" sur période libre.
     */
    public function generatePolitiqueMonetaire(array $data): string
    {
        $prs = new PhpPresentation();
        $prs->getDocumentProperties()
            ->setTitle('Indicateurs de Période â€” Politique Monétaire')
            ->setDescription('Orientations de politique monétaire â€” BCC Cabinet')
            ->setCompany('Banque Centrale du Congo');
        $prs->removeSlideByIndex(0);

        $this->buildPMSlide1Cover($prs, $data);
        $this->buildPMSlide2Pillars($prs, $data);
        $this->buildPMSlide3TauxDirecteur($prs, $data);
        $this->buildPMSlide4Seuils($prs, $data);

        $tmpFile = tempnam(sys_get_temp_dir(), 'bcc_pm_') . '.pptx';
        $writer = IOFactory::createWriter($prs, 'PowerPoint2007');
        $writer->save($tmpFile);
        return $tmpFile;
    }

    private function fmtD(mixed $v): string
    {
        if ($v instanceof \DateTimeInterface)
            return $v->format('d/m/Y');
        return $v ? (string) $v : 'N/D';
    }

    private function buildPMSlide1Cover(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, self::BCC_BLUE_DARK);

        $dp = $this->fmtD($data['dateDebut'] ?? null);
        $df = $this->fmtD($data['dateFin'] ?? null);
        $periode = "$dp â€” $df";

        $logoPath = __DIR__ . '/../../public/images/bcc-logo.png';
        if (file_exists($logoPath)) {
            $logo = new \PhpOffice\PhpPresentation\Shape\Drawing\File();
            $logo->setPath($logoPath)->setOffsetX(380)->setOffsetY(35)->setHeight(110);
            $slide->addShape($logo);
        }

        $this->addTextBox($slide, 'BANQUE CENTRALE DU CONGO â€” Cabinet du Gouverneur', 50, 172, 860, 28, 12, self::BCC_WHITE, false, false, 'center')
            ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $tb = $this->addTextBox($slide, 'Note d\'Orientation de Politique Monétaire', 50, 200, 860, 66, 29, self::BCC_WHITE, true, false, 'center');
        $tb->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->addTextBox($slide, 'Analyse de Période : ' . $periode, 50, 270, 860, 38, 15, self::BCC_GOLD, true, false, 'center')
            ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->addFilledRect($slide, 300, 313, 360, 3, 'FFFFFF');

        // Badge recommandation
        $reco = $data['recoTaux'] ?? ['action' => 'N/D', 'label' => 'N/D', 'justification' => '', 'emoji' => 'ðŸ“Š'];
        $signal = $data['signalGlobal'] ?? 'secondary';
        $bgMap = ['red' => 'f8d7da', 'orange' => 'ffe5cc', 'yellow' => 'fff3cd', 'green' => 'd4edda', 'secondary' => 'e9ecef'];
        $fgMap = ['red' => '721c24', 'orange' => '8a4700', 'yellow' => '856404', 'green' => '155724', 'secondary' => '6c757d'];
        $bg = $bgMap[$signal] ?? 'e9ecef';
        $fg = $fgMap[$signal] ?? '6c757d';

        $this->addFilledRect($slide, 120, 325, 720, 88, $bg);
        $emoji = $reco['emoji'] ?? 'ðŸ“Š';
        $this->addTextBox($slide, $emoji . '  Recommandation : ' . ($reco['action'] ?? 'N/D'), 135, 335, 685, 30, 15, $fg, true, false, 'center')
            ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->addTextBox($slide, $reco['label'] ?? '', 135, 367, 685, 20, 11, $fg, false, true, 'center')
            ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $td = $data['tauxDirecteur'] ?? null;
        if ($td !== null) {
            $this->addTextBox($slide, 'Taux directeur en vigueur : ' . number_format((float) $td, 1, ',', ' ') . ' %', 135, 389, 685, 20, 10, $fg, false, false, 'center')
                ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Synthèse
        $this->addFilledRect($slide, 50, 426, 860, 104, self::BCC_LIGHT, self::BCC_BLUE);
        $this->addTextBox($slide, "Synthèse de la Situation", 70, 435, 820, 18, 10, self::BCC_BLUE, true);
        $this->addTextBox($slide, $reco['justification'] ?? '', 70, 452, 820, 70, 10, self::BCC_BLUE_DARK, false, true);

        $this->addTextBox($slide, 'Document confidentiel â€” Usage interne Cabinet du Gouverneur · BCC-Flex · ' . (new \DateTime())->format('d/m/Y'), 50, 556, 860, 20, 9, 'aaaaaa', false, false, 'center')
            ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function buildPMSlide2Pillars(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, 'f8f9fa');
        $this->addFilledRect($slide, 0, 0, 960, 78, self::BCC_BLUE_DARK);

        $dp = $this->fmtD($data['dateDebut'] ?? null);
        $df = $this->fmtD($data['dateFin'] ?? null);
        $this->addTextBox($slide, 'Analyse des 4 Piliers de Stabilité Monétaire', 20, 12, 700, 32, 17, self::BCC_WHITE, true);
        $this->addTextBox($slide, "Période $dp â€” $df", 20, 46, 700, 22, 11, self::BCC_GOLD);

        $ch = $data['change'] ?? [];
        $rv = $data['reserves'] ?? [];
        $en = $data['encours'] ?? [];
        $fi = $data['finances'] ?? [];

        $n = fn($v, int $dec = 2, string $suf = '') => $v !== null ? number_format((float) $v, $dec, ',', ' ') . $suf : 'N/D';

        $pillars = [
            [
                'title' => 'Pilier I â€” Marché des Changes & Parités',
                'value' => $data['ecartMoy'] !== null ? $n($data['ecartMoy']) . ' %' : 'N/D',
                'sub' => 'Ã‰cart moyen indicatif / parallèle',
                'signal' => $data['signalChange'] ?? 'secondary',
                'details' => [
                    ['Ã‰cart moyen %', $n($data['ecartMoy']) . ' %'],
                    ['Ã‰cart max %', $n($data['ecartMax']) . ' %'],
                    ['Cours indicatif moy.', $n($ch['cours_indicatif_moy'] ?? null) . ' CDF'],
                    ['Jours d\'observation', $ch['nb_jours'] ?? 'N/D'],
                ],
            ],
            [
                'title' => 'Pilier II â€” Position Extérieure & Réserves',
                'value' => $data['reservesIntMoy'] !== null ? $n($data['reservesIntMoy'] / 1000) . ' Md$' : 'N/D',
                'sub' => 'Réserves int. moyennes (Md$)',
                'signal' => 'green',
                'details' => [
                    ['Réserves int. moy. (Md$)', $data['reservesIntMoy'] !== null ? $n($data['reservesIntMoy'] / 1000) . ' Md$' : 'N/D'],
                    ['Avoirs ext. moy. (Md$)', isset($rv['avoirs_ext_moy']) ? $n($rv['avoirs_ext_moy'] / 1000) . ' Md$' : 'N/D'],
                    ['Min. Réserves', isset($rv['reserves_int_min']) ? $n($rv['reserves_int_min'] / 1000) . ' Md$' : 'N/D'],
                    ['Max. Réserves', isset($rv['reserves_int_max']) ? $n($rv['reserves_int_max'] / 1000) . ' Md$' : 'N/D'],
                ],
            ],
            [
                'title' => 'Pilier III â€” Liquidité Bancaire & Stérilisation',
                'value' => $data['encoursBonsMoy'] !== null ? $n($data['encoursBonsMoy'], 0) . ' Mds' : 'N/D',
                'sub' => 'Encours Bons BCC moy. (Mds CDF)',
                'signal' => $data['signalLiquidite'] ?? 'secondary',
                'details' => [
                    ['Éncours Bons moy.', $data['encoursBonsMoy'] !== null ? $n($data['encoursBonsMoy'], 0) . ' Mds' : 'N/D'],
                    ['Taux Interbancaire', $data['tauxInterbancaireMoy'] !== null ? $n($data['tauxInterbancaireMoy'], 2) . '%' : 'N/D'],
                    ['TMP BBCC', $data['tauxMoyenPondereMoy'] !== null ? $n($data['tauxMoyenPondereMoy'], 2) . '%' : 'N/D'],
                    ['Billets en circ.', $data['billetsCirculationMoy'] !== null ? $n($data['billetsCirculationMoy'], 0) . ' Mds' : 'N/D'],
                    ['Solde CGT moy.', isset($data['soldeCgtMoy']) && $data['soldeCgtMoy'] !== null ? $n($data['soldeCgtMoy'], 0) . ' Mds' : 'N/D'],
                    ['Var. CGT cumulée', isset($data['variationCgtCumulee']) && $data['variationCgtCumulee'] !== null
                        ? (($data['variationCgtCumulee'] > 10 ? '▲ ' : ($data['variationCgtCumulee'] < -10 ? '▼ ' : '≈ ')) . $n(abs($data['variationCgtCumulee']), 0) . ' Mds')
                        : 'N/D'],
                ],
            ],
            [
                'title' => 'Pilier 4 â€” Budget & Treso.',
                'value' => $data['soldeMoy'] !== null ? $n($data['soldeMoy'], 0) . ' Mds' : 'N/D',
                'sub' => 'Solde de trésorerie moyen (Mds CDF)',
                'signal' => $data['signalTresorerie'] ?? 'secondary',
                'details' => [
                    ['Solde moyen', $data['soldeMoy'] !== null ? $n($data['soldeMoy'], 0) . ' Mds' : 'N/D'],
                    ['Solde minimum', isset($fi['solde_min']) ? $n($fi['solde_min'], 0) . ' Mds' : 'N/D'],
                    ['Solde maximum', isset($fi['solde_max']) ? $n($fi['solde_max'], 0) . ' Mds' : 'N/D'],
                    ['Recettes cumulées', isset($fi['recettes_cumul']) ? $n($fi['recettes_cumul'], 0) . ' Mds' : 'N/D'],
                    ['Dépenses cumulées', isset($fi['depenses_cumul']) ? $n($fi['depenses_cumul'], 0) . ' Mds' : 'N/D'],
                ],
            ],
        ];

        $cardW = 220;
        $cardX = [18, 256, 494, 732];
        $cardY = 90;
        $cardH = 460;
        foreach ($pillars as $i => $p) {
            $this->buildPillarCard($slide, $cardX[$i], $cardY, $cardW, $cardH, $p);
        }
    }

    private function buildPMSlide3TauxDirecteur(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, 'FFFFFF');
        $this->addFilledRect($slide, 0, 0, 960, 75, self::BCC_BLUE);
        $this->addTextBox($slide, 'Recommandation sur le Taux Directeur â€” Analyse de Période', 20, 10, 700, 34, 16, self::BCC_WHITE, true);
        $dp = $this->fmtD($data['dateDebut'] ?? null);
        $df = $this->fmtD($data['dateFin'] ?? null);
        $this->addTextBox($slide, "Période $dp â€” $df", 20, 48, 700, 20, 11, self::BCC_GOLD);

        $reco = $data['recoTaux'] ?? ['action' => 'N/D', 'label' => 'N/D', 'emoji' => 'ðŸ“Š', 'justification' => '', 'taux' => 0];
        $signal = $data['signalGlobal'] ?? 'secondary';
        $colors = self::SIGNAL_COLORS[$signal] ?? self::SIGNAL_COLORS['secondary'];

        // Badge recommandation
        $this->addFilledRect($slide, 30, 88, 430, 130, $colors['bg'], $colors['border'], 3);
        $emoji = $reco['emoji'] ?? 'ðŸ“Š';
        $this->addTextBox($slide, $emoji . '  RECOMMANDATION', 45, 98, 400, 20, 10, $colors['fg'], true);
        $this->addTextBox($slide, $reco['action'] ?? '', 45, 120, 400, 44, 25, $colors['fg'], true);
        $this->addTextBox($slide, $reco['label'] ?? '', 45, 166, 400, 20, 10, $colors['fg'], false, true);

        // Taux directeur
        $td = $data['tauxDirecteur'] ?? null;
        $tdP = $data['tauxDirecteurPrec'] ?? null;
        $this->addFilledRect($slide, 480, 88, 450, 130, self::BCC_LIGHT, self::BCC_BLUE, 2);
        $this->addTextBox($slide, 'Taux Directeur BCC', 495, 98, 410, 20, 10, self::BCC_BLUE, true);
        $tdStr = $td !== null ? number_format((float) $td, 1, ',', ' ') . ' %' : 'N/D';
        $this->addTextBox($slide, $tdStr, 495, 120, 410, 52, 33, self::BCC_BLUE_DARK, true);
        if ($tdP !== null && $tdP != $td) {
            $sens = $td > $tdP ? 'Hausse depuis' : 'Baisse depuis';
            $this->addTextBox($slide, $sens . ' ' . number_format($tdP, 1, ',') . ' %', 495, 175, 410, 20, 10, '888888', false, true);
        } else {
            $this->addTextBox($slide, 'Inchange', 495, 175, 410, 20, 10, '888888', false, true);
        }

        // Justification
        $this->addFilledRect($slide, 30, 232, 900, 160, 'f8f9fa', 'dddddd', 1);
        $this->addTextBox($slide, 'ANALYSE & JUSTIFICATION', 45, 240, 870, 16, 9, '888888', true);
        $this->addTextBox($slide, $reco['justification'] ?? '', 45, 258, 870, 126, 11, '333333', false, true);

        // Grille 4 piliers
        $this->addTextBox($slide, 'Ã‰TAT DES PILIERS SUR LA PÃ‰RIODE', 30, 408, 900, 16, 9, '888888', true);
        $n = fn($v, int $dec = 1, string $suf = '') => $v !== null ? number_format((float) $v, $dec, ',', ' ') . $suf : 'N/D';
        $pillarsGrid = [
            ['Marché des Changes', $data['signalChange'] ?? 'secondary', $data['ecartMoy'] !== null ? $n($data['ecartMoy'], 2) . '% moy' : 'N/D'],
            ['Réserves Ext.', 'green', $data['reservesIntMoy'] !== null ? $n($data['reservesIntMoy'] / 1000) . ' Md$' : 'N/D'],
            ['Marché Monétaire', $data['signalLiquidite'] ?? 'secondary', $data['encoursBonsMoy'] !== null ? $n($data['encoursBonsMoy'], 0) . ' moy' : 'N/D'],
            ['Budget / Trésorerie', $data['signalTresorerie'] ?? 'secondary', $data['soldeMoy'] !== null ? $n($data['soldeMoy'], 0) . ' moy' : 'N/D'],
        ];
        $px = [30, 258, 486, 714];
        $pw = 214;
        foreach ($pillarsGrid as $i => [$p, $s, $v]) {
            $c = self::SIGNAL_COLORS[$s] ?? self::SIGNAL_COLORS['secondary'];
            $this->addFilledRect($slide, $px[$i], 428, $pw, 62, $c['bg'], $c['border'], 2);
            $this->addFilledRect($slide, $px[$i], 428, $pw, 5, $c['border']);
            $this->addTextBox($slide, $p, $px[$i] + 6, 436, $pw - 12, 18, 8, $c['fg'], true);
            $this->addTextBox($slide, $v, $px[$i] + 6, 454, $pw - 12, 18, 12, $c['fg'], true);
            $this->addTextBox($slide, $c['label'], $px[$i] + 6, 472, $pw - 12, 14, 8, $c['fg']);
        }

        $this->addTextBox($slide, 'BCC-Flex · ' . (new \DateTime())->format('d/m/Y') . ' â€” Document confidentiel â€” Usage interne Cabinet du Gouverneur', 30, 558, 900, 16, 8, '999999', false, false, 'center')
            ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function buildPMSlide4Seuils(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, 'FFFFFF');
        $this->addFilledRect($slide, 0, 0, 960, 68, self::BCC_BLUE_DARK);
        $this->addTextBox($slide, 'Cadre Analytique â€” Seuils de Référence & Règles de Décision Monétaire', 20, 8, 900, 30, 14, self::BCC_WHITE, true);
        $this->addTextBox($slide, 'Cadre analytique â€” Banque Centrale du Congo', 20, 42, 700, 18, 10, self::BCC_GOLD);

        // Règle taux directeur
        $this->addTextBox($slide, 'RÃˆGLE TAUX DIRECTEUR', 20, 82, 900, 16, 9, '888888', true);
        $bgMap = ['d4edda', 'fff3cd', 'ffe5cc', 'f8d7da'];
        $fgMap = ['155724', '856404', '8a4700', '721c24'];
        $regleRows = [
            ['Ã‰cart moy. < 2% ET avoirs libres < 800 Mds CDF', 'Zone Verte', 'Assouplissement prudent envisageable'],
            ['Ã‰cart moy. 2â€“3% ET liquidité dans la norme', 'Zone Jaune', 'Neutralité restrictive â€” Surveillance renforcée'],
            ['Ã‰cart moy. > 3% OU avoirs libres > 800 Mds CDF', 'Zone Orange', 'Abstention de baisse â€” Stérilisation prioritaire'],
            ['Ã‰cart moy. > 5% OU avoirs libres > 1â€¯200 Mds CDF', 'Zone Rouge', 'Maintien ou resserrement â€” Action coordonnée urgente'],
        ];
        $ry = 102;
        foreach ($regleRows as $ri => [$cond, $zone, $action]) {
            $bg = $bgMap[$ri];
            $fg = $fgMap[$ri];
            $this->addFilledRect($slide, 20, $ry, 500, 30, $bg);
            $this->addTextBox($slide, $cond, 28, $ry + 8, 488, 18, 9, $fg, false, true);
            $this->addFilledRect($slide, 525, $ry, 130, 30, $bg);
            $this->addTextBox($slide, $zone, 530, $ry + 8, 120, 18, 9, $fg, true, false, 'center')
                ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->addFilledRect($slide, 660, $ry, 280, 30, $bg);
            $this->addTextBox($slide, $action, 665, $ry + 8, 268, 18, 9, $fg, false, true);
            $ry += 33;
        }

        // Tableau seuils
        $this->addTextBox($slide, 'SEUILS INDICATEURS PAR ZONE', 20, 248, 900, 16, 9, '888888', true);
        $headers = ['Indicateur', 'Vert', 'Jaune', 'Orange', 'Rouge'];
        $sRows = [
            ['Ã‰cart moy. indicatif / parallèle', '< 2 %', '2â€“3 %', '3â€“5 %', '> 5 %'],
            ['Ã‰cart max (borne haute)', '< 3 %', 'â€”', '3â€“6 %', '> 6 %'],
            ['Avoirs libres (Mds CDF)', '< 500', '500â€“800', '800â€“1â€¯200', '> 1â€¯200'],
            ['Solde trésorerie (Mds CDF)', '> 0', '0 à -100', '-100 à -200', '< -200'],
            ['Réserves int. (Md USD)', '> 4,0', '2,5â€“4,0', '1,5â€“2,5', '< 1,5'],
        ];
        $cw = [185, 80, 90, 100, 92];
        $cx = [20];
        foreach ($cw as $k => $w) {
            if ($k < count($cw) - 1)
                $cx[] = $cx[$k] + $w;
        }
        $ry = 268;
        foreach ($headers as $j => $h) {
            $this->addFilledRect($slide, $cx[$j], $ry, $cw[$j], 24, self::BCC_BLUE_DARK);
            $this->addTextBox($slide, $h, $cx[$j] + 4, $ry + 5, $cw[$j] - 8, 16, 8, self::BCC_WHITE, true, false, $j > 0 ? 'center' : 'left');
        }
        $ry += 24;
        foreach ($sRows as $ri => $row) {
            $rb = $ri % 2 === 0 ? 'FFFFFF' : 'f4f7fb';
            foreach ($row as $j => $cell) {
                $this->addFilledRect($slide, $cx[$j], $ry, $cw[$j], 24, $rb, 'e0e0e0', 1);
                $this->addTextBox($slide, $cell, $cx[$j] + 4, $ry + 5, $cw[$j] - 8, 16, 8, '333333', false, false, $j > 0 ? 'center' : 'left');
            }
            $ry += 24;
        }

        // Encart principe
        $this->addFilledRect($slide, 560, 248, 380, 192, self::BCC_LIGHT, self::BCC_BLUE, 1);
        $this->addTextBox($slide, "Principe d'Arbitrage Monétaire", 572, 258, 356, 18, 10, self::BCC_BLUE, true);
        $principe = "Dans une économie fortement dollarisée comme la RDC, le taux directeur est un signal de crédibilité dont la portée dépasse le coût du refinancement. "
            . "Sa baisse peut alimenter des anticipations de dépréciation si le marché parallèle n'est pas aligné. "
            . "La règle opérationnelle : le taux directeur ne baisse que si l'écart de change moyen est stabilisé sous 2% "
            . "ET que la surliquidité est maîtrisée par la stérilisation (B-BCC/OT-BCC). "
            . "Toute décision doit s'accompagner d'une communication calibrée pour ne pas raviver les anticipations de dépréciation.";
        $this->addTextBox($slide, $principe, 572, 280, 356, 148, 9, self::BCC_BLUE_DARK, false, true);

        $this->addTextBox($slide, 'BCC-Flex â€” Cadre de référence Cabinet â€” Document confidentiel â€” Usage interne Cabinet du Gouverneur', 20, 558, 920, 16, 8, '999999', false, false, 'center')
            ->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}

