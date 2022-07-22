<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\ModelSms;
use App\Entity\Sms;
use App\Repository\CandidatRepository;
use App\Repository\EditionRepository;
use App\Repository\ModelSmsRepository;
use App\Repository\SmsRepository;
use App\Repository\UserRepository;
use App\Utils\ClientSms;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

/**
 * @Route("/admin8796patr214vgfd/sms")
 */
class SmsController extends AbstractController
{
    private $modelsmsRepository;
    private $smsRepository;
    private $candidatRepository;
    private $clientsmsService;
    private $editionRepository;
    private $dataTableFactory;

    /**
     * SmsController constructor.
     * @param CandidatRepository $candidatRepository
     * @param ClientSms $clientsmsService
     * @param UserRepository $userRepository
     * @param ModelSmsRepository $modelsmsRepository
     * @param SmsRepository $smsRepository
     */
    public function __construct(DataTableFactory $dataTableFactory, EditionRepository $editionRepository, CandidatRepository $candidatRepository, ClientSms $clientsmsService, UserRepository $userRepository, ModelSmsRepository $modelsmsRepository, SmsRepository $smsRepository)
    {
        $this->modelsmsRepository = $modelsmsRepository;
        $this->smsRepository = $smsRepository;
        $this->clientsmsService = $clientsmsService;
        $this->candidatRepository = $candidatRepository;
        $this->editionRepository = $editionRepository;
        $this->dataTableFactory = $dataTableFactory;
    }

