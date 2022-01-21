<?php

namespace App\Controller;

use App\Entity\Partenaire;
use App\Form\PartenaireType;
use App\Repository\PartenaireRepository;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/partenaire")
 */
class PartenaireController extends AbstractController
{
    private $dataTableFactory;

    /**
     * @param $dataTableFactory
     */
    public function __construct(DataTableFactory $dataTableFactory)
    {
        $this->dataTableFactory = $dataTableFactory;
    }

    /**
     * @Route("/", name="partenaire_index", methods={"GET","POST"})
     */
    public function index(Request $request): Response
    {
        $table = $this->dataTableFactory->create()
            ->add('idx', TextColumn::class,[
                'field' => 'e.id',
                'className'=>"text-center"
            ])
            ->add('logo', TwigColumn::class, [
                'className' => 'bg-blue',
                'orderable'=>false,
                'template' => 'partenaire/photo.html.twig',
                'render' => function ($value, $context) {
                    return $value;
                }
            ])
            ->add('name', TextColumn::class, [
                'field' => 'e.name',
                'className'=>"text-center"
            ])
            ->add('active', TextColumn::class, [
                'field' => 'e.active',
                'className'=>"text-center"
            ])

            ->add('id', TwigColumn::class, [
                'className' => 'buttons text-center',
                'label' => 'action',
                'template' => 'partenaire/buttonbar.html.twig',
                'render' => function ($value, $context) {
                    return $value;
                }])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Partenaire::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->from(Partenaire::class, 'e')
                        ->orderBy("e.name","ASC")
                    ;
                },
            ])->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('partenaire/index.html.twig', [
            'datatable' => $table,
            'title'=>"Partenaires"
        ]);
    }

    /**
     * @Route("/new", name="partenaire_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $partenaire = new Partenaire();
        $form = $this->createForm(PartenaireType::class, $partenaire)
            ->add('imageFilename',FileType::class,[
                'mapped'=>false,
                'required'=>true,
                'label'=>'Logo'
            ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFilename = $form['imageFilename']->getData();
            if ($imageFilename) {
                $destination = $this->getParameter('kernel.project_dir').'/public/uploads/logo';
                $originalFilename = pathinfo($imageFilename->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFilename->guessExtension();

                try {
                    $imageFilename->move(
                        $destination,
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $partenaire->setLogo($newFilename);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($partenaire);
            $entityManager->flush();

            return $this->redirectToRoute('partenaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('partenaire/new.html.twig', [
            'partenaire' => $partenaire,
            'form' => $form->createView(),
            'title'=>"Partenaires"
        ]);
    }

    /**
     * @Route("/{id}", name="partenaire_show", methods={"GET"})
     */
    public function show(Partenaire $partenaire): Response
    {
        return $this->render('partenaire/show.html.twig', [
            'partenaire' => $partenaire,
            'title'=>"Partenaires"
        ]);
    }

    /**
     * @Route("/{id}/edit", name="partenaire_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Partenaire $partenaire): Response
    {
        $form = $this->createForm(PartenaireType::class, $partenaire)
        ->add('imageFilename',FileType::class,[
        'mapped'=>false,
        'required'=>true,
        'label'=>'Logo'
    ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFilename = $form['imageFilename']->getData();
            if ($imageFilename) {
                $destination = $this->getParameter('kernel.project_dir').'/public/uploads/logo';
                $originalFilename = pathinfo($imageFilename->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFilename->guessExtension();

                try {
                    $imageFilename->move(
                        $destination,
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $partenaire->setLogo($newFilename);
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('partenaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('partenaire/edit.html.twig', [
            'partenaire' => $partenaire,
            'form' => $form->createView(),
            'title'=>"Partenaires"
        ]);
    }

    /**
     * @Route("/{id}", name="partenaire_delete", methods={"POST"})
     */
    public function delete(Request $request, Partenaire $partenaire): Response
    {
        if ($this->isCsrfTokenValid('delete'.$partenaire->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($partenaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('partenaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
