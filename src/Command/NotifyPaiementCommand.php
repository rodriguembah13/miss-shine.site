<?php

namespace App\Command;

use App\Entity\Vote;
use App\Repository\CandidatRepository;
use App\Repository\EditionRepository;
use App\Repository\VoteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NotifyPaiementCommand extends Command
{
    protected static $defaultName = 'app:notify-paiement';
    protected static $defaultDescription = 'Add a short description for your command';
    private $voteRepository;
    private $candidatRepository;
    private $doctrine;
    private $logger;
    private $editionRepository;

    /**
     * NotifyPaiementCommand constructor.
     * @param $voteRepository
     * @param $candidatRepository
     */
    public function __construct(ManagerRegistry $registry,LoggerInterface $logger,
                                VoteRepository $voteRepository, EditionRepository $editionRepository,
                                CandidatRepository $candidatRepository)
    {
        $this->voteRepository = $voteRepository;
        $this->candidatRepository = $candidatRepository;
        $this->doctrine=$registry;
        $this->logger=$logger;
        $this->editionRepository=$editionRepository;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $datebegin = $input->getArgument('datebegin');
        $dateend = $input->getArgument('dateend');
        $edition = $this->editionRepository->findOneBy(['status' => 'Publie']);
        $candidats = $this->candidatRepository->findByEdition($edition);
        foreach ($candidats as $candidat){
            $votes=$this->voteRepository->findByDay($datebegin,$dateend,$candidat);
            foreach ($votes as $vote){
                $status="Success";
                if (strtolower($status) === "success") {
                    $this->updateVote($vote, 'ACCEPTED');
                } elseif (strtolower($status) === "failed") {
                    $this->updateVote($vote, 'REFUSED');
                }
            }

        }
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
    protected function updateVote(Vote $vote, $status)
    {
        if ($status == "ACCEPTED") {
            $vote->setStatus($status);
            $candidat = $vote->getCandidat();
            $candidat->setVote($candidat->getVote() + $vote->getNombreVote());
            $this->generateRang();
        } else {
            $vote->setStatus($status);
        }
        $this->doctrine->getManager()->flush();
    }
    protected function generateRang()
    {
        $edition = $this->editionRepository->findOneBy(['status' => 'Publie']);
        $candidats = $this->candidatRepository->findByEdition($edition);
        foreach ($candidats as $candidat) {
            $j = 0;
            for ($i = 0; $i < sizeof($candidats); $i++) {
                if ($candidat->getVote() === $candidats[$i]->getVote()) {
                    $j = sizeof($candidats) - $i;
                }
            }
            $candidat->setPosition($j);
        }
        $this->doctrine->getManager()->flush();
    }
}
