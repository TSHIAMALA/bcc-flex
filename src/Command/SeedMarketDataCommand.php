<?php

namespace App\Command;

use App\Entity\Indicateur;
use App\Entity\RegleIntervention;
use App\Repository\IndicateurRepository;
use App\Repository\RegleInterventionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-market-data',
    description: 'Seed initial indicators and rules',
)]
class SeedMarketDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private IndicateurRepository $indicateurRepo,
        private RegleInterventionRepository $regleRepo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $indicatorsData = [
            [
                'code' => 'ECART_CHANGE',
                'nom' => 'Écart de Change',
                'unite' => '%',
                'type' => 'CHANGE',
                'rule' => ['s1' => 1.5, 's2' => 2.5, 'sens' => 'hausse', 'poids' => 30]
            ],
            [
                'code' => 'AVOIRS_LIBRES',
                'nom' => 'Avoirs Libres en CDF',
                'unite' => 'Mds CDF',
                'type' => 'TRESORERIE',
                'rule' => ['s1' => 100, 's2' => 50, 'sens' => 'baisse', 'poids' => 20]
            ],
            [
                'code' => 'RESERVES_INT',
                'nom' => 'Réserves de Change',
                'unite' => 'Mio USD',
                'type' => 'RESERVES',
                'rule' => ['s1' => 1000, 's2' => 800, 'sens' => 'baisse', 'poids' => 20]
            ],
            [
                'code' => 'SOLDE_BUDGET',
                'nom' => 'Solde Budgetaire',
                'unite' => 'Mds CDF',
                'type' => 'FINANCES',
                'rule' => ['s1' => -50, 's2' => -100, 'sens' => 'baisse', 'poids' => 15] // Placeholder thresholds
            ],
            [
                'code' => 'COURS_INDICATIF',
                'nom' => 'Cours Indicatif',
                'unite' => 'CDF',
                'type' => 'CHANGE',
                'rule' => ['s1' => 3000, 's2' => 3200, 'sens' => 'hausse', 'poids' => 15] // Placeholder
            ]
        ];

        foreach ($indicatorsData as $data) {
            $indicateur = $this->indicateurRepo->findOneBy(['code' => $data['code']]);
            
            if (!$indicateur) {
                $indicateur = new Indicateur();
                $indicateur->setCode($data['code']);
                $indicateur->setNom($data['nom']);
                $indicateur->setUnite($data['unite']);
                $indicateur->setType($data['type']);
                $this->em->persist($indicateur);
                $io->text("Created indicator: {$data['nom']}");
            }

            // Check rule
            $existingRule = null;
            if ($indicateur->getId()) {
                $existingRule = $this->regleRepo->findOneBy(['indicateur' => $indicateur]);
            }

            if (!$existingRule) {
                $rule = new RegleIntervention();
                $rule->setIndicateur($indicateur);
                $rule->setSeuilAlerte($data['rule']['s1']);
                $rule->setSeuilIntervention($data['rule']['s2']);
                $rule->setSens($data['rule']['sens']);
                $rule->setPoids($data['rule']['poids']);
                $rule->setActif(true);
                $this->em->persist($rule);
                $io->text("Created rule for: {$data['nom']}");
            }
        }

        $this->em->flush();

        $io->success('Market data seeded successfully.');

        return Command::SUCCESS;
    }
}
