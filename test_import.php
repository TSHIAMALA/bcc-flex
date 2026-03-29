<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Service/TextImportParserService.php';

$text = <<<TEXT
4. Marché monétaire:

 •  Réserves des banques (en Mds)
         3 633,43
* Avoirs libres: 1 665,41
* Billet en circulation : 6 213,49


* Taux directeur:15%
* Taux moyen pondéré BBCC :
-7 jours   : 10,66 %
-28 jours : 13,53 %
-84 jours : 14,53 %

* Taux interbancaire: 4,5 %

* Encours bons BCC: 1 763,00
* Encours OT-BCC : 939,06
TEXT;

$parser = new \App\Service\TextImportParserService();
$data = $parser->parseText($text);

print_r($data['encours'] ?? []);
