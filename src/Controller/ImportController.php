<?php

namespace App\Controller;

use App\Entity\ConjonctureJour;
use App\Repository\ConjonctureJourRepository;
use App\Service\AlerteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    #[Route('/import/upload', name: 'app_import_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        EntityManagerInterface $em,
        ConjonctureJourRepository $conjonctureRepository,
        AlerteService $alerteService
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
            $result = $this->processImportData($data, $conjoncture, $em);
            
            $em->persist($conjoncture);
            $em->flush();

            // Calculate alerts for the new data
            $alerteService->calculateAlerts($conjoncture);

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

    private function processImportData(array $data, ConjonctureJour $conjoncture, EntityManagerInterface $em): array
    {
        $rowCount = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            try {
                // Process based on data type/category in the row
                $type = $row['type'] ?? $row['categorie'] ?? '';
                
                switch (strtolower($type)) {
                    case 'marche_changes':
                    case 'change':
                        $this->importMarcheChanges($row, $conjoncture, $em);
                        break;
                    case 'reserves':
                    case 'reserves_financieres':
                        $this->importReserves($row, $conjoncture, $em);
                        break;
                    case 'finances':
                    case 'finances_publiques':
                        $this->importFinances($row, $conjoncture, $em);
                        break;
                    case 'tresorerie':
                    case 'tresorerie_etat':
                        $this->importTresorerie($row, $conjoncture, $em);
                        break;
                    default:
                        // Try to auto-detect based on columns present
                        $this->autoDetectAndImport($row, $conjoncture, $em);
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

    private function importMarcheChanges(array $row, ConjonctureJour $conjoncture, EntityManagerInterface $em): void
    {
        $marche = new \App\Entity\MarcheChanges();
        $marche->setConjoncture($conjoncture);
        
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

    private function importReserves(array $row, ConjonctureJour $conjoncture, EntityManagerInterface $em): void
    {
        $reserves = new \App\Entity\ReservesFinancieres();
        $reserves->setConjoncture($conjoncture);
        
        if (isset($row['reserves_internationales_usd'])) {
            $reserves->setReservesInternationalesUsd($this->parseNumber($row['reserves_internationales_usd']));
        }
        if (isset($row['avoirs_externes_usd'])) {
            $reserves->setAvoirsExternesUsd($this->parseNumber($row['avoirs_externes_usd']));
        }
        
        $em->persist($reserves);
    }

    private function importFinances(array $row, ConjonctureJour $conjoncture, EntityManagerInterface $em): void
    {
        $finances = new \App\Entity\FinancesPubliques();
        $finances->setConjoncture($conjoncture);
        
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
        }
        
        $em->persist($finances);
    }

    private function importTresorerie(array $row, ConjonctureJour $conjoncture, EntityManagerInterface $em): void
    {
        $tresorerie = new \App\Entity\TresorerieEtat();
        $tresorerie->setConjoncture($conjoncture);
        
        // Add specific fields based on TresorerieEtat entity
        
        $em->persist($tresorerie);
    }

    private function autoDetectAndImport(array $row, ConjonctureJour $conjoncture, EntityManagerInterface $em): void
    {
        // Auto-detect based on columns present
        if (isset($row['cours_indicatif']) || isset($row['parallele_vente'])) {
            $this->importMarcheChanges($row, $conjoncture, $em);
        } elseif (isset($row['reserves_internationales_usd']) || isset($row['avoirs_externes'])) {
            $this->importReserves($row, $conjoncture, $em);
        } elseif (isset($row['recettes_fiscales']) || isset($row['depenses_totales'])) {
            $this->importFinances($row, $conjoncture, $em);
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
