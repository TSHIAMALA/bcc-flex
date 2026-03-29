<?php
$f = 'src/Service/SlideExportService.php';
$c = file_get_contents($f);

// Liste des caractères fréquents abîmés
$map = [
    'é', 'è', 'ê', 'â', 'ô', 'î', 'ï', 'û', 'ç', 'à', '—', '–', '·', 'É', '’', '«', '»', '°',
    '🟢', '🟡', '🟠', '🔴', '⚪'
];

foreach ($map as $char) {
    // Génère le mojibake correspondant en traitant l'UTF-8 natif comme de l'ISO-8859-1
    $mojibake = mb_convert_encoding($char, 'UTF-8', 'ISO-8859-1');
    $c = str_replace($mojibake, $char, $c);
}

// Ré-appliquer l'ajout de "Marché Monétaire" qui a été écrasé par le git restore
$c = str_replace('Pilier III â€” LiquiditÃ© Bancaire & StÃ©rilisation', 'Pilier III — Marché Monétaire', $c);
$c = str_replace('Pilier III — Liquidité Bancaire & Stérilisation', 'Pilier III — Marché Monétaire', $c);

// Détails
$c = str_replace("['Avoirs libres moy.', \$data['avLibresMoy'] !== null ? \$n(\$data['avLibresMoy'], 0) . ' Mds' : 'N/D'],", "['Éncours Bons moy.', \$data['encoursBonsMoy'] !== null ? \$n(\$data['encoursBonsMoy'], 0) . ' Mds' : 'N/D'],", $c);
$c = str_replace("['Avoirs libres max.', \$data['avLibresMax'] !== null ? \$n(\$data['avLibresMax'], 0) . ' Mds' : 'N/D'],", "['Taux Interbancaire', \$data['tauxInterbancaireMoy'] !== null ? \$n(\$data['tauxInterbancaireMoy'], 2) . '%' : 'N/D'],", $c);
$c = str_replace("['Stérilisation moy.', \$data['sterilisationMoy'] !== null ? \$n(\$data['sterilisationMoy'], 0) . ' Mds' : 'N/D'],", "['TMP BBCC', \$data['tauxMoyenPondereMoy'] !== null ? \$n(\$data['tauxMoyenPondereMoy'], 2) . '%' : 'N/D'],\n                    ['Billets en circ.', \$data['billetsCirculationMoy'] !== null ? \$n(\$data['billetsCirculationMoy'], 0) . ' Mds' : 'N/D'],", $c);

// Le sous-titre
$c = str_replace("'sub' => 'Avoirs libres moyens (Mds CDF)',", "'sub' => 'Encours Bons BCC moy. (Mds CDF)',", $c);
// La valeur
$c = str_replace("'value' => \$data['avLibresMoy'] !== null ? \$n(\$data['avLibresMoy'], 0) . ' Mds' : 'N/D',", "'value' => \$data['encoursBonsMoy'] !== null ? \$n(\$data['encoursBonsMoy'], 0) . ' Mds' : 'N/D',", $c);

// Graphique grid (vers le bas)
$c = str_replace("['Liquidité / Stérilisation', \$data['signalLiquidite'] ?? 'secondary', \$data['avLibresMax'] !== null ? \$n(\$data['avLibresMax'], 0) . ' max' : 'N/D'],", "['Marché Monétaire', \$data['signalLiquidite'] ?? 'secondary', \$data['encoursBonsMoy'] !== null ? \$n(\$data['encoursBonsMoy'], 0) . ' moy' : 'N/D'],", $c);


file_put_contents($f, $c);
echo "Fix applied successfully.\n";
