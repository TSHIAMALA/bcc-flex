<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\ConjonctureJour;
use App\Entity\MarcheChanges;
use App\Entity\EncoursBcc;
use App\Entity\ReservesFinancieres;
use App\Entity\FinancesPubliques;
use App\Entity\TresorerieEtat;
use App\Entity\PaieEtat;
use App\Entity\TitresPublics;

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

// Date situation: 06/03/2026
$dateSituation = new DateTime('2026-03-06');
// Date applicable: 09/03/2026
$dateApplicable = new DateTime('2026-03-09');

// Check if ConjonctureJour already exists
$existing = $em->getRepository(ConjonctureJour::class)->findOneBy(['date_situation' => $dateSituation]);
if ($existing) {
    echo "ConjonctureJour already exists for this date.\n";
    exit;
}

$conjoncture = new ConjonctureJour();
$conjoncture->setDateSituation($dateSituation);
$conjoncture->setDateApplicable($dateApplicable);

$em->persist($conjoncture);

// MarcheChanges
$marche = new MarcheChanges();
$marche->setConjoncture($conjoncture);
$marche->setCoursIndicatif('2162.1442');
$marche->setParalleleAchat('2285.63');
$marche->setParalleleVente('2307.50');
$marche->setEcartIndicParallele('134.4208');
$em->persist($marche);

// EncoursBcc
$encours = new EncoursBcc();
$encours->setConjoncture($conjoncture);
$encours->setEncoursOtBcc('927.92');
$encours->setEncoursBBcc('1438.00');
$em->persist($encours);

// ReservesFinancieres
$reserves = new ReservesFinancieres();
$reserves->setConjoncture($conjoncture);
$reserves->setReservesInternationalesUsd('7148.28');
$reserves->setAvoirsExternesUsd('8245.47');
$reserves->setReservesBanquesCdf('3236.80');
$reserves->setAvoirsLibresCdf('1254.12');
$em->persist($reserves);

// FinancesPubliques
$finances = new FinancesPubliques();
$finances->setConjoncture($conjoncture);
$finances->setRecettesTotales('359.47');
$finances->setRecettesFiscales('234.46');
$finances->setAutresRecettes('125.01');
$finances->setDepensesTotales('497.51');
$em->persist($finances);

// TresorerieEtat
$tresorerie = new TresorerieEtat();
$tresorerie->setConjoncture($conjoncture);
$tresorerie->setSoldeAvantFin('-138.04');
$tresorerie->setSoldeApresFin('59.40');
$tresorerie->setSoldeCumuleAnnee('-94.21');
$tresorerie->setSoldeCgt('2834.42');
$tresorerie->setDepensesUrgence('-2795.38');
$tresorerie->setExcedent('6.47');
$tresorerie->setReserveSousTitres('20.38');
$em->persist($tresorerie);

// PaieEtat
$paie = new PaieEtat();
$paie->setConjoncture($conjoncture);
$paie->setMontantTotal('1265.57');
$paie->setMontantPaye('326.45');
$paie->setMontantRestant('939.12');
$em->persist($paie);

// TitresPublics
$titres = new TitresPublics();
$titres->setConjoncture($conjoncture);
$titres->setEncoursOtindex('912.65');
$titres->setEncoursOtUsd('1780.52');
$titres->setEncoursBtUsd('245.75');
$em->persist($titres);

$em->flush();

echo "Data added successfully.\n";