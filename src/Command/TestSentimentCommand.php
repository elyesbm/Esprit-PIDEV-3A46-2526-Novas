<?php

namespace App\Command;

use App\Service\SentimentAnalysisService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-sentiment',
    description: 'Teste l\'analyse de sentiments (Hugging Face, modèle nlptown/bert-base-multilingual-uncased-sentiment).',
)]
class TestSentimentCommand extends Command
{
    public function __construct(
        private SentimentAnalysisService $sentimentService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('texte', InputArgument::OPTIONAL, 'Texte à analyser', 'J\'adore ce projet, tout est parfait !')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $text = $input->getArgument('texte');

        $io->title('Test analyse de sentiments (Hugging Face)');
        $io->writeln('Texte : "' . $text . '"');
        $io->newLine();

        try {
            $rawScores = $this->sentimentService->getRawScores($text);
            $io->section('Scores bruts (label / score)');
            if ($rawScores === []) {
                $io->writeln('(aucun score)');
                $io->warning(
                    'Aucun score reçu. Vérifiez :' . "\n" .
                    '  - HF_TOKEN dans .env.local (permission Inference Providers)' . "\n" .
                    '  - Optionnel : HF_SENTIMENT_SCRIPT=scripts/hf_sentiment.py et pip install huggingface_hub'
                );
            } else {
                $encoded = json_encode($rawScores, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
                $io->writeln(is_string($encoded) ? $encoded : 'Erreur de serialisation JSON.');
            }

            $stars = $this->sentimentService->analyze($text);
            $io->section('Résultat (note 1–5 étoiles)');
            $io->writeln($stars !== null
                ? sprintf('Note : %d / 5 %s', $stars, str_repeat('★', $stars) . str_repeat('☆', 5 - $stars))
                : 'Non disponible (token manquant ou erreur).'
            );

            if ($rawScores !== []) {
                $io->success('Test terminé.');
            }
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
