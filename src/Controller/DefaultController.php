<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Vote;
use App\Repository\CandidatRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\EditionRepository;
use App\Repository\PartenaireRepository;
use App\Repository\VoteRepository;
use App\Utils\ClientPaymoo;
use App\Utils\ClientServer;
use App\Utils\FlutterwaveService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private $candidatRepository;
    private $editionrepository;
    private $logger;
    private $params;
    private $voteRepository;
    private $configRepository;
    private $partenaireRepository;
    private $flutterwaveService;

    /**
     * @param $candidatRepository
     * @param $editionrepository
     */
    public function __construct(FlutterwaveService $flutterwaveService, PartenaireRepository $partenaireRepository, ConfigurationRepository $configRepository, VoteRepository $voteRepository, ParameterBagInterface $paramConverter, LoggerInterface $logger, CandidatRepository $candidatRepository, EditionRepository $editionrepository)
    {
        $this->candidatRepository = $candidatRepository;
        $this->editionrepository = $editionrepository;
        $this->logger = $logger;
        $this->params = $paramConverter;
        $this->voteRepository = $voteRepository;
        $this->configRepository = $configRepository;
        $this->partenaireRepository = $partenaireRepository;
        $this->flutterwaveService = $flutterwaveService;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        $configuration = $this->configRepository->findOneByLast();
        $edition = $this->editionrepository->findOneByStatuspulie();
        if ($configuration->getMaintenance()) {
            return $this->render('default/maintenance.html.twig', [
                'candidats' => $this->candidatRepository->findByEdition($edition),

            ]);
        }
        return $this->render('default/index.html.twig', [
            'candidats' => $this->candidatRepository->findByEdition($edition),
            'partenaires' => $this->partenaireRepository->findBy(['active' => true]),
            'edition'=>$edition
        ]);
    }

    /**
     * @Route("/inscription", name="inscription", methods={"GET","POST"})
     */
    public function inscription(Request $request): Response
    {
        $edition = $this->editionrepository->findOneByStatuspulie();
        if ($request->getMethod() == "POST") {

        }
        return $this->render('default/inscription.html.twig', [
            'candidats' => $this->candidatRepository->findAll(),
            'edition'=>$edition,
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }

    /**
     * @Route("/candidats", name="candidats")
     */
    public function candidats(): Response
    {
        $edition = $this->editionrepository->findOneByStatuspulie();
        return $this->render('default/candidats.html.twig', [
            'candidats' => $this->candidatRepository->findByEdition($edition),
            'edition'=>$edition,
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }

    /**
     * @Route("/criteres", name="criteres")
     */
    public function criteres(): Response
    {
        $edition = $this->editionrepository->findOneByStatuspulie();
        return $this->render('default/criteres.html.twig', [
            'candidats' => $this->candidatRepository->findAll(),
            'edition'=>$edition,
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }

    /**
     * @Route("/show/{url}", name="showcandidat")
     */
    public function show(Request $request): Response
    {
        $candidat = $this->candidatRepository->findOneBy(["genericurl" => $request->get("url")]);
        if ($candidat == null) {
            throw  new BadRequestException('This user does not have access to this section.');
        }
        // $this->logger->info("-------ic" . $request->get("url"));
        // $this->logger->error("-------ic" . $request->get("url"));
        return $this->render('default/detail-candidat.html.twig', [
            'candidat' => $candidat,
            'partenaires' => $this->partenaireRepository->findBy(['active' => true])
        ]);
    }

    /**
     * @Route("/sendpaiementcinetpay/ajax", name="sendpaiementcinetpay", methods={"POST"})
     */
    public function sendpaiementcinetpay(Request $request): Response
    {
        $candidat_id = $request->get("candidatid");
        $candidat = $this->candidatRepository->find($candidat_id);
        $client_phone = $request->get("clientphone");
        $client_currency = $request->get("clientcurrency");
        $client_votes = $request->get("clientvote");
        $endpoints = "payment";
        $notify_url = $this->generateUrl('notifyurlajax', ['vote' => $this->getLast(), 'candidat' => $candidat_id]);

        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = $candidat->getFirstname() . " " . $candidat->getLastname();
        $amount = $this->getAmount($client_votes);
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
        );
        $this->logger->error("LOGGER-----" . json_encode($data));
        $client = new ClientServer();
        $response = $client->postfinal($endpoints, $data);
        if ($response['code'] == "201") {
            $this->createVote($candidat, $client_votes,$reference);
            $url = $response["data"]["payment_url"];
            $link_array = explode('/', $url);
            return $this->redirect($url);
        }

        // return $this->redirectToRoute("home");
        return $this->render('default/detail-candidat.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    private function getLast()
    {
        $last = null;
        if (null == $this->voteRepository->findOneByLast()) {
            $last = 0;
        } else {
            $last = $this->voteRepository->findOneByLast()->getId();
        }
        return $last + 1;
    }

    /**
     * @Route("/sendpaiementcinetpayinternational/ajax", name="sendpaiementcinetpayinternational", methods={"POST"})
     */
    public function sendpaiementcinetpayinternational(Request $request): Response
    {
        $candidat_id = $request->get("candidatid");
        $candidat = $this->candidatRepository->find($candidat_id);
        $client_phone = $request->get("clientphone");
        $client_email = $request->get("clientemail");
        $client_currency = $request->get("clientcurrency");
        $client_votes = $request->get("clientintvote");
        $endpoints = "payment";
        $current_url = "";
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        $notify_url = $this->generateUrl('notifyurlajax', ['vote' => $this->getLast(), 'candidat' => $candidat_id]);
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = "vote miss-shinne " . $candidat->getFirstname() . " " . $candidat->getLastname();
        $amount = $this->convertXAF("USD", $this->getAmountInternational($client_votes));
        $redirect_url = "";
        $notify_url = $this->params->get('domain') . $notify_url;
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
            "customer_surname" => $candidat->getLastname(),
            "customer_phone_number" => $client_phone,
            "customer_email" => $client_email,
            "customer_address" => "AKWA ",
            "customer_city" => "DOUALA",
            "customer_country" => "CM",
            "alternative_currency" => "USD",
            "customer_zip_code" => "77777"
        );
        $this->logger->error("LOGGER-----" . json_encode($data));
        $client = new ClientServer();
        $response = $client->postfinal($endpoints, $data);
        if ($response['code'] == "201") {
            $this->createVote($candidat, $client_votes,$reference);
            $url = $response["data"]["payment_url"];
            $this->logger->info($url);
            $link_array = explode('/', $url);
            return $this->redirect($url);
        }

        return $this->render('default/detail-candidat.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    protected function convertXAF($currency, $amount)
    {
        $val = $amount;
        if ($currency == "USD") {
            $val = $amount * 579.75;
        }
        if ($currency == "EUR") {
            $val = $amount * 655.78;
        }
        return $val;
    }

    protected function getAmountInternational($votes)
    {
        $val = 0;
        if ($votes == 50) {
            $val = 10;
        } elseif ($votes == 100) {
            $val = 20;
        } elseif ($votes == 160) {
            $val = 30;
        } elseif ($votes == 270) {
            $val = 50;
        } elseif ($votes == 530) {
            $val = 100;
        }
        return $val;
    }

    protected function getAmount($votes)
    {
        $val = 0;
        if ($votes == 1) {
            $val = 100;
        } elseif ($votes == 2) {
            $val = 200;
        } elseif ($votes == 3) {
            $val = 300;
        } elseif ($votes == 5) {
            $val = 500;
        } elseif ($votes == 12) {
            $val = 1000;
        } elseif ($votes == 65) {
            $val = 5000;
        } elseif ($votes == 140) {
            $val = 10000;
        }
        return $val;
    }

    protected function createVote(Candidat $candidat, $qtevote,$reference)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $vote = new Vote();
        $vote->setReference($reference);
        $vote->setCandidat($candidat);
        $vote->setNombreVote($qtevote);
        $vote->setMonaie("XAF");
        $vote->setStatus("PENDING");
        $vote->setCreatedAt(new \DateTime('now', new \DateTimeZone('Africa/Douala')));
        $vote->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Africa/Douala')));
        $entityManager->persist($vote);
        $entityManager->flush();
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
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
    }

    /**
     * @Route("/returnurl/ajax/", name="returnurlajax", methods={"POST","GET"})
     */
    public function returnurl(Request $request): Response
    {

    }

    /**
     * @Route("/notifyurlcinetpay/ajax", name="notifyurlajax", methods={"POST","GET"})
     */
    public function notifyurl(Request $request): Response
    {
        $this->logger->error("notify call");
        $site_id = $_POST['cpm_site_id'];
        $transaction = $_POST['cpm_trans_id'];
        $vote_ = $this->voteRepository->find($_GET['vote']);
        $base_url = "https://api-checkout.cinetpay.com/v2/payment/check";
        if ($vote_->getStatus() == "PENDING") {
            $data = array(
                'apikey' => $this->params->get('api_key'),
                'site_id' => $site_id,
                'transaction_id' => $transaction
            );
            $client = new ClientServer();
            $response = $client->postfinal($base_url, $data);
            if ($response['data']['status'] == "ACCEPTED") {
                $this->updateVote($vote_, 'ACCEPTED');
            } elseif ($response['data']['status'] == "REFUSED") {
                $this->updateVote($vote_, 'REFUSED');
            }
        }
        return new JsonResponse($response, 200);
    }

    /**
     * @Route("/notifyurlproduct/ajax", name="notifyurlproduct", methods={"POST","GET"})
     */
    public function notifyurlproduct(Request $request): Response
    {
        $this->logger->error("notify call");
        $site_id = $_POST['cpm_site_id'];
        $transaction = $_POST['cpm_trans_id'];
        $base_url = "https://api-checkout.cinetpay.com/v2/payment/check";

        $data = array(
            'apikey' => $this->params->get('api_key'),
            'site_id' => $site_id,
            'transaction_id' => $transaction
        );
        $client = new ClientServer();
        $response = $client->postfinal($base_url, $data);
        if ($response['data']['status'] == "ACCEPTED") {
            $array = [
                "status" => "ACCEPTED"
            ];
            return new JsonResponse($array, 200);
        } elseif ($response['data']['status'] == "REFUSED") {
            $array = [
                "status" => "REFUSED"
            ];
            return new JsonResponse($array, 200);
        } else {
            $array = [
                "status" => "REFUSED"
            ];
            return new JsonResponse($array, 200);
        }

    }

    /**
     * @Route("/notifyurl/ajax", name="notifyurlpaymooajax", methods={"POST","GET"})
     */
    public function notifyurlpaymoo(Request $request): Response
    {
        $res = json_decode($request->getContent(), true);
        $orderId = $_GET['orderId'];
        $reference = $res['item_ref'];
        $this->logger->error($reference);
        $status = $res['status'];
        $this->logger->error("----------------------- notify call" . $request->get('vote'));
        $this->logger->error("----------------------- notify call" . $status);
        // $vote_ = $this->voteRepository->find($request->get('vote'));
        $vote_ = $this->voteRepository->findOneBy(['reference' => $reference]);
        if ($status == "Success") {
            if ($vote_->getStatus() === "PENDING") {
                if (strtolower($status) === "success") {
                    $this->updateVote($vote_, 'ACCEPTED');
                } elseif (strtolower($status) === "failed") {
                    $this->updateVote($vote_, 'REFUSED');
                }
            }
        }

        return new JsonResponse($status, 200);
    }

    /**
     * @Route("/notifyurlflutter/ajax", name="notifyurlflutterajax", methods={"POST","GET"})
     */
    public function notifyurlflutter(Request $request)
    {
        $this->logger->error("----------------------- notify call");
        $data = json_decode($request->getContent(), true);
        $this->logger->error("----------------------- notify call" . $request->get('vote'));
        if (!empty($request->get('status'))) {
            $status = $request->get('status');
            $vote_ = $this->voteRepository->find($request->get('vote'));
            if ($vote_->getStatus() == "PENDING") {
                if ($status == "successful") {
                    $this->updateVote($vote_, 'ACCEPTED');
                } elseif ($status == "cancelled") {
                    $this->updateVote($vote_, 'REFUSED');
                }
            }
        } else {
            $status = $request->get('status');
        }
        $this->generateRang();
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/notifyurlproductpaymoo/ajax", name="notifyurlproductpaymoo", methods={"POST","GET"})
     */
    public function notifyurlproductpaymoo(Request $request): Response
    {
        $this->logger->error("notify call");
        $status = $request->get('status');

        if ($status == "successful") {
            return $this->redirectToRoute('home');
        } else {
            return $this->redirectToRoute('boutique');
        }
    }

    protected function generateRang()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $edition = $this->editionrepository->findOneBy(['status' => 'Publie']);
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
        $entityManager->flush();
    }
    /**
     * @Route("/redirectpaiement", name="redirectpaiement", methods={"POST","GET"})
     */
    public function redirectpaiement(Request $request): Response
    {
        $reference=$_GET['orderId'];
        $this->logger->error("----------------------- notify call" . $reference);
        // $vote_ = $this->voteRepository->find($request->get('vote'));
        $vote_ = $this->voteRepository->findOneBy(['reference' => $reference]);

            if ($vote_->getStatus() === "PENDING") {
                    $this->updateVote($vote_, 'ACCEPTED');
            }

        return $this->render('default/success.html.twig', [
            'title'=>"Success page"
        ]);
    }
    /**
     * @Route("/sendpaiement/ajax", name="sendpaiementajax", methods={"POST"})
     */
    public function sendpaiement(Request $request): Response
    {
        $candidat_id = $request->get("candidatid");
        $candidat = $this->candidatRepository->find($candidat_id);
        $client_votes = $request->get("clientvote");
        $client_phone = $request->get("clientphone");
        $client_email = $request->get("clientemail");
        $this->logger->log(200, $request->get("clientphone"));
        $this->logger->info($request->get("clientvote"));
        $current_url = "";
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = "vote miss-shinne " . $candidat->getFirstname() . " " . $candidat->getLastname();
        $amount = $this->getAmount($client_votes);
        $notify_url = $this->generateUrl('notifyurlpaymooajax', ['vote' => $this->getLast(), 'candidat' => $candidat_id]);
        $key = $this->params->get('paymookey');
        $notify_url = $this->params->get('domain') . $notify_url;
        $data = [
            'amount' => $amount,
            'currency_code' => 'XAF',
            'ccode' => 'CM',
            'lang' => 'en',
            'item_ref' => $reference,
            'item_name' => $product,
            'description' => 'Voting session',
            'email' => $client_email,
            'phone' => $client_phone,
            'first_name' => 'Dossard' . $candidat->getDossard(),
            'last_name' => $candidat->getLastname(),
            'public_key' => $key,
            'logo' => 'https://paymooney.com/images/logo_paymooney2.png',
            'redirectUrl' => $this->generateUrl('redirectpaiement', ['item' => $reference]), //$this->siteUrl . $this->SUCCESS_REDIRECT_URL . $orderIdString,
            "redirectOnFailureUrl" => $this->generateUrl('redirectpaiement', ['item' => $reference]),
            'callbackUrl' => $notify_url,
            'callbackOnFailureUrl' => $notify_url,
            'ref_payment' => $reference,
            'redirectTarget' => 'TOP',
            'transaction_number' => $reference,
            'merchantCustomerId' => $reference,
            'environement' => 'live',
        ];
        $client = new ClientPaymoo();
        $response = $client->postfinal("payment_url", $data);
        $this->logger->info('------------------------' . $notify_url);
        if ($response['response'] == "success") {
            $this->createVote($candidat, $client_votes,$reference);
            $url = $response["payment_url"];
            $this->logger->info($url);
            $link_array = explode('/', $url);
            return $this->redirect($url);
        }

        return $this->redirectToRoute("home");

        //return new JsonResponse($data, 200);
    }

    /**
     * @Route("/sendpaiementinternationalpaymoo/ajax", name="sendpaiementinternationalpaymooajax", methods={"POST"})
     */
    public function sendpaiementinternational(Request $request): Response
    {
        $candidat_id = $request->get("candidatid");
        $candidat = $this->candidatRepository->find($candidat_id);
        $client_votes = $request->get("clientintvote");
        $client_phone = $request->get("clientphone");
        $client_email = $request->get("clientemail");
        $this->logger->log(200, $request->get("clientphone"));
        $this->logger->info($request->get("clientvote"));
        $current_url = $this->params->get('domain');
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = "vote miss-shinne " . $candidat->getFirstname() . " " . $candidat->getLastname();
        $amount = $this->getAmountInternational($client_votes);
        $notify_url = $this->generateUrl('notifyurlpaymooajax', ['vote' => $this->getLast(), 'candidat' => $candidat_id]);
        $key = $this->params->get('paymookey');
        $notify_url = $this->params->get('domain') . $notify_url;
        $data = [
            'amount' => $amount,
            'currency_code' => 'USD',
            'ccode' => 'CM',
            'lang' => 'en',
            'item_ref' => $reference,
            'item_name' => $product,
            'description' => 'Voting session',
            'email' => $client_email,
            'phone' => $client_phone,
            'first_name' => 'Dossard' . $candidat->getDossard(),
            'last_name' => $candidat->getLastname(),
            'public_key' => $key,
            'logo' => 'https://paymooney.com/images/logo_paymooney2.png',
            'redirectUrl' => $current_url . '?orderpay=' . $reference, //$this->siteUrl . $this->SUCCESS_REDIRECT_URL . $orderIdString,
            //"redirectOnFailureUrl" => $order->get_cancel_order_url(),//$this->siteUrl . $this->FAILURE_REDIRECT_URL . $orderIdString,
            'callbackUrl' => $notify_url,
            'callbackOnFailureUrl' => $notify_url,
            'redirectTarget' => 'TOP',
            'merchantCustomerId' => $reference,
            'environement' => 'test',
        ];
        $client = new ClientPaymoo();
        $response = $client->postfinal("payment_url", $data);
        $this->logger->info($notify_url);
        if ($response['response'] == "success") {
            $this->createVote($candidat, $client_votes,$reference);
            $url = $response["payment_url"];
            $this->logger->info($url);
            $link_array = explode('/', $url);
            return $this->redirect($url);
        }

        return $this->redirectToRoute("home");

        //return new JsonResponse($data, 200);
    }

    /**
     * @Route("/sendpaiementinternational/ajax", name="sendpaiementinternationalajax", methods={"POST"})
     */
    public function sendpaiementinternationalflutter(Request $request): Response
    {
        $candidat_id = $request->get("candidatid");
        $candidat = $this->candidatRepository->find($candidat_id);
        $client_votes = $request->get("clientintvote");
        $client_phone = $request->get("clientphone");
        $client_email = $request->get("clientemail");
        $this->logger->log(200, $request->get("clientphone"));
        $this->logger->info($request->get("clientvote"));
        $current_url = $this->params->get('domain');
        $transaction_id = "";
        $allowed_characters = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        for ($i = 1; $i <= 12; ++$i) {
            $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
        }
        $reference = $transaction_id;
        $product = "vote miss-shinne " . $candidat->getFirstname() . " " . $candidat->getLastname();
        $amount = $this->getAmountInternational($client_votes);
        $notify_url = $this->generateUrl('notifyurlflutterajax', ['vote' => $this->getLast(), 'candidat' => $candidat_id]);
        $key = $this->params->get('paymookey');
        $notify_url = $this->params->get('domain') . $notify_url;
        $data = [
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => 'card',
            'country' => 'CMR',
            'ref' => $reference,
            'title' => $product,
            'description' => 'Voting session',
            'email' => $client_email,
            'phonenumber' => $client_phone,
            'name' => 'Dossard:' . $candidat->getDossard() . '-' . $candidat->getLastname(),
            'last_name' => $candidat->getLastname(),
            'logo' => 'http://www.piedpiper.com/app/themes/joystick-v27/images/logo.png',
            'pay_button_text' => "Valider le vote",
            'successurl' => $notify_url,
            'redirect_url' => $notify_url,
        ];
        $response = $this->flutterwaveService->postPayement($data);
        $this->logger->info($notify_url);
        if ($response['status'] == "success") {
            $this->createVote($candidat, $client_votes,$reference);
            $url = $response["data"]['link'];
            $this->logger->info($url);
            return $this->redirect($url);
        }

        return $this->redirectToRoute("home");

        //return new JsonResponse($data, 200);
    }

    protected function getRangVoting(Candidat $candidat)
    {
        $edition = $this->editionrepository->findOneBy(['status' => 'Publie']);
        $candidats = $this->candidatRepository->findBy(['edition' => $edition], []);
        $j = 0;
        for ($i = 0; $i < sizeof($candidats); $i++) {
            if ($candidat->getVote() === $candidats[$i]->getVote()) {
                $j = sizeof($candidats) - $i;
            }
        }
        return $j;
    }

}
