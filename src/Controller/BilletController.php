<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Entity\Candidat;
use App\Repository\BilletRepository;
use App\Repository\CandidatRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\EditionRepository;
use App\Repository\PartenaireRepository;
use App\Repository\ProductRepository;
use App\Repository\VoteRepository;
use App\Utils\ClientPaymoo;
use App\Utils\FlutterwaveService;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BilletController extends AbstractController
{
    private $candidatRepository;
    private $editionrepository;
    private $logger;
    private $params;
    private $voteRepository;
    private $configRepository;
    private $partenaireRepository;
    private $productRepository;
    private $flutterwaveService;
    private $billetRepository;
    private $dataTableFactory;

    /**
     * @param $candidatRepository
     * @param $editionrepository
     */
    public function __construct(DataTableFactory $dataTableFactory, BilletRepository $billetRepository, FlutterwaveService $flutterwaveService, ProductRepository $productRepository, PartenaireRepository $partenaireRepository, ConfigurationRepository $configRepository, VoteRepository $voteRepository, ParameterBagInterface $paramConverter, LoggerInterface $logger, CandidatRepository $candidatRepository, EditionRepository $editionrepository)
    {
        $this->candidatRepository = $candidatRepository;
        $this->editionrepository = $editionrepository;
        $this->logger = $logger;
        $this->params = $paramConverter;
        $this->voteRepository = $voteRepository;
        $this->configRepository = $configRepository;
        $this->partenaireRepository = $partenaireRepository;
        $this->productRepository = $productRepository;
        $this->flutterwaveService = $flutterwaveService;
        $this->billetRepository = $billetRepository;
        $this->dataTableFactory = $dataTableFactory;
    }

    /**
     * @Route("/admin8796patr214vgfd/billet", name="billet")
     */
    public function index(Request $request): Response
    {
        $table = $this->dataTableFactory->create()
            ->add('idx', TextColumn::class, [
                'field' => 'e.id',
                'className' => "text-center"
            ])
            ->add('firstname', TextColumn::class, [
                'field' => 'e.firstname',
                'className' => "text-center"
            ])
            ->add('lastname', TextColumn::class, [
                'field' => 'e.lastname',
                'className' => "text-center"
            ])
            ->add('amount', TextColumn::class, [
                'field' => 'e.amount',
                'className' => "text-center"
            ])
            ->add('Candidat', TextColumn::class, [
                'label' => 'dt.columns.candidat',
                'field' => 'c.firstname',
                'className' => "text-center"
            ])
            ->add('phone', TextColumn::class, [
                'className' => "text-center",
                'field' => 'e.phone',
                'label' => 'dt.columns.phone',

            ])
            ->add('createdAt', DateTimeColumn::class, [
                'format' => 'd-m-Y h:m',
                'className' => "text-center",
                'field' => 'e.createdAt',
                //'orderable' => false,
                'searchable' => false,
                'label' => 'dt.columns.createdat'
            ])
            /* ->add('id', TwigColumn::class, [
                 'className' => 'buttons text-center',
                 'label' => 'action',
                 'field' => 'e.id',
                 'orderable' => false,
                 'template' => 'candidat/buttonbar.html.twig',
                 'render' => function ($value, $context) {
                     return $value;
                 }])*/
            ->createAdapter(ORMAdapter::class, [
                'entity' => Billet::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->addSelect('c')
                        ->from(Billet::class, 'e')
                        ->leftJoin('e.candidat', 'c')// ->orderBy("e.id", "ASC")
                    ;
                },
            ])->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('billet/index.html.twig', [
            'title' => "Billeterie",
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/billeteries", name="billeteries")
     */
    public function billeteries(): Response
    {
        return $this->render('billet/billeteries.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true]),
            'products' => $this->productRepository->findAll()
        ]);
    }

    /**
     * @Route("/billeteriespay/{indice}/{region}", name="billeteriespay")
     */
    public function billeteriespay(Request $request, $indice,$region): Response
    {
        $initprice = 0.0;
        $typebillet = "";
        if ($indice == 1) {
            $initprice = 2000;
            $typebillet = "Billet standard 2000 FCFA";

        } elseif ($indice == 2) {
            $initprice = 5000;
            $typebillet = "Billet Gold 5000 FCFA";
        } else {
            $initprice = 10000;
            $typebillet = "Billet Vip 10000 FCFA";
        }
        $billet = new Billet();
        $form = $this->createFormBuilder($billet)
            ->add('candidat', EntityType::class, [
                'class' => Candidat::class,
                'multiple' => false,
                'placeholder' => 'veuillez choisir une candidate',
                'required' => true,
                'label' => 'Candidat(e) a suivre ',
                'attr' => ['name' => "candidat", 'class' => 'selectpicker form-select form-control rounded-0', 'data-size' => 5, 'data-live-search' => true],
            ])->getForm();
        $form->handleRequest($request);
        return $this->render('billet/billeteriepay.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true]),
            'initprice' => $initprice,
            'typebillet' => $typebillet,
            'region'=>$region,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/paiementpaybillet/ajax", name="paiementpaybilletajax", methods={"POST"})
     */
    public function paiementpaybillet(Request $request): Response
    {
        // dump($request);
        //die("200");
        $initprice = $request->get("initprice");
        $quantite = $request->get("quantite");
        $region = $request->get("region");
        $firstname = $request->get("firstname");
        $lastname = $request->get("lastname");
        $email = $request->get("email");
        $phone = $request->get("phone");
        $candidat_id = $request->get("form")['candidat'];
        $this->logger->error("----------------------- notify Billeterie" . $candidat_id);
        $name = $request->get("shopname");
        $this->logger->log(200, $request->get("firstname"));
        $this->logger->info($request->get("initprice"));
        $current_url = $this->params->get('domain');
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = "Vente de billet";
        $notify_url = $this->generateUrl('notifyurlbilleterieajax', ['billet' => $this->getLast(), 'candidat' => $candidat_id]);

        $key = $this->params->get('paymookey');
        $notify_url = $this->params->get('domain') . $notify_url;
        $data = [
            'amount' => $initprice*$quantite,
            'currency_code' => 'XAF',
            'code' => 'CM',
            'lang' => 'en',
            'item_ref' => $reference,
            'item_name' => $product,
            'description' => $region,
            'email' => $email,
            'phone' => $phone,
            'first_name' => $firstname,
            'last_name' => $lastname,
            'public_key' => $key,
            'logo' => 'https://paymooney.com/images/logo_paymooney2.png',
            'redirectUrl' => $current_url . '?orderpay=' . $reference,
            'callbackUrl' => $notify_url,
            'callbackOnFailureUrl' => $notify_url,
            'redirectTarget' => 'TOP',
            'merchantCustomerId' => $reference,
            'environement' => 'test',
        ];
        $client = new ClientPaymoo();
        $response = $client->postfinal("payment_url", $data);
        $this->logger->info($response['response']);
        if ($response['response'] == "success") {
            $url = $response["payment_url"];
            $link_array = explode('/', $url);
            return $this->redirect($url);
        }

        return $this->redirectToRoute("billeteries");
    }

    private function getLast()
    {
        $last = null;
        if (null == $this->billetRepository->findOneByLast()) {
            $last = 0;
        } else {
            $last = $this->billetRepository->findOneByLast()->getId();
        }
        return $last + 1;
    }

    /**
     * @Route("/notifyurlbilleterie/ajax", name="notifyurlbilleterieajax", methods={"POST","GET"})
     */
    public function notifyurlbilleterie(Request $request): Response
    {
        $status = $_POST['status'];
        $this->logger->debug("----------------------- notify Billeterie" . $request->get('billet'));
        $this->logger->debug("----------------------- notify Billeterie" . $status);
        $candidat_ = $this->candidatRepository->find($request->get('candidat'));

        if (strtolower($status) === "success") {
            $billet = new Billet();
            $billet->setEmail($_POST['email']);
            $billet->setFirstname($_POST['name']);
            $billet->setLastname($_POST['name']);
            $billet->setAmount($_POST['amount']);
            $billet->setPhone($_POST['phone']);
            $billet->setVille($_POST['description']);
            $billet->setCandidat($candidat_);
            $billet->setEdition($this->editionrepository->findOneBy(['status' => 'Publie']));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($billet);
            $entityManager->flush();
        }
        return new JsonResponse($status, 200);
    }

    private function createBillet($somme, $candidat)
    {


    }
}
