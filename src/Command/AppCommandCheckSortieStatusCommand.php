<?php

namespace App\Command;

use App\Repository\EtatRepository;
use App\Service\SortieStatusService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppCommandCheckSortieStatusCommand extends Command
{
    protected static $defaultName = 'app:check-sortie-status';

    private $sortieStatusChecker;
    private $logger;
    private $etatRepository;

    public function __construct(SortieStatusService $sortieStatusChecker, LoggerInterface $logger, EtatRepository $etatRepository)
    {
        parent::__construct();
        $this->sortieStatusChecker = $sortieStatusChecker;
        $this->logger = $logger;
        $this->etatRepository = $etatRepository;
    }

    protected function configure()
    {
        $this->setDescription('Vérifie les statuts des sorties.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Lancement de la vérification des statuts des sorties');
        $this->sortieStatusChecker->checkSortieStatus($this->logger, $this->etatRepository);
        $output->writeln('Vérification des statuts des sorties terminée.');

        return Command::SUCCESS;
    }
}
