<?php

namespace App\Controller;

use App\Repository\CandidatRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\EditionRepository;
use App\Repository\PartenaireRepository;
use App\Repository\ProductRepository;
use App\Repository\VoteRepository;
use App\Utils\ClientPaymoo;
use App\Utils\ClientServer;
use App\Utils\FlutterwaveService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
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
    private $flutterwaveService;
    /**
     * @param $candidatRepository
     * @param $editionrepository
     */
    public function __construct(FlutterwaveService $flutterwaveService,ProductRepository $productRepository,PartenaireRepository $partenaireRepository, ConfigurationRepository $configRepository, VoteRepository $voteRepository, ParameterBagInterface $paramConverter, LoggerInterface $logger, CandidatRepository $candidatRepository, EditionRepository $editionrepository)
    {
        $this->candidatRepository = $candidatRepository;
        $this->editionrepository = $editionrepository;
        $this->logger = $logger;
        $this->params = $paramConverter;
        $this->voteRepository = $voteRepository;
        $this->configRepository = $configRepository;
        $this->partenaireRepository = $partenaireRepository;
        $this->productRepository=$productRepository;
        $this->flutterwaveService=$flutterwaveService;
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
    /**
     * @Route("/detail/{slug}", name="boutiqueshow")
     */
    public function show(Request $request): Response
    {
        $produit = $this->productRepository->findOneBy(["slug" => $request->get("slug")]);
        if ($produit == null) {
            throw  new BadRequestException('This user does not have access to this section.');
        }
        return $this->render('boutique/detail.html.twig', [
            'partenaires' => $this->partenaireRepository->findBy(['active' => true]),
            'product' => $produit
        ]);
    }
    public function addItemForm(): Response
    {

        return $this->render('boutique/donation.html.twig', [
        ]);
    }
    /**
     * @Route("/sendpaiementcinetpayproduct/ajax", name="sendpaiementcinetpayproduct", methods={"POST"})
     */
    public function sendpaiementcinetpay(Request $request): Response
    {
        $initprice = $request->get("initprice");
        $slug = $request->get("slug");
        $firstname = $request->get("firstname");
        $lastname = $request->get("lastname");
        $email = $request->get("email");
        $phone = $request->get("phone");
        $quantity = $request->get("shopquantity");
        $name = $request->get("shopname");
        $endpoints = "payment";
        $notify_url = $this->generateUrl('notifyurlproduct', []);
        //$notify_url ="";
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = $name;
        $amount = $initprice*$quantity;
        $redirect_url = $this->generateUrl('home');
        $notify_url = $this->params->get('domain') . $notify_url;
        // $this->logger->info(strip_tags($notify_url,'/'));
        $data = array(
            'site_id' => $this->params->get('site_id'),
            'currency' => 'XAF',
            'lang' => 'fr',
            'description' => $product,
            'amount' => $amount,
            'returnUrl' => $redirect_url,
            'transaction_id' => $reference,
            'apikey' => $this->params->get('api_key'),
            'notify_url' => $notify_url,
            'channels' => 'ALL',
            'customer_id' => "",
            "customer_name" => $firstname,
            "customer_surname" => $lastname,
            "customer_phone_number" => $phone,
            "customer_email" => $email,
            "customer_address" => "AKWA ",
            "customer_city" => "DOUALA",
            "customer_country" => "CM",
            "alternative_currency" => "USD",
            "customer_zip_code" => "77777"
        );
        $this->logger->error("LOGGER-----" . json_encode($data));
        // $this->createVote($candidat,$client_votes);
        $client = new ClientServer();
        $response = $client->postfinal($endpoints, $data);
        if ($response['code'] == "201") {
            $url = $response["data"]["payment_url"];
            $link_array = explode('/', $url);
            return $this->redirect($url);
        }
        $url=$this->generateUrl('boutiqueshow',["slug"=>$slug]);
        return $this->redirect($url);
       /* return $this->render('boutique/detail.html.twig', [
            //'candidat' => $candidat,
        ]);*/
    }
    /**
     * @Route("/sendpaiementcinetpaydonation/ajax", name="sendpaiementcinetpaydonation", methods={"POST"})
     */
    public function sendpaiementcinetpaydon(Request $request): Response
    {
        $initprice = $request->get("amount");
        $firstname = $request->get("firstname");
        $lastname = $request->get("lastname");
        $email = $request->get("email");
        $phone = $request->get("phone");
        $endpoints = "payment";
        $notify_url = $this->generateUrl('notifyurlproduct', []);
        //$notify_url ="";
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = "Donnation:".$firstname." ".$lastname;
        $amount = $initprice;
        $redirect_url = $this->generateUrl('home');
        $notify_url = $this->params->get('domain') . $notify_url;
        // $this->logger->info(strip_tags($notify_url,'/'));
        $data = array(
            'site_id' => $this->params->get('site_id'),
            'currency' => 'XAF',
            'lang' => 'fr',
            'description' => $product,
            'amount' => $amount,
            'returnUrl' => $redirect_url,
            'transaction_id' => $reference,
            'apikey' => $this->params->get('api_key'),
            'notify_url' => $notify_url,
            'channels' => 'ALL',
            'customer_id' => "",
            "customer_name" => "client1",
            "customer_surname" => $lastname,
            "customer_phone_number" => $phone,
            "customer_email" => $email,
            "customer_address" => "AKWA ",
            "customer_city" => "DOUALA",
            "customer_country" => "CM",
            "alternative_currency" => "USD",
            "customer_zip_code" => "77777"
        );
        $this->logger->error("LOGGER-----" . json_encode($data));
        // $this->createVote($candidat,$client_votes);
        $client = new ClientServer();
        $response = $client->postfinal($endpoints, $data);
        if ($response['code'] == "201") {
            $url = $response["data"]["payment_url"];
            $link_array = explode('/', $url);
            return $this->redirect($url);
        }
        $url=$this->generateUrl('home');
        return $this->redirect($url);
        /* return $this->render('boutique/detail.html.twig', [
             //'candidat' => $candidat,
         ]);*/
    }
    /**
     * @Route("/sendpaiementpaydonation/ajax", name="sendpaiementpaydonationajax", methods={"POST"})
     */
    public function sendpaiementpaydonation(Request $request): Response
    {
        $initprice = $request->get("amount");
        $firstname = $request->get("firstname");
        $lastname = $request->get("lastname");
        $email = $request->get("email");
        $phone = $request->get("phone");
        $currency = $request->get("currency");
        $this->logger->log(200, $request->get("clientphone"));
        $this->logger->info($request->get("clientvote"));
        $current_url = $this->params->get('domain');
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = "vote miss-shinne : donnation";


        $notify_url = $this->generateUrl('notifyurlproductpaymoo', []);
        $key=$this->params->get('paymookey');
        $notify_url = $this->params->get('domain') . $notify_url;
        $data = [
            'amount' => $initprice,
            'currency' => 'USD',
            'payment_method' => 'card',
            'country' => 'CMR',
            'ref' => $reference,
            'title' => $product,
            'description' => 'Voting session',
            'email' => $email,
            'phonenumber' => $phone,
            'name' => $firstname.' '.$lastname,
            'last_name' =>"",
            'logo' => 'http://www.piedpiper.com/app/themes/joystick-v27/images/logo.png',
            'pay_button_text' => "Valider le don",
            'successurl' => $notify_url,
            'redirect_url' => $notify_url,
        ];
        $response= $this->flutterwaveService->postPayement($data);
        $this->logger->info($notify_url);
        if ($response['status'] == "success") {
            $url = $response["data"]['link'];
            $this->logger->info($url);
            return $this->redirect($url);
        }

        return $this->redirectToRoute("home");
    }

    /**
     * @Route("/sendpaiementpayproduct/ajax", name="sendpaiementpayproductjax", methods={"POST"})
     */
    public function sendpaiementpayproduct(Request $request): Response
    {
        $initprice = $request->get("initprice");
        $slug = $request->get("slug");
        $firstname = $request->get("firstname");
        $lastname = $request->get("lastname");
        $email = $request->get("email");
        $phone = $request->get("phone");
        $quantity = $request->get("shopquantity");
        $name = $request->get("shopname");
        $currency = $request->get("currency");
        $this->logger->log(200, $request->get("clientphone"));
        $this->logger->info($request->get("clientvote"));
        $current_url = $this->params->get('domain');
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = $name;

        $notify_url = $this->generateUrl('notifyurlproductpaymoo', []);
        $key=$this->params->get('paymookey');
        $notify_url = $this->params->get('domain') . $notify_url;
        $data = [
            'amount' => $initprice,
            'currency_code' => 'XAF',
            'code' => 'CM',
            'lang' => 'en',
            'item_ref' => $reference,
            'item_name' => $product,
            'description' => 'Boutique:',
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

        return $this->redirectToRoute("home");
    }
}
