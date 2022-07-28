<?php

namespace App\Controller;

use App\Repository\CandidatRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\EditionRepository;
use App\Repository\PartenaireRepository;
use App\Repository\VoteRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
/**
 * @Route("/service")
 */
class ServiceController extends AbstractController
{
    private $candidatRepository;
    private $editionrepository;
    private $logger;
    private $params;
    private $voteRepository;
    private $configRepository;
    private $partenaireRepository;

    /**
     * @param $candidatRepository
     * @param $editionrepository
     */
    public function __construct(PartenaireRepository $partenaireRepository, ConfigurationRepository $configRepository, VoteRepository $voteRepository, ParameterBagInterface $paramConverter, LoggerInterface $logger, CandidatRepository $candidatRepository, EditionRepository $editionrepository)
    {
        $this->candidatRepository = $candidatRepository;
        $this->editionrepository = $editionrepository;
        $this->logger = $logger;
        $this->params = $paramConverter;
        $this->voteRepository = $voteRepository;
        $this->configRepository = $configRepository;
        $this->partenaireRepository = $partenaireRepository;
    }
    /**
     * @Route("/", name="service")
     */
    public function index(): Response
    {
        return $this->render('service/index.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }
    /**
     * @Route("/formation", name="service_formation")
     */
    public function formation(): Response
    {
        return $this->render('service/formation.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }
    /**
     * @Route("/developpement", name="service_developpement")
     */
    public function developpement(): Response
    {
        return $this->render('service/developpement.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }
    /**
     * @Route("/entreprenariat", name="service_entreprenariat")
     */
    public function entreprenariat(): Response
    {
        return $this->render('service/entreprenariat.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }
}
