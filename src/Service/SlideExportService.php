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
        'green' => ['bg' => 'd4edda', 'fg' => '155724', 'border' => '38a169', 'label' => '🟢 Normal'],
        'yellow' => ['bg' => 'fff3cd', 'fg' => '856404', 'border' => 'ecc94b', 'label' => '🟡 Vigilance'],
        'orange' => ['bg' => 'ffe5cc', 'fg' => '8a4700', 'border' => 'dd6b20', 'label' => '🟠 Alerte'],
        'red' => ['bg' => 'f8d7da', 'fg' => '721c24', 'border' => 'c53030', 'label' => '🔴 Critique'],
        'secondary' => ['bg' => 'e9ecef', 'fg' => '6c757d', 'border' => 'adb5bd', 'label' => '⚪ N/D'],
    ];

    /**
     * Génère le PPTX et retourne le chemin vers le fichier temporaire.
     */
    public function generate(array $data): string
    {
        $prs = new PhpPresentation();
        $prs->getDocumentProperties()
            ->setTitle('Fiche Journalière BCC')
            ->setDescription('Fiche Quotidienne de Décision — Stabilité Monétaire')
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

    // ─────────────────────────────────────────────────────────────────────────
    // SLIDE 1 — Couverture
    // ─────────────────────────────────────────────────────────────────────────

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
            'BANQUE CENTRALE DU CONGO — Cabinet',
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
            'Fiche Quotidienne — Stabilité Monétaire',
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
            'Usage interne — Confidentiel Cabinet · BCC-Flex · ' . (new \DateTime())->format('d/m/Y'),
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

    // ─────────────────────────────────────────────────────────────────────────
    // SLIDE 2 — 4 Piliers
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSlide2Pillars(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, 'f8f9fa');

        // Header banner
        $this->addFilledRect($slide, 0, 0, 960, 85, self::BCC_BLUE_DARK);
        $dateStr = isset($data['date']) ? $data['date']->format('d/m/Y') : 'N/D';
        $this->addTextBox(
            $slide,
            'Fiche Journalière — 4 Piliers de Surveillance',
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
                'title' => 'Pilier 1 — Marché des Changes',
                'value' => $data['ecartPct'] !== null ? number_format($data['ecartPct'], 2, ',', ' ') . ' %' : 'N/D',
                'sub' => 'Écart moyen indicatif / parallèle',
                'signal' => $data['signalChange'] ?? 'secondary',
                'details' => [
                    ['Taux indicatif', $data['marche'] ? number_format((float) $data['marche']->getCoursIndicatif(), 4, ',', ' ') . ' CDF' : 'N/D'],
                    ['Parallèle A/V', $data['marche'] ? number_format((float) $data['marche']->getParalleleAchat(), 2, ',') . ' / ' . number_format((float) $data['marche']->getParalleleVente(), 2, ',') : 'N/D'],
                    ['Écart absolu', $data['marche'] ? number_format((float) $data['marche']->getEcartIndicParallele(), 2, ',') . ' CDF' : 'N/D'],
                    ['Écart max %', $data['ecartMaxPct'] !== null ? number_format($data['ecartMaxPct'], 2, ',') . ' %' : 'N/D'],
                    ['Spread parallèle', $data['spreadPct'] !== null ? number_format($data['spreadPct'], 2, ',') . ' %' : 'N/D'],
                ],
            ],
            [
                'title' => 'Pilier 2 — Position Extérieure',
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
                'title' => 'Pilier 3 — Liquidité & Stérilisation',
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
                ],
            ],
            [
                'title' => 'Pilier 4 — Budget & Trésorerie',
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

    // ─────────────────────────────────────────────────────────────────────────
    // SLIDE 3 — Synthèse & Seuils
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSlide3Synthesis(PhpPresentation $prs, array $data): void
    {
        $slide = $prs->createSlide();
        $this->setSlideBackgroundColor($slide, 'FFFFFF');

        // Header
        $this->addFilledRect($slide, 0, 0, 960, 75, self::BCC_BLUE);
        $this->addTextBox(
            $slide,
            'Points d\'attention & Seuils de référence',
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
        $this->addTextBox($slide, 'POINTS D\'ATTENTION IMMÉDIATS', 20, 88, 450, 20, 9, '888888', true);

        $attnItems = [];

        $signalChange = $data['signalChange'] ?? 'secondary';
        $signalLiquidite = $data['signalLiquidite'] ?? 'secondary';
        $signalTresorerie = $data['signalTresorerie'] ?? 'secondary';
        $signalPaie = $data['signalPaie'] ?? 'secondary';

        if (in_array($signalChange, ['orange', 'red'])) {
            $ecartStr = $data['ecartPct'] !== null ? number_format($data['ecartPct'], 1, ',') . ' %' : 'N/D';
            $attnItems[] = ['🟠 Désalignement de change', "Écart moyen $ecartStr. Vérifier la cohérence du taux indicatif.", 'ffe5cc', '8a4700'];
        }
        if (in_array($signalLiquidite, ['orange', 'red'])) {
            $avStr = ($data['reserves'] && $data['reserves']->getAvoirsLibresCdf())
                ? number_format((float) $data['reserves']->getAvoirsLibresCdf(), 0, ',', ' ') . ' Mds CDF'
                : 'N/D';
            $attnItems[] = ['💧 Surliquidité bancaire', "Avoirs libres $avStr. Renforcer la stérilisation.", 'cfe2ff', '084298'];
        }
        if (in_array($signalTresorerie, ['yellow', 'orange', 'red'])) {
            $soldeStr = ($data['tresorerie'] && $data['tresorerie']->getSoldeAvantFin())
                ? number_format((float) $data['tresorerie']->getSoldeAvantFin(), 0, ',', ' ') . ' Mds'
                : 'N/D';
            $attnItems[] = ['🏛️ Tension de trésorerie', "Solde avant fin $soldeStr. Coordonner le phasage.", 'fff3cd', '856404'];
        }
        if (in_array($signalPaie, ['orange', 'red'])) {
            $resteStr = $data['paie'] ? number_format((float) $data['paie']->getMontantRestant(), 0, ',', ' ') . ' Mds' : 'N/D';
            $attnItems[] = ['💼 Choc de paie à venir', "Reste $resteStr non exécuté. Anticiper l'impact.", 'f8d7da', '721c24'];
        }
        if (empty($attnItems)) {
            $attnItems[] = ['✅ Situation favorable', 'Tous les indicateurs dans les normes. Maintenir la surveillance.', 'd4edda', '155724'];
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
        $this->addTextBox($slide, 'SEUILS D\'ALERTE DE RÉFÉRENCE', 490, 88, 450, 20, 9, '888888', true);

        $rows = [
            ['Écart moyen indic./parallèle', '≤ 2%', '2–3%', '3–5%', '> 5%'],
            ['Écart max (borne haute)', '≤ 3%', '—', '3–6%', '> 6%'],
            ['Avoirs libres (Mds CDF)', '< 800', '—', '800–1 200', '> 1 200'],
            ['Solde trésorerie avant fin', '> 0', '0 à −100', '—', '< −100'],
            ['Reste paie / total', '—', '< 20%', '20–50%', '> 50%'],
        ];

        $headers = ['Indicateur', '🟢 Normal', '🟡 Vigil.', '🟠 Alerte', '🔴 Critique'];
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
            'BCC-Flex · Document généré le ' . (new \DateTime())->format('d/m/Y à H:i') . ' · Usage interne — Confidentiel Cabinet',
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

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

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
}
