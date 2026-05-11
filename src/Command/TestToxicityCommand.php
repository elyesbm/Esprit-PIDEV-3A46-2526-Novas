<?php

namespace App\Command;

use App\Service\ToxicityAnalysisService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-toxicity',
    description: 'Teste l\'analyse de toxicité (API Hugging Face, modèle unitary/toxic-bert).',
)]
class TestToxicityCommand extends Command
{
    public function __construct(
        private ToxicityAnalysisService $toxicityService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('texte', InputArgument::OPTIONAL, 'Texte à analyser', 'Tu es nul et inutile, crève.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $text = $input->getArgument('texte');

        $io->title('Test analyse de toxicité (Hugging Face)');
        $io->writeln('Texte : "' . $text . '"');
        $io->writeln('URL configurée : ' . $this->toxicityService->getApiUrl());
        $io->newLine();

        try {
            $scores = $this->toxicityService->getToxicityScores($text);
            $io->section('Scores');
            $encoded = json_encode($scores, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
            $io->writeln(is_string($encoded) ? $encoded : 'Erreur de serialisation JSON.');

            $isToxic = $this->toxicityService->isToxic($text);
            $io->section('Résultat');
            $io->writeln('isToxic() : ' . ($isToxic ? 'OUI (publication bloquée)' : 'NON (publication autorisée)'));

            if (empty($scores) && trim($text) !== '') {
                $io->warning('Aucun score reçu. Vérifiez HF_TOKEN et HF_INFERENCE_URL dans .env.local.');
            } else {
                $io->success('Test terminé.');
            }
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
