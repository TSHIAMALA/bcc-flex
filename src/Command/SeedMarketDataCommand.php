<?php

namespace App\Command;

use App\Entity\Indicateur;
use App\Entity\RegleIntervention;
use App\Repository\IndicateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:market:seed',
    description: 'Seed valid indicators and rules',
)]
class SeedMarketDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private IndicateurRepository $indicateurRepo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding Market Data');

        $indicators = [
            'ECART_CHANGE' => [
                'nom' => 'Écart de change',
                'unite' => '%',
                'type' => 'PREMIER_RANG',
                'seuil_alerte' => '1.5',
                'seuil_intervention' => '2.5',
                'poids' => 30,
                'operateur' => '>'
            ],
            'AVOIRS_LIBRES' => [
                'nom' => 'Avoirs libres (CDF)',
                'unite' => 'Mds CDF',
                'type' => 'PREMIER_RANG',
                'seuil_alerte' => '100', // Example low threshold
                'seuil_intervention' => '50',
                'poids' => 20,
                'operateur' => '<'
            ],
            'RESERVES' => [
                'nom' => 'Réserves de change',
                'unite' => 'Mio USD',
                'type' => 'PREMIER_RANG',
                'seuil_alerte' => '1000',
                'seuil_intervention' => '800',
                'poids' => 20,
                'operateur' => '<'
            ]
        ];

        foreach ($indicators as $code => $data) {
            $indicateur = $this->indicateurRepo->findOneBy(['code' => $code]);
            
            if (!$indicateur) {
                $indicateur = new Indicateur();
                $indicateur->setCode($code);
                $indicateur->setNom($data['nom']);
                $indicateur->setUnite($data['unite']);
                $indicateur->setType($data['type']);
                $this->em->persist($indicateur);
                
                $regle = new RegleIntervention();
                $regle->setIndicateur($indicateur);
                $regle->setSeuilAlerte($data['seuil_alerte']);
                $regle->setSeuilIntervention($data['seuil_intervention']);
                $regle->setPoids($data['poids']);
                $regle->setOperateur($data['operateur']);
                $regle->setBaseComparaison('JOUR');
                $regle->setActif(true);
                $this->em->persist($regle);
                
                $io->text("Created $code");
            } else {
                $io->text("$code already exists");
            }
        }

        $this->em->flush();
        $io->success('Seeding complete.');

        return Command::SUCCESS;
    }
}
