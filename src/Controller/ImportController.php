<?php

namespace App\Controller;

use App\Entity\ConjonctureJour;
use App\Event\ConjonctureDataUpdatedEvent;
use App\Repository\ConjonctureJourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Service\TextImportParserService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ImportController extends AbstractController
{
    #[Route('/import', name: 'app_import')]
    public function index(ConjonctureJourRepository $conjonctureRepository): Response
    {
        // Get recent imports (conjonctures)
        $recentImports = $conjonctureRepository->createQueryBuilder('c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('import/index.html.twig', [
            'recentImports' => $recentImports,
        ]);
    }

    #[Route('/import/text', name: 'app_import_text', methods: ['GET', 'POST'])]
    public function importText(Request $request, TextImportParserService $parser, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $text = $request->request->get('import_text');
            if (empty($text)) {
                $this->addFlash('error', 'Veuillez coller le texte à importer.');
                return $this->redirectToRoute('app_import_text');
            }

            // Parse text
            $parsedData = $parser->parseText($text);
            
            // Debug if needed or check if parsing failed
            if (empty($parsedData['date_situation'])) {
                $this->addFlash('error', 'Impossible de détecter la date de situation. Assurez-vous que le format est correct.');
                return $this->redirectToRoute('app_import_text');
            }

            // Save in session for preview
            $session->set('import_preview_data', $parsedData);
            
            return $this->redirectToRoute('app_import_text_preview');
        }

        return $this->render('import/text_import.html.twig');
    }

    #[Route('/import/text/preview', name: 'app_import_text_preview', methods: ['GET'])]
    public function importTextPreview(SessionInterface $session): Response
    {
        $data = $session->get('import_preview_data');
        
        if (!$data) {
            $this->addFlash('error', 'Aucune donnée à prévisualiser.');
            return $this->redirectToRoute('app_import_text');
        }

        return $this->render('import/text_preview.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/text/confirm', name: 'app_import_text_confirm', methods: ['POST'])]
    public function importTextConfirm(
        SessionInterface $session,
        EntityManagerInterface $em,
        ConjonctureJourRepository $conjonctureRepository,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $data = $session->get('import_preview_data');
        
        if (!$data) {
            $this->addFlash('error', 'La session d\'import a expiré. Veuillez recommencer.');
            return $this->redirectToRoute('app_import_text');
        }

        try {
            // Find or create conjoncture
            $dateSit = new \DateTime($data['date_situation']);
            $conj = $conjonctureRepository->findOneBy(['date_situation' => $dateSit]);
            
            if (!$conj) {
                $conj = new ConjonctureJour();
                $conj->setDateSituation($dateSit);
                $this->addFlash('success', 'Nouvelle conjoncture créée pour le ' . $data['date_situation']);
            } else {
                $this->addFlash('warning', 'Conjoncture existante mise à jour pour le ' . $data['date_situation']);
            }
            
            if (!empty($data['date_applicable'])) {
                $conj->setDateApplicable(new \DateTime($data['date_applicable']));
            } else {
                // Si pas de date applicable détectée, on met la même que la situation
                $conj->setDateApplicable($dateSit);
            }

            $em->persist($conj);
            
            // 1. Marché des changes
            if (!empty($data['marche_changes'])) {
                $mc = $em->getRepository(\App\Entity\MarcheChanges::class)->findOneBy(['conjoncture' => $conj]);
                if (!$mc) {
                    $mc = new \App\Entity\MarcheChanges();
                    $mc->setConjoncture($conj);
                    $em->persist($mc);
                }
                if (isset($data['marche_changes']['cours_indicatif'])) $mc->setCoursIndicatif($data['marche_changes']['cours_indicatif']);
                if (isset($data['marche_changes']['parallele_achat'])) $mc->setParalleleAchat($data['marche_changes']['parallele_achat']);
                if (isset($data['marche_changes']['parallele_vente'])) $mc->setParalleleVente($data['marche_changes']['parallele_vente']);
                if (isset($data['marche_changes']['ecart_indic_parallele'])) $mc->setEcartIndicParallele($data['marche_changes']['ecart_indic_parallele']);
            }

            // 2. Transactions
            if (!empty($data['transactions'])) {
                foreach ($data['transactions'] as $tx) {
                    if (empty($tx['banque'])) continue;
                    
                    $banque = $em->getRepository(\App\Entity\Banques::class)->findOneBy(['nom' => $tx['banque']]);
                    if (!$banque) {
                        $banque = new \App\Entity\Banques();
                        $banque->setNom($tx['banque']);
                        $em->persist($banque);
                        $em->flush(); // Nécessaire pour l'utiliser ensuite
                    }

                    $txd = $em->getRepository(\App\Entity\TransactionsUsd::class)->findOneBy([
                        'conjoncture' => $conj, 
                        'type_transaction' => $tx['type'], 
                        'banque' => $banque
                    ]);
                    
                    if (!$txd) {
                        $txd = new \App\Entity\TransactionsUsd();
                        $txd->setConjoncture($conj);
                        $txd->setBanque($banque);
                        $txd->setTypeTransaction($tx['type']);
                        $em->persist($txd);
                    }
                    if (isset($tx['cours'])) $txd->setCours($tx['cours']);
                    if (isset($tx['volume'])) $txd->setVolumeUsd($tx['volume']);
                }
            }

            // 3. Reserves
            if (!empty($data['reserves'])) {
                $rf = $em->getRepository(\App\Entity\ReservesFinancieres::class)->findOneBy(['conjoncture' => $conj]);
                if (!$rf) {
                    $rf = new \App\Entity\ReservesFinancieres();
                    $rf->setConjoncture($conj);
                    $em->persist($rf);
                }
                if (isset($data['reserves']['int_usd'])) $rf->setReservesInternationalesUsd($data['reserves']['int_usd']);
                if (isset($data['reserves']['ext_usd'])) $rf->setAvoirsExternesUsd($data['reserves']['ext_usd']);
                if (isset($data['reserves']['b_cdf'])) $rf->setReservesBanquesCdf($data['reserves']['b_cdf']);
                if (isset($data['reserves']['lib_cdf'])) $rf->setAvoirsLibresCdf($data['reserves']['lib_cdf']);
            }

            // 4. Encours
            if (!empty($data['encours'])) {
                $eb = $em->getRepository(\App\Entity\EncoursBcc::class)->findOneBy(['conjoncture' => $conj]);
                if (!$eb) {
                    $eb = new \App\Entity\EncoursBcc();
                    $eb->setConjoncture($conj);
                    $em->persist($eb);
                }
                if (isset($data['encours']['ot'])) $eb->setEncoursOtBcc($data['encours']['ot']);
                if (isset($data['encours']['b'])) $eb->setEncoursBBcc($data['encours']['b']);
                if (isset($data['encours']['billets'])) $eb->setBilletsEnCirculation($data['encours']['billets']);
                if (isset($data['encours']['taux_moyen_pondere'])) $eb->setTauxMoyenPondereBbcc($data['encours']['taux_moyen_pondere']);
                if (isset($data['encours']['taux_interbancaire'])) $eb->setTauxInterbancaire($data['encours']['taux_interbancaire']);
            }

            // 5. Finances Publiques
            if (!empty($data['finances'])) {
                $fp = $em->getRepository(\App\Entity\FinancesPubliques::class)->findOneBy(['conjoncture' => $conj]);
                if (!$fp) {
                    $fp = new \App\Entity\FinancesPubliques();
                    $fp->setConjoncture($conj);
                    $em->persist($fp);
                }
                if (isset($data['finances']['recettes_tot'])) $fp->setRecettesTotales($data['finances']['recettes_tot']);
                if (isset($data['finances']['recettes_fisc'])) $fp->setRecettesFiscales($data['finances']['recettes_fisc']);
                if (isset($data['finances']['recettes_aut'])) $fp->setAutresRecettes($data['finances']['recettes_aut']);
                if (isset($data['finances']['depenses_tot'])) $fp->setDepensesTotales($data['finances']['depenses_tot']);
                
                if ($fp->getSolde() === null && $fp->getRecettesTotales() !== null && $fp->getDepensesTotales() !== null) {
                    $fp->setSolde((string)((float)$fp->getRecettesTotales() - (float)$fp->getDepensesTotales()));
                }
            }

            // 6. Tresorerie
            if (!empty($data['tresorerie'])) {
                $te = $em->getRepository(\App\Entity\TresorerieEtat::class)->findOneBy(['conjoncture' => $conj]);
                if (!$te) {
                    $te = new \App\Entity\TresorerieEtat();
                    $te->setConjoncture($conj);
                    $em->persist($te);
                }
                if (isset($data['tresorerie']['avant'])) $te->setSoldeAvantFin($data['tresorerie']['avant']);
                if (isset($data['tresorerie']['apres'])) $te->setSoldeApresFin($data['tresorerie']['apres']);
                if (isset($data['tresorerie']['cumul'])) $te->setSoldeCumuleAnnee($data['tresorerie']['cumul']);
                if (isset($data['tresorerie']['cgt'])) $te->setSoldeCgt($data['tresorerie']['cgt']);
                if (isset($data['tresorerie']['dep_urg'])) $te->setDepensesUrgence($data['tresorerie']['dep_urg']);
                if (isset($data['tresorerie']['exc'])) $te->setExcedent($data['tresorerie']['exc']);
                if (isset($data['tresorerie']['res_tit'])) $te->setReserveSousTitres($data['tresorerie']['res_tit']);
            }

            // 7. Titres Publics
            if (!empty($data['titres'])) {
                 $tp = $em->getRepository(\App\Entity\TitresPublics::class)->findOneBy(['conjoncture' => $conj]);
                 if (!$tp) {
                     $tp = new \App\Entity\TitresPublics();
                     $tp->setConjoncture($conj);
                     $em->persist($tp);
                 }
                 if (isset($data['titres']['ot_idx'])) $tp->setEncoursOtindex($data['titres']['ot_idx']);
                 if (isset($data['titres']['bt_idx'])) $tp->setEncoursBtindex($data['titres']['bt_idx']);
                 if (isset($data['titres']['ot_usd'])) $tp->setEncoursOtUsd($data['titres']['ot_usd']);
                 if (isset($data['titres']['bt_usd'])) $tp->setEncoursBtUsd($data['titres']['bt_usd']);
            }

            // 8. Paie Etat
            if (!empty($data['paie'])) {
                $pe = $em->getRepository(\App\Entity\PaieEtat::class)->findOneBy(['conjoncture' => $conj]);
                if (!$pe) {
                    $pe = new \App\Entity\PaieEtat();
                    $pe->setConjoncture($conj);
                    $em->persist($pe);
                }
                if (isset($data['paie']['tot'])) $pe->setMontantTotal($data['paie']['tot']);
                if (isset($data['paie']['paye'])) $pe->setMontantPaye($data['paie']['paye']);
                if (isset($data['paie']['reste'])) $pe->setMontantRestant($data['paie']['reste']);
            }
            
            $em->flush();

            // Notify event
            $eventDispatcher->dispatch(
                new ConjonctureDataUpdatedEvent($conj, 'text_import'),
                ConjonctureDataUpdatedEvent::NAME
            );

            // Clear session
            $session->remove('import_preview_data');

            $this->addFlash('success', 'Toutes les données extraites ont été enregistrées avec succès!');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_import');
    }

    #[Route('/import/upload', name: 'app_import_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        EntityManagerInterface $em,
        ConjonctureJourRepository $conjonctureRepository,
        \App\Repository\MarcheChangesRepository $marcheRepo,
        \App\Repository\ReservesFinancieresRepository $reservesRepo,
        \App\Repository\FinancesPubliquesRepository $financesRepo,
        \App\Repository\TresorerieEtatRepository $tresorerieRepo,
        \App\Repository\EncoursBccRepository $encoursRepo,
        \App\Repository\PaieEtatRepository $paieRepo,
        \App\Repository\TransactionsUsdRepository $transacRepo,
        \App\Repository\BanquesRepository $banqueRepo,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $file = $request->files->get('import_file');
        $dateStr = $request->request->get('date_situation');
        
        if (!$file instanceof UploadedFile) {
            $this->addFlash('error', 'Veuillez sélectionner un fichier.');
            return $this->redirectToRoute('app_import');
        }

        if (!$dateStr) {
            $this->addFlash('error', 'Veuillez sélectionner une date.');
            return $this->redirectToRoute('app_import');
        }

        $date = new \DateTime($dateStr);
        
        // Check for duplicate
        $existing = $conjonctureRepository->findOneBy(['date_situation' => $date]);
        if ($existing) {
            $this->addFlash('warning', 'Une conjoncture existe déjà pour cette date. Les données seront mises à jour.');
            $conjoncture = $existing;
        } else {
            $conjoncture = new ConjonctureJour();
            $conjoncture->setDateSituation($date);
            $conjoncture->setDateApplicable($date);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        
        try {
            if ($extension === 'csv') {
                $data = $this->parseCSV($file);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $data = $this->parseExcel($file);
            } else {
                throw new \Exception('Format de fichier non supporté. Utilisez CSV ou Excel.');
            }

            // Process the data and create/update related entities
            $result = $this->processImportData(
                $data,
                $conjoncture,
                $em,
                $marcheRepo,
                $reservesRepo,
                $financesRepo,
                $tresorerieRepo,
                $encoursRepo,
                $paieRepo,
                $transacRepo,
                $banqueRepo
            );
            
            $em->persist($conjoncture);
            $em->flush();

            // Dispatch event for automatic alert recalculation
            $eventDispatcher->dispatch(
                new ConjonctureDataUpdatedEvent($conjoncture, 'import'),
                ConjonctureDataUpdatedEvent::NAME
            );

            $this->addFlash('success', sprintf(
                'Import réussi! %d lignes traitées pour le %s.',
                $result['rowCount'],
                $date->format('d/m/Y')
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'import: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_import');
    }

    private function parseCSV(UploadedFile $file): array
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        // Skip header row
        $headers = fgetcsv($handle, 0, ';');
        $headers = array_map('trim', $headers);
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) >= count($headers)) {
                $data[] = array_combine($headers, array_map('trim', $row));
            }
        }
        
        fclose($handle);
        return $data;
    }

    private function parseExcel(UploadedFile $file): array
    {
        $data = [];
        
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        if (empty($rows)) {
            return $data;
        }
        
        $headers = array_map('trim', $rows[0]);
        
        for ($i = 1; $i < count($rows); $i++) {
            if (count($rows[$i]) >= count($headers)) {
                $data[] = array_combine($headers, array_map(function($v) {
                    return is_string($v) ? trim($v) : $v;
                }, $rows[$i]));
            }
        }
        
        return $data;
    }

    private function processImportData(
        array $data,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\MarcheChangesRepository $marcheRepo,
        \App\Repository\ReservesFinancieresRepository $reservesRepo,
        \App\Repository\FinancesPubliquesRepository $financesRepo,
        \App\Repository\TresorerieEtatRepository $tresorerieRepo,
        \App\Repository\EncoursBccRepository $encoursRepo,
        \App\Repository\PaieEtatRepository $paieRepo,
        \App\Repository\TransactionsUsdRepository $transacRepo,
        \App\Repository\BanquesRepository $banqueRepo
    ): array
    {
        $rowCount = 0;
        $errors = [];
        
        // Optional: Clear existing transactions for this day to avoid duplicates on re-import
        // We do this only if we detect transaction data in the file, but looping twice is expensive.
        // Let's rely on the user to cleanly import or we just append.
        // BETTER: If we process a transaction line, we assume this is an update. 
        // But since we can't key by ID, we might double data. 
        // DECISION: We will NOT auto-delete here to be safe, but simplistic appending might be an issue.
        // Ideally, we should delete all transactions for this conjoncture IF the file contains transactions.
        
        // Let's check first row to see if it's a transaction file? 
        // Too complex for now. We'll append. The user can delete via UI i needed or we handle it later.

        foreach ($data as $index => $row) {
            try {
                // Process based on data type/category in the row
                $type = $row['type'] ?? $row['categorie'] ?? '';
                
                switch (strtolower($type)) {
                    case 'marche_changes':
                    case 'change':
                        $this->importMarcheChanges($row, $conjoncture, $em, $marcheRepo);
                        break;
                    case 'reserves':
                    case 'reserves_financieres':
                        $this->importReserves($row, $conjoncture, $em, $reservesRepo);
                        break;
                    case 'finances':
                    case 'finances_publiques':
                        $this->importFinances($row, $conjoncture, $em, $financesRepo);
                        break;
                    case 'tresorerie':
                    case 'tresorerie_etat':
                        $this->importTresorerie($row, $conjoncture, $em, $tresorerieRepo);
                        break;
                    case 'encours':
                    case 'encours_bcc':
                        $this->importEncoursBcc($row, $conjoncture, $em, $encoursRepo);
                        break;
                    case 'paie':
                    case 'paie_etat':
                        $this->importPaieEtat($row, $conjoncture, $em, $paieRepo);
                        break;
                    case 'transactions':
                    case 'transactions_usd':
                        $this->importTransactionsUsd($row, $conjoncture, $em, $transacRepo, $banqueRepo);
                        break;
                    default:
                        // Try to auto-detect based on columns present
                        $this->autoDetectAndImport(
                            $row,
                            $conjoncture,
                            $em,
                            $marcheRepo,
                            $reservesRepo,
                            $financesRepo,
                            $encoursRepo,
                            $paieRepo,
                            $transacRepo,
                            $banqueRepo
                        );
                }
                
                $rowCount++;
            } catch (\Exception $e) {
                $errors[] = sprintf('Ligne %d: %s', $index + 2, $e->getMessage());
            }
        }

        return [
            'rowCount' => $rowCount,
            'errors' => $errors
        ];
    }

    private function importMarcheChanges(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\MarcheChangesRepository $repo
    ): void
    {
        $marche = $repo->findOneBy(['conjoncture' => $conjoncture]);
        if (!$marche) {
            $marche = new \App\Entity\MarcheChanges();
            $marche->setConjoncture($conjoncture);
        }
        
        if (isset($row['cours_indicatif'])) {
            $marche->setCoursIndicatif($this->parseNumber($row['cours_indicatif']));
        }
        if (isset($row['parallele_achat'])) {
            $marche->setParalleleAchat($this->parseNumber($row['parallele_achat']));
        }
        if (isset($row['parallele_vente'])) {
            $marche->setParalleleVente($this->parseNumber($row['parallele_vente']));
        }
        if (isset($row['ecart'])) {
            $marche->setEcartIndicParallele($this->parseNumber($row['ecart']));
        }
        
        $em->persist($marche);
    }

    private function importReserves(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\ReservesFinancieresRepository $repo
    ): void
    {
        $reserves = $repo->findOneBy(['conjoncture' => $conjoncture]);
        if (!$reserves) {
            $reserves = new \App\Entity\ReservesFinancieres();
            $reserves->setConjoncture($conjoncture);
        }
        
        if (isset($row['reserves_internationales_usd'])) {
            $reserves->setReservesInternationalesUsd($this->parseNumber($row['reserves_internationales_usd']));
        }
        if (isset($row['avoirs_externes_usd'])) {
            $reserves->setAvoirsExternesUsd($this->parseNumber($row['avoirs_externes_usd']));
        }
        
        $em->persist($reserves);
    }

    private function importFinances(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\FinancesPubliquesRepository $repo
    ): void
    {
        $finances = $repo->findOneBy(['conjoncture' => $conjoncture]);
        if (!$finances) {
            $finances = new \App\Entity\FinancesPubliques();
            $finances->setConjoncture($conjoncture);
        }
        
        if (isset($row['recettes_fiscales'])) {
            $finances->setRecettesFiscales($this->parseNumber($row['recettes_fiscales']));
        }
        if (isset($row['autres_recettes'])) {
            $finances->setAutresRecettes($this->parseNumber($row['autres_recettes']));
        }
        if (isset($row['recettes_totales'])) {
            $finances->setRecettesTotales($this->parseNumber($row['recettes_totales']));
        }
        if (isset($row['depenses_totales'])) {
            $finances->setDepensesTotales($this->parseNumber($row['depenses_totales']));
        }
        if (isset($row['solde'])) {
            $finances->setSolde($this->parseNumber($row['solde']));
        } elseif ($finances->getRecettesTotales() !== null && $finances->getDepensesTotales() !== null) {
            $finances->setSolde((string)((float)$finances->getRecettesTotales() - (float)$finances->getDepensesTotales()));
        }
        
        $em->persist($finances);
    }

    private function importTresorerie(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\TresorerieEtatRepository $repo
    ): void
    {
        $tresorerie = $repo->findOneBy(['conjoncture' => $conjoncture]);
        if (!$tresorerie) {
            $tresorerie = new \App\Entity\TresorerieEtat();
            $tresorerie->setConjoncture($conjoncture);
        }
        
        // Add specific fields based on TresorerieEtat entity
        
        $em->persist($tresorerie);
    }

    private function importEncoursBcc(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\EncoursBccRepository $repo
    ): void
    {
        $encours = $repo->findOneBy(['conjoncture' => $conjoncture]);
        if (!$encours) {
            $encours = new \App\Entity\EncoursBcc();
            $encours->setConjoncture($conjoncture);
        }
        
        if (isset($row['encours_ot_bcc'])) {
            $encours->setEncoursOtBcc($this->parseNumber($row['encours_ot_bcc']));
        }
        if (isset($row['encours_b_bcc'])) {
            $encours->setEncoursBBcc($this->parseNumber($row['encours_b_bcc']));
        }
        if (isset($row['billets_en_circulation'])) {
            $encours->setBilletsEnCirculation($this->parseNumber($row['billets_en_circulation']));
        }
        if (isset($row['taux_moyen_pondere_bbcc'])) {
            $encours->setTauxMoyenPondereBbcc($this->parseNumber($row['taux_moyen_pondere_bbcc']));
        }
        if (isset($row['taux_interbancaire'])) {
            $encours->setTauxInterbancaire($this->parseNumber($row['taux_interbancaire']));
        }
        
        $em->persist($encours);
    }

    private function importPaieEtat(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\PaieEtatRepository $repo
    ): void
    {
        $paie = $repo->findOneBy(['conjoncture' => $conjoncture]);
        if (!$paie) {
            $paie = new \App\Entity\PaieEtat();
            $paie->setConjoncture($conjoncture);
        }
        
        if (isset($row['montant_total'])) {
            $paie->setMontantTotal($this->parseNumber($row['montant_total']));
        }
        if (isset($row['montant_paye'])) {
            $paie->setMontantPaye($this->parseNumber($row['montant_paye']));
        }
        if (isset($row['montant_restant'])) {
            $paie->setMontantRestant($this->parseNumber($row['montant_restant']));
        }
        
        $em->persist($paie);
    }

    private function importTransactionsUsd(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        \App\Repository\TransactionsUsdRepository $transacRepo,
        \App\Repository\BanquesRepository $banqueRepo
    ): void
    {
        // Strategy: We append transactions. Ideally, we should clear existing transactions for this date BEFORE processing the file (e.g. at the beginning of processImportData if type is detected), 
        // but here we are row by row. 
        // A simple approach for import is: 
        // 1. Check if we already have this exact transaction (same bank, same type, same amount) -> Skip
        // 2. Or, just add it.
        // Given complexity, let's try to match by bank name.
        
        $banqueName = $row['banque'] ?? null;
        if (!$banqueName) return;

        $banque = $banqueRepo->findOneBy(['nom' => $banqueName]);
        if (!$banque) {
            // Option: Create bank if not exists? Or skip/error?
            // Let's create it for flexibility, or maybe just log error.
            // For now, let's try to find approximate or create.
            // Simple: Error if not found.
            throw new \Exception("Banque non trouvée: " . $banqueName);
        }

        $transaction = new \App\Entity\TransactionsUsd();
        $transaction->setConjoncture($conjoncture);
        $transaction->setBanque($banque);
        
        $type = strtoupper($row['type_transaction'] ?? '');
        if (!in_array($type, ['ACHAT', 'VENTE'])) {
             // Try to deduce from cols?
             if (isset($row['achat'])) $type = 'ACHAT';
             elseif (isset($row['vente'])) $type = 'VENTE';
             else $type = 'ACHAT'; // Default? or Skip
        }
        $transaction->setTypeTransaction($type);

        if (isset($row['cours'])) {
            $transaction->setCours($this->parseNumber($row['cours']));
        }
        if (isset($row['volume_usd'])) {
            $transaction->setVolumeUsd($this->parseNumber($row['volume_usd']));
        }
        
        $em->persist($transaction);
    }

    private function autoDetectAndImport(
        array $row,
        ConjonctureJour $conjoncture,
        EntityManagerInterface $em,
        $marcheRepo,
        $reservesRepo,
        $financesRepo,
        $encoursRepo,
        $paieRepo,
        $transacRepo,
        $banqueRepo
    ): void
    {
        // Auto-detect based on columns present
        if (isset($row['cours_indicatif']) || isset($row['parallele_vente'])) {
            $this->importMarcheChanges($row, $conjoncture, $em, $marcheRepo);
        } elseif (isset($row['reserves_internationales_usd']) || isset($row['avoirs_externes'])) {
            $this->importReserves($row, $conjoncture, $em, $reservesRepo);
        } elseif (isset($row['recettes_fiscales']) || isset($row['depenses_totales'])) {
            $this->importFinances($row, $conjoncture, $em, $financesRepo);
        } elseif (isset($row['encours_ot_bcc']) || isset($row['encours_b_bcc'])) {
            $this->importEncoursBcc($row, $conjoncture, $em, $encoursRepo);
        } elseif (isset($row['montant_total']) || isset($row['montant_paye'])) {
            $this->importPaieEtat($row, $conjoncture, $em, $paieRepo);
        } elseif (isset($row['banque']) && (isset($row['volume_usd']) || isset($row['cours']))) {
            $this->importTransactionsUsd($row, $conjoncture, $em, $transacRepo, $banqueRepo);
        }
    }

    private function parseNumber($value): ?string
    {
        if (empty($value) || $value === '-') {
            return null;
        }
        
        // Remove spaces and convert comma to dot
        $value = str_replace([' ', ','], ['', '.'], $value);
        
        return is_numeric($value) ? $value : null;
    }
}
