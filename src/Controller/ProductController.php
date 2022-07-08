<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
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
 * @Route("/admin8796patr214vgfd/product")
 */
class ProductController extends AbstractController
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
     * @Route("/", name="product_index", methods={"GET","POST"})
     */
    public function index(Request $request): Response
    {
        $table = $this->dataTableFactory->create()
            ->add('idx', TextColumn::class,[
                'field' => 'e.id',
                'className'=>"text-center"
            ])
            ->add('photo', TwigColumn::class, [
                'className' => 'bg-blue',
                'field' => 'e.image',
                'orderable'=>false,
                'template' => 'product/photo.html.twig',
                'render' => function ($value, $context) {
                    return $value;
                }
            ])
            ->add('name', TextColumn::class, [
                'field' => 'e.name',
                'className'=>"text-center"
            ])
            ->add('amount', TextColumn::class, [
                'field' => 'e.price',
                'className'=>"text-center"
            ])
            ->add('quantity', TextColumn::class, [
                'field' => 'e.stockquantity',
                'className'=>"text-center"
            ])
            ->add('active', TextColumn::class, [
                'field' => 'e.active',
                'className'=>"text-center"
            ])

            ->add('id', TwigColumn::class, [
                'className' => 'buttons text-center',
                'label' => 'action',
                'template' => 'product/buttonbar.html.twig',
                'render' => function ($value, $context) {
                    return $value;
                }])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Product::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->from(Product::class, 'e')
                        ->orderBy("e.name","ASC")
                    ;
                },
            ])->handleRequest($request);
        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('product/index.html.twig', [
            'datatable' => $table,
            'title'=>"Produits"
        ]);
    }

    /**
     * @Route("/new", name="product_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product)
            ->add('imageFilename',FileType::class,[
            'mapped'=>false,
            'required'=>true,
            'label'=>'Image'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $imageFilename = $form['imageFilename']->getData();
            if ($imageFilename) {
                $destination = $this->getParameter('kernel.project_dir').'/public/uploads/products';
                $originalFilename = pathinfo($imageFilename->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFilename->guessExtension();

                try {
                    $imageFilename->move(
                        $destination,
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $product->setImage($newFilename);
            }
            if (null == $product->getSlug() || '' == $product->getSlug()) {
                $slug = str_replace(' ', '_', $product->getName());
                $product->setSlug($slug);
            } else {
                $slug = str_replace(' ', '_', $product->getSlug());
                $product->setSlug($slug);
            }
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'title'=>"Produits"
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'title'=>"Produits"
        ]);
    }

    /**
     * @Route("/{id}/edit", name="product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (null == $product->getSlug() || '' == $product->getSlug()) {
                $slug = str_replace(' ', '_', $product->getName());
                $product->setSlug($slug);
            } else {
                $slug = str_replace(' ', '_', $product->getSlug());
                $product->setSlug($slug);
            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'title'=>"Produits"
        ]);
    }

    /**
     * @Route("/{id}", name="product_delete", methods={"POST"})
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_index', [], Response::HTTP_SEE_OTHER);
    }
}
