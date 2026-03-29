<?php

namespace App\Service;

class TextImportParserService
{
    public function parseText(string $text): array
    {
        $data = [];
        
        // --- 1. Dates ---
        if (preg_match('/SITUATION DES INDICATEURS DE CONJONCTURE AU (\d{2}\/\d{2}\/\d{4})/u', $text, $m)) {
            $data['date_situation'] = $this->formatDate($m[1]);
        }
        if (preg_match('/applicable le (\d{2}\/\d{2}\/\d{4})/u', $text, $m)) {
            $data['date_applicable'] = $this->formatDate($m[1]);
        }

        // --- 2. Marché des changes ---
        $data['marche_changes'] = [];
        if (preg_match('/Cours Indic\. applicable[^\n]*\n\s*([\d\s,]+)/', $text, $m)) {
            $data['marche_changes']['cours_indicatif'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Parallèle\s*:\s*\n?\s*([\d\s,]+)\/([\d\s,]+)/', $text, $m)) {
            $data['marche_changes']['parallele_achat'] = $this->parseFloat($m[1]);
            $data['marche_changes']['parallele_vente'] = $this->parseFloat($m[2]);
        }
        if (preg_match('/Écart Indic\/parallèle\s*:\s*\n?\s*([\d\s,]+)/', $text, $m)) {
            $data['marche_changes']['ecart_indic_parallele'] = $this->parseFloat($m[1]);
        }

        // --- 3. Transactions USD ---
        $data['transactions'] = [];
        // Vente
        if (preg_match('/cours vend\+élevé\s*:\s*\n?\s*([\d\s,]+)\s*\n\s*Vol\.\s*USD\s*\n?\s*([\d\s,]+)\s*\n([A-Z\s]+)/i', $text, $m)) {
            $data['transactions'][] = [
                'type' => 'VENTE',
                'cours' => $this->parseFloat($m[1]),
                'volume' => $this->parseFloat($m[2]),
                'banque' => trim($m[3])
            ];
        }
        // Achat
        if (preg_match('/Cours achat\.\+élevé\s*:\s*\n?\s*([\d\s,]+)\s*\n\s*Vol\.\s*USD\s*\n?\s*([\d\s,]+)\s*\n([A-Z\s]+)/i', $text, $m)) {
            $data['transactions'][] = [
                'type' => 'ACHAT',
                'cours' => $this->parseFloat($m[1]),
                'volume' => $this->parseFloat($m[2]),
                'banque' => trim($m[3])
            ];
        }

        // --- 4. Réserves Financières ---
        $data['reserves'] = [];
        if (preg_match('/Réserves Int(?:er)?\.?\s*:\s*(?:USD\s*)?([\d ,]+)/iu', $text, $m)) {
            $data['reserves']['int_usd'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Avoirs (?:externes|en d[eé]vises)\s*:\s*(?:USD\s*)?([\d ,]+)/iu', $text, $m)) {
            $data['reserves']['ext_usd'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Réserves des banques\s*(?:\(en Mds\))?\s*[:\n]\s*(?:CDF\s*)?([\d ,]+)/iu', $text, $m)) {
            $data['reserves']['b_cdf'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Avoirs libres\s*:\s*(?:CDF\s*)?([\d ,]+)/iu', $text, $m)) {
            $data['reserves']['lib_cdf'] = $this->parseFloat($m[1]);
        }

        // --- 5. Encours BCC ---
        $data['encours'] = [];
        if (preg_match('/Encours OT-BCC\s*:\s*([\d ,]+)/iu', $text, $m)) {
            $data['encours']['ot'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Encours (?:B-BCC|bons BCC)\s*:\s*([\d ,]+)/iu', $text, $m)) {
            $data['encours']['b'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Billets?\s+en\s+circulation\s*:\s*([\d ,]+)/iu', $text, $m)) {
            $data['encours']['billets'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Taux\s+moyen\s+pond[eé]r[eé]\s+BBCC\s*:[\s\n]*-?\s*7\s+jours\s*:\s*([\d ,]+)/iu', $text, $m)) {
            $data['encours']['taux_moyen_pondere'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Taux\s+interbancaire[\s:]*\n?\s*([\d ,]+)/iu', $text, $m)) {
            $data['encours']['taux_interbancaire'] = $this->parseFloat($m[1]);
        }

        // --- 6. Finances Publiques ---
        $data['finances'] = [];
        if (preg_match('/Recettes totales\s*:\s*\n?\s*([\d\s,]+)/', $text, $m)) {
            $data['finances']['recettes_tot'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Recettes fiscales\s*:\s*\n?\s*([\d\s,]+)/', $text, $m)) {
            $data['finances']['recettes_fisc'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Autres Récettes\s*:\s*\n?\s*([\d\s,]+)/', $text, $m)) {
            $data['finances']['recettes_aut'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Dépenses\s*totales\s*:\s*\n?\s*([\d\s,]+)/', $text, $m)) {
            $data['finances']['depenses_tot'] = $this->parseFloat($m[1]);
        }

        // --- 7. Trésorerie de l'Etat ---
        $data['tresorerie'] = [];
        if (preg_match('/Solde trésorerie avant fin\s*:\s*\n?\s*([-\s\d,]+)/', $text, $m)) {
            $data['tresorerie']['avant'] = $this->parseFloat($m[1]);
        }
        // Pour après fin (on prend le premier "Solde après" qui correspond, mais le texte en a deux : "Solde après fin" et "Solde après excédent")
        if (preg_match('/Solde après fin\s*:\s*\n?\s*([-\s\d,]+)/', $text, $m)) {
            $data['tresorerie']['apres'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Solde cumul \d{4}:\s*\n?\s*([-\s\d,]+)/', $text, $m)) {
            $data['tresorerie']['cumul'] = $this->parseFloat($m[1]);
        }
        
        // Soldes : CGT, Dép Urg, Excédent, Res.s/titres
        if (preg_match('/CGT\s*:\s*([-\s\d,]+)/', $text, $m)) {
            $data['tresorerie']['cgt'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Dép Urg\s*:\s*([-\s\d,]+)/', $text, $m)) {
            $data['tresorerie']['dep_urg'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Excédent\s*:\s*([-\s\d,]+)/', $text, $m)) {
            $data['tresorerie']['exc'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Res\.s\/titres\s*:\s*([-\s\d,]+)/', $text, $m)) {
            $data['tresorerie']['res_tit'] = $this->parseFloat($m[1]);
        }

        // --- 8. Titres Publics ---
        $data['titres'] = [];
        if (preg_match('/Encours OTINDEX\s*:\s*([-\s\d,]+)/i', $text, $m)) {
            $data['titres']['ot_idx'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Encours BTIndex\s*:\s*([-\s\d,]+)/i', $text, $m)) {
            $data['titres']['bt_idx'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Encours OT USD\s*:\s*\n?\s*([-\s\d,]+)/i', $text, $m)) {
            $data['titres']['ot_usd'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/BT USD:\s*([-\s\d,]+)/i', $text, $m)) {
            $data['titres']['bt_usd'] = $this->parseFloat($m[1]);
        }

        // --- 9. Paie de l'Etat ---
        $data['paie'] = [];
        if (preg_match('/Totale\s*:\s*([-\s\d,]+)/', $text, $m)) {
            $data['paie']['tot'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Payée\s*:\s*([-\s\d,]+)/i', $text, $m)) {
            $data['paie']['paye'] = $this->parseFloat($m[1]);
        }
        if (preg_match('/Reste\s*:\s*([-\s\d,]+)/i', $text, $m)) {
            $data['paie']['reste'] = $this->parseFloat($m[1]);
        }

        return $data;
    }

    private function parseFloat(string $val): ?float
    {
        if (empty(trim($val))) return null;

        // Strip non-numeric except digits, minus sign, and comma
        // First handle cases like "- 3 032,94" -> "-3032,94"
        $val = str_replace(' ', '', $val);
        // Replace comma with dot
        $val = str_replace(',', '.', $val);
        
        if (is_numeric($val)) {
            return (float)$val;
        }

        return null; // Return null if not a valid number
    }

    private function formatDate(string $dateFr): ?string
    {
        // Convert DD/MM/YYYY to YYYY-MM-DD
        $parts = explode('/', $dateFr);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return null;
    }
}
