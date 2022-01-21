<?php

namespace App\Controller;

use App\Repository\CandidatRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\EditionRepository;
use App\Repository\PartenaireRepository;
use App\Repository\ProductRepository;
use App\Repository\VoteRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
/**
 * @Route("/boutique")
 */
class BoutiqueController extends AbstractController
{
    private $candidatRepository;
    private $editionrepository;
    private $logger;
    private $params;
    private $voteRepository;
    private $configRepository;
    private $partenaireRepository;
    private $productRepository;

    /**
     * @param $candidatRepository
     * @param $editionrepository
     */
    public function __construct(ProductRepository $productRepository,PartenaireRepository $partenaireRepository, ConfigurationRepository $configRepository, VoteRepository $voteRepository, ParameterBagInterface $paramConverter, LoggerInterface $logger, CandidatRepository $candidatRepository, EditionRepository $editionrepository)
    {
        $this->candidatRepository = $candidatRepository;
        $this->editionrepository = $editionrepository;
        $this->logger = $logger;
        $this->params = $paramConverter;
        $this->voteRepository = $voteRepository;
        $this->configRepository = $configRepository;
        $this->partenaireRepository = $partenaireRepository;
        $this->productRepository=$productRepository;
    }

    /**
     * @Route("/", name="boutique")
     */
    public function index(): Response
    {
        return $this->render('boutique/index.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true]),
            'products' => $this->productRepository->findAll()
        ]);
    }
}
