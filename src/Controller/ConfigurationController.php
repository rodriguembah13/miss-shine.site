<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Configuration;
use App\Form\ConfigurationType;
use App\Repository\ConfigurationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends AbstractController
{
    private $configRepository;

    /**
     * @param $configRepository
     */
    public function __construct(ConfigurationRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/configuration", name="configuration", methods={"GET","POST"})
     */
    public function index(Request $request): Response
    {
        $configuration=$this->configRepository->findOneByLast();
        if ($configuration==null){
            $configuration=new Configuration();
            $entityManager = $this->getDoctrine()->getManager();
            $configuration->setMaintenance(false);
            $entityManager->persist($configuration);
            $entityManager->flush();
        }
        $form = $this->createForm(ConfigurationType::class, $configuration);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('configuration');
        }
        return $this->render('configuration/index.html.twig', [
            'title'=>"Configuration",
            'form' => $form->createView(),
        ]);
    }
}