    /**
     * @Route("/historique", name="smshistorique")
     */
    public function index(Request $request): Response
    {
        // dump($this->candidatRepository->findOneByLast());
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        $table = $this->dataTableFactory->create()
            ->add('idx', TextColumn::class, [
                'field' => 'e.id',
                'className' => "text-center"
            ])
            ->add('recepteur', TextColumn::class, [
                'field' => 'e.recepteur',
                'className' => "text-center"
            ])
            ->add('message', TextColumn::class, [
                'field' => 'e.message',
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
            ->addOrderBy('idx', DataTable::SORT_DESCENDING)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Sms::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->from(Sms::class, 'e');
                },
            ])->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('sms/index.html.twig', [
            'title' => "Sms",
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/smssendone", name="smssendone")
     */
    public function smsSms(): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        return $this->render('sms/sendOne.html.twig', [
            'title' => "Sms",
        ]);
    }
    /**
     * @Route("/updatecontact", name="updatecontact")
     */
    public function updateContact(): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        return $this->render('sms/updatecontact.html.twig', [
            'title' => "Sms",
            'groupes' => [
                ['name' => 'Choisir type',
                    'id' => 0],
                ['name' => 'Depuis la base de donnée',
                    'id' => 1],
                ['name' => 'Apartir du fichier excel',
                    'id' => 2],
            ]
        ]);
    }
    /**
     * @Route("/smssendgroup", name="smssendgroup")
     */
    public function smsGroupSms(): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        // $customer = $this->customerRepository->findOneBy(['user' => $user]);
        return $this->render('sms/smssendgroup.html.twig', [
            'title' => "Sms Group",
            'customer' => "",
            'groupes' => [
                ['name' => 'Choisir type',
                    'id' => 0],
                ['name' => 'Depuis la base de donnée',
                    'id' => 1],
                ['name' => 'Apartir du fichier excel',
                    'id' => 2],
            ]
        ]);
    }

    /**
     * @Route("/get/getsmssolde", name="getsmssolde", methods={"GET"})
     */
    public function getSmsSolde(Request $request): JsonResponse
    {
        $user = $this->getUser();
        try {
            return new JsonResponse("", Response::HTTP_OK);
        } catch (\Exception $ex) {
            //Log exception
            return new JsonResponse($ex->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/uploadfileajax", name="uploadfileajax", methods={"POST","GET"})
     */
    public function uploadfileajax(Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $body = json_decode($request->getContent(), true);
        $uploadFilename = $request->files->get('file');
        //   $spreadsheet = $reader->load($uploadFilename);
        return new JsonResponse($body, 200);
    }

    /**
     * @Route("/getcontactajax", name="getcontactajax", methods={"POST","GET"})
     */
    public function getcontactajax(Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $contacts = [];
        foreach ($this->candidatRepository->findAll() as $candidat) {
            $contacts[] = [
                'id' => $candidat->getId(),
                'name' => $candidat->getFirstname(),
                'phone' => $candidat->getPhone(),
                'dossard' => $candidat->getDossard(),
            ];
        }
        return new JsonResponse($contacts, 200);
    }

    /**
     * @Route("/sendsmsajax", name="sendsmsajax", methods={"GET"})
     */
    public function sendSmsAjax(Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $sms = new Sms();
        $sms->setTelephone($request->get('phone'));
        $sms->setMessage($request->get('message'));
        $sms->setCreatedAt(new \DateTime('now', new \DateTimeZone('Africa/Brazzaville')));
        $datasms = [
            'phone' => $request->get('phone'),
            'message' => $request->get('message'),
            'clientkey' => $this->getParameter('clientkey'),
            'clientsecret' => $this->getParameter('clientsecret')
        ];

        $res = $this->clientsmsService->sendOne($datasms);
        if ($res['status'] === "SUCCESSFUL") {
            $sms->setStatus("SUCCESSFUL");
            $code = Response::HTTP_ACCEPTED;
        } else {
            $sms->setStatus("ECHEC");
            $code = Response::HTTP_BAD_REQUEST;
        }
        $em->persist($sms);
        $em->flush();
        return new JsonResponse($res, $code);
    }

    /**
     * @Route("/sendsmsmanyajax", name="sendsmsmanyajax", methods={"POST"})
     */
    public function sendSmsManyAjax(Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $body = json_decode($request->getContent(), true);
        $ob = $body['ob'];
        $votes = $this->candidatRepository->findOneByLast()->getVote();
        for ($i = 0; $i < sizeof($ob); ++$i) {
            $candidat = $this->candidatRepository->findOneBy(['dossard' => $ob[$i]['dossard']]);
            if (!is_null($candidat)) {
                if (!is_null($ob[$i]['phone'])) {
                    $phone = $ob[$i]['phone'];
                    $sms = new Sms();
                    $sms->setTelephone($phone);
                    $sms->setRecepteur($candidat->getFirstname());
                    $message = $candidat->getFirstname() . " Dossard" . $candidat->getDossard() . $candidat->getDescription() . "Vous occupez la" . $candidat->getPosition() . " e position avec" . $candidat->getVote() . " votes.
            La premiere totalise " . $votes . " votes.vous pouvez encore atteindre cette place.Merci";
                    $sms->setMessage($message);
                    $sms->setCreatedAt(new \DateTime('now', new \DateTimeZone('Africa/Brazzaville')));
                    $datasms = [
                        'phone' => $phone,
                        'message' => $message,
                        'clientkey' => $this->getParameter('clientkey'),
                        'clientsecret' => $this->getParameter('clientsecret')
                    ];
                    if ($body['updatecontact']) {
                        $candidat->setPhone($phone);
                    }
                    $res = $this->clientsmsService->sendOne($datasms);
                    if ($res['status'] === "SUCCESSFUL") {
                        $sms->setStatus("SUCCESSFUL");
                        $code = Response::HTTP_ACCEPTED;
                    } else {
                        $sms->setStatus("ECHEC");
                        $code = Response::HTTP_BAD_REQUEST;
                    }
                    $em->persist($sms);
                    $em->flush();
                }

            }

        }

        return new JsonResponse($ob, 200);
    }

    /**
     * @Route("/updatecontactsmsajax", name="updatecontactsmsajax", methods={"POST"})
     */
    public function updatecontactsmsAjax(Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $body = json_decode($request->getContent(), true);
        $ob = $body['ob'];
        for ($i = 0; $i < sizeof($ob); ++$i) {
            $candidat = $this->candidatRepository->findOneBy(['dossard' => $ob[$i]['dossard']]);
            if (!is_null($candidat)) {
                if (!is_null($ob[$i]['phone'])) {
                    $phone = $ob[$i]['phone'];
                        $candidat->setPhone($phone);
                   // $em->persist($sms);
                    $em->flush();
                }

            }

        }

        return new JsonResponse($ob, 200);
    }

    function updatePhone(Candidat $candidat, $phone)
    {
        $candidat->setPhone($phone);
    }
}
