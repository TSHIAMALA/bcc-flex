<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\ConjonctureJour;
use App\Entity\MarcheChanges;
use App\Entity\TransactionsUsd;
use App\Entity\Banques;
use App\Entity\ReservesFinancieres;
use App\Entity\EncoursBcc;
use App\Entity\FinancesPubliques;
use App\Entity\TresorerieEtat;
use App\Entity\TitresPublics;
use App\Entity\PaieEtat;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

function getOrCreateBanque($em, $nom) {
    if (!$nom) return null;
    $banque = $em->getRepository(Banques::class)->findOneBy(['nom' => $nom]);
    if (!$banque) {
        $banque = new Banques();
        $banque->setNom($nom);
        $em->persist($banque);
        $em->flush();
    }
    return $banque;
}

$data = [
    [
        'date_situation' => '2026-03-06',
        'date_applicable' => '2026-03-09',
        'marche_changes' => [
            'cours_indicatif' => 2162.1442,
            'parallele_achat' => 2285.63,
            'parallele_vente' => 2307.50,
            'ecart_indic_parallele' => 134.4208,
        ],
        'transactions' => [
            ['type' => 'VENTE', 'banque' => 'BOA', 'cours' => 2280.0000, 'volume' => 100000.00],
            ['type' => 'ACHAT', 'banque' => 'TMB', 'cours' => 2219.9789, 'volume' => 384279.00],
        ],
        'reserves' => [
            'int_usd' => 7148.28,
            'ext_usd' => 8245.47,
            'b_cdf' => 3236.80,
            'lib_cdf' => 1254.12,
        ],
        'encours' => [
            'ot' => 927.92,
            'b' => 1438.00,
        ],
        'finances' => [
            'recettes_tot' => 359.47,
            'recettes_fisc' => 234.46,
            'recettes_aut' => 125.01,
            'depenses_tot' => 497.51,
        ],
        'tresorerie' => [
            'avant' => -138.04,
            'apres' => 59.40,
            'cumul' => -94.21,
            'cgt' => 2834.42,
            'dep_urg' => -2795.38,
            'exc' => 6.47,
            'res_tit' => 20.38,
        ],
        'titres' => [
            'ot_idx' => 912.65,
            'bt_idx' => null,
            'ot_usd' => 1780.52,
            'bt_usd' => 245.75,
        ],
        'paie' => [
            'tot' => 1265.57,
            'paye' => 326.45,
            'reste' => 939.12,
        ]
    ],
    [
        'date_situation' => '2026-03-09',
        'date_applicable' => '2026-03-10',
        'marche_changes' => [
            'cours_indicatif' => 2182.8346,
            'parallele_achat' => 2285.00,
            'parallele_vente' => 2310.00,
            'ecart_indic_parallele' => 114.6654,
        ],
        'transactions' => [
            ['type' => 'VENTE', 'banque' => 'FBN BANK', 'cours' => 2290.0000, 'volume' => 262009.00],
            ['type' => 'ACHAT', 'banque' => 'BOA', 'cours' => 2210.0000, 'volume' => 339366.52],
        ],
        'reserves' => [
            'int_usd' => 7130.70,
            'ext_usd' => 8217.34,
            'b_cdf' => 3233.62,
            'lib_cdf' => 1250.95,
        ],
        'encours' => [
            'ot' => 927.92,
            'b' => 1438.00,
        ],
        'finances' => [
            'recettes_tot' => 490.43,
            'recettes_fisc' => 265.42,
            'recettes_aut' => 225.01,
            'depenses_tot' => 653.75,
        ],
        'tresorerie' => [
            'avant' => -163.32,
            'apres' => 48.21,
            'cumul' => -119.49,
            'cgt' => 2809.14,
            'dep_urg' => -2795.38,
            'exc' => 6.47,
            'res_tit' => 34.46,
        ],
        'titres' => [
            'ot_idx' => 912.65,
            'bt_idx' => null,
            'ot_usd' => 1780.52,
            'bt_usd' => 245.75,
        ],
        'paie' => [
            'tot' => 1265.57,
            'paye' => 324.36,
            'reste' => 941.21,
        ]
    ],
    [
        'date_situation' => '2026-03-10',
        'date_applicable' => '2026-03-11',
        'marche_changes' => [
            'cours_indicatif' => 2188.2836,
            'parallele_achat' => 2285.00,
            'parallele_vente' => 2310.00,
            'ecart_indic_parallele' => 109.2164,
        ],
        'transactions' => [
            ['type' => 'VENTE', 'banque' => 'FBN BANK', 'cours' => 2297.5881, 'volume' => 118378.49],
            ['type' => 'ACHAT', 'banque' => 'TMB', 'cours' => 2229.9978, 'volume' => 573345.00],
        ],
        'reserves' => [
            'int_usd' => 7050.88,
            'ext_usd' => 8144.19,
            'b_cdf' => 3206.34,
            'lib_cdf' => 1223.66,
        ],
        'encours' => [
            'ot' => 939.06,
            'b' => 1438.00,
        ],
        'finances' => [
            'recettes_tot' => 562.50,
            'recettes_fisc' => 357.49,
            'recettes_aut' => 205.01,
            'depenses_tot' => 712.81,
        ],
        'tresorerie' => [
            'avant' => -150.32,
            'apres' => 78.40,
            'cumul' => -106.49,
            'cgt' => 2850.46,
            'dep_urg' => -2823.69,
            'exc' => 6.47,
            'res_tit' => 51.68,
        ],
        'titres' => [
            'ot_idx' => 912.65,
            'bt_idx' => null,
            'ot_usd' => 1806.98,
            'bt_usd' => 245.75,
        ],
        'paie' => [
            'tot' => 1265.57,
            'paye' => 324.36,
            'reste' => 941.21,
        ]
    ]
];

