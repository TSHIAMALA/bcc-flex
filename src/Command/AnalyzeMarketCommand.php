<?php

namespace App\Command;

use App\Service\MarketTensionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:market:analyze',
    description: 'Analyze market data and generate alerts/index',
)]
class AnalyzeMarketCommand extends Command
{
    public function __construct(
        private MarketTensionService $tensionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::OPTIONAL, 'Date to analyze (YYYY-MM-DD)', date('Y-m-d'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dateStr = $input->getArgument('date');
        $date = new \DateTime($dateStr);

        $io->title('Analyzing Market for ' . $date->format('Y-m-d'));

        $result = $this->tensionService->analyzeMarket($date);

        if (isset($result['error'])) {
            $io->error($result['error']);
            return Command::FAILURE;
        }

        $indice = $result['indice'];
        $alerts = $result['alerts'];

        $io->section('Market Tension Index');
        $io->text(sprintf('Score: <info>%s</info>', $indice->getScore()));
        $io->text(sprintf('Level: <info>%s</info>', $indice->getNiveau()));
        
        $io->section('Details');
        foreach ($indice->getDetails() as $name => $detail) {
            $io->text(sprintf(
                '%s: Val=%s, Score=%s (Weight: %s)', 
                $name, 
                $detail['valeur'] ?? 'N/A', 
                $detail['score'], 
                $detail['poids']
            ));
        }

        $io->section(sprintf('Alerts Generated (%d)', count($alerts)));
        foreach ($alerts as $alert) {
            $io->text(sprintf(
                '<error>[%s]</error> %s: %s (Threshold: %s)',
                $alert->getNiveau(),
                $alert->getIndicateur()->getNom(),
                $alert->getValeurConstatee(),
                $alert->getSeuilDeclenche()
            ));
        }

        $io->success('Analysis complete.');

        return Command::SUCCESS;
    }
}
