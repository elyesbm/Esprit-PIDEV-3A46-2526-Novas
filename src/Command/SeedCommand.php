<?php

namespace App\Command;

use App\Entity\CandidatureJob;
use App\Entity\Offrejob;
use App\Entity\User;
use App\Repository\OffrejobRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed',
    description: 'Seed the database with sample data (jobs + candidatures)'
)]
class SeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly OffrejobRepository $offres
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('append', null, InputOption::VALUE_NONE, 'Add data even if tables are not empty');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $append = (bool) $input->getOption('append');

        if (!$append && ($this->users->count([]) > 0 || $this->offres->count([]) > 0)) {
            $io->warning('Data already exists. Re-run with --append to add more.');
            return Command::SUCCESS;
        }

        $io->title('Seeding database');

        $now = new \DateTime();

        $userData = [
            ['NOM' => 'Ben', 'PRENOM' => 'Alice', 'EMAIL' => 'alice@example.com'],
            ['NOM' => 'Karim', 'PRENOM' => 'Youssef', 'EMAIL' => 'youssef@example.com'],
            ['NOM' => 'Haddad', 'PRENOM' => 'Sara', 'EMAIL' => 'sara@example.com'],
            ['NOM' => 'Triki', 'PRENOM' => 'Fares', 'EMAIL' => 'fares@example.com'],
        ];

        $createdUsers = [];
        foreach ($userData as $u) {
            $user = new User();
            $user->setNOM($u['NOM']);
            $user->setPRENOM($u['PRENOM']);
            $user->setEMAIL($u['EMAIL']);
            $user->setIMAGE('default.png');
            $user->setNUMERO(11111111 + random_int(0, 8888888));
            $user->setROLE('ROLE_USER');
            $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $this->em->persist($user);
            $createdUsers[] = $user;
        }

        $categories = ['TUTORAT', 'AIDE', 'CREATION'];
        $lieux = ['EN_LIGNE', 'PRESENTIEL'];
        $statuts = ['OUVERTE', 'FERMEE'];

        $titles = [
            'Cours de maths niveau L1',
            'Aide pour programmation PHP',
            'Preparation examen reseaux',
            'Atelier creation site web',
            'Soutien en anglais',
            'Projet creation application',
        ];

        $descs = [
            'Besoin d un etudiant pour expliquer les chapitres de base et faire des exercices.',
            'Mission courte pour revoir les concepts Symfony et corriger un projet.',
            'Session intensive sur les reseaux et protocoles, avec exercices pratiques.',
            'Aide a creer un site vitrine avec HTML CSS et un peu de JS.',
            'Correction et conversation pour ameliorer le niveau general.',
            'Accompagnement sur un projet de fin de semestre, planning flexible.',
        ];

        $createdOffres = [];
        $creator = $createdUsers[0];
        for ($i = 0; $i < 6; $i++) {
            $offre = new Offrejob();
            $offre->setTitreOffre($titles[$i]);
            $offre->setDescriptionOffre($descs[$i]);
            $offre->setCategorieOffre($categories[$i % count($categories)]);
            $offre->setLieu($lieux[$i % count($lieux)]);
            $offre->setStatutOffre($statuts[$i % count($statuts)]);
            $offre->setDateCreationOffre((clone $now)->modify('-' . (6 - $i) . ' days'));
            $offre->setCreateur($creator);
            $this->em->persist($offre);
            $createdOffres[] = $offre;
        }

        $candStatuses = ['EN_ATTENTE', 'ACCEPTEE', 'REFUSEE'];
        $messages = [
            'Bonjour, je suis tres motive et disponible cette semaine.',
            'Je peux commencer rapidement et j ai deja aide sur ce type de sujet.',
            'Merci pour l offre, voici mon profil et mon experience.',
        ];

        $candidates = array_slice($createdUsers, 1);
        foreach ($createdOffres as $index => $offre) {
            $candCount = ($index % 3) + 1;
            for ($j = 0; $j < $candCount; $j++) {
                $cand = new CandidatureJob();
                $cand->setOffre($offre);
                $cand->setCandidat($candidates[$j % count($candidates)]);
                $cand->setMessageCandidature($messages[$j % count($messages)]);
                $cand->setStatutCandidature($candStatuses[$j % count($candStatuses)]);
                $cand->setDateCandidature((clone $now)->modify('-' . (3 - $j) . ' days'));
                $this->em->persist($cand);
            }
        }

        $this->em->flush();

        $io->success('Seed complete: users, offres, candidatures created.');
        return Command::SUCCESS;
    }
}