foreach ($data as $item) {
    $conj = $em->getRepository(ConjonctureJour::class)->findOneBy(['date_situation' => new \DateTime($item['date_situation'])]);
    if (!$conj) {
        $conj = new ConjonctureJour();
        $conj->setDateSituation(new \DateTime($item['date_situation']));
        $em->persist($conj);
    }
    $conj->setDateApplicable(new \DateTime($item['date_applicable']));

    // MarcheChanges
    $mc = $em->getRepository(MarcheChanges::class)->findOneBy(['conjoncture' => $conj]);
    if (!$mc) {
        $mc = new MarcheChanges();
        $mc->setConjoncture($conj);
        $em->persist($mc);
    }
    $mc->setCoursIndicatif($item['marche_changes']['cours_indicatif']);
    $mc->setParalleleAchat($item['marche_changes']['parallele_achat']);
    $mc->setParalleleVente($item['marche_changes']['parallele_vente']);
    $mc->setEcartIndicParallele($item['marche_changes']['ecart_indic_parallele']);

    // Transactions Usd
    foreach ($item['transactions'] as $tx) {
        $banque = getOrCreateBanque($em, $tx['banque']);
        $txd = $em->getRepository(TransactionsUsd::class)->findOneBy(['conjoncture' => $conj, 'type_transaction' => $tx['type'], 'banque' => $banque]);
        if (!$txd) {
            $txd = new TransactionsUsd();
            $txd->setConjoncture($conj);
            $txd->setBanque($banque);
            $txd->setTypeTransaction($tx['type']);
            $em->persist($txd);
        }
        $txd->setCours($tx['cours']);
        $txd->setVolumeUsd($tx['volume']);
    }

    // ReservesFinancieres
    $rf = $em->getRepository(ReservesFinancieres::class)->findOneBy(['conjoncture' => $conj]);
    if (!$rf) {
        $rf = new ReservesFinancieres();
        $rf->setConjoncture($conj);
        $em->persist($rf);
    }
    $rf->setReservesInternationalesUsd($item['reserves']['int_usd']);
    $rf->setAvoirsExternesUsd($item['reserves']['ext_usd']);
    $rf->setReservesBanquesCdf($item['reserves']['b_cdf']);
    $rf->setAvoirsLibresCdf($item['reserves']['lib_cdf']);

    // EncoursBcc
    $eb = $em->getRepository(EncoursBcc::class)->findOneBy(['conjoncture' => $conj]);
    if (!$eb) {
        $eb = new EncoursBcc();
        $eb->setConjoncture($conj);
        $em->persist($eb);
    }
    $eb->setEncoursOtBcc($item['encours']['ot']);
    $eb->setEncoursBBcc($item['encours']['b']);

    // FinancesPubliques
    $fp = $em->getRepository(FinancesPubliques::class)->findOneBy(['conjoncture' => $conj]);
    if (!$fp) {
        $fp = new FinancesPubliques();
        $fp->setConjoncture($conj);
        $em->persist($fp);
    }
    $fp->setRecettesTotales($item['finances']['recettes_tot']);
    $fp->setRecettesFiscales($item['finances']['recettes_fisc']);
    $fp->setAutresRecettes($item['finances']['recettes_aut']);
    $fp->setDepensesTotales($item['finances']['depenses_tot']);

    // TresorerieEtat
    $te = $em->getRepository(TresorerieEtat::class)->findOneBy(['conjoncture' => $conj]);
    if (!$te) {
        $te = new TresorerieEtat();
        $te->setConjoncture($conj);
        $em->persist($te);
    }
    $te->setSoldeAvantFin($item['tresorerie']['avant']);
    $te->setSoldeApresFin($item['tresorerie']['apres']);
    $te->setSoldeCumuleAnnee($item['tresorerie']['cumul']);
    $te->setSoldeCgt($item['tresorerie']['cgt']);
    $te->setDepensesUrgence($item['tresorerie']['dep_urg']);
    $te->setExcedent($item['tresorerie']['exc']);
    $te->setReserveSousTitres($item['tresorerie']['res_tit']);

    // TitresPublics
    $tp = $em->getRepository(TitresPublics::class)->findOneBy(['conjoncture' => $conj]);
    if (!$tp) {
        $tp = new TitresPublics();
        $tp->setConjoncture($conj);
        $em->persist($tp);
    }
    $tp->setEncoursOtindex($item['titres']['ot_idx']);
    $tp->setEncoursBtindex($item['titres']['bt_idx']);
    $tp->setEncoursOtUsd($item['titres']['ot_usd']);
    $tp->setEncoursBtUsd($item['titres']['bt_usd']);

    // PaieEtat
    $pe = $em->getRepository(PaieEtat::class)->findOneBy(['conjoncture' => $conj]);
    if (!$pe) {
        $pe = new PaieEtat();
        $pe->setConjoncture($conj);
        $em->persist($pe);
    }
    $pe->setMontantTotal($item['paie']['tot']);
    $pe->setMontantPaye($item['paie']['paye']);
    $pe->setMontantRestant($item['paie']['reste']);

    $em->flush();
}

echo "Data imported successfully.\n";
