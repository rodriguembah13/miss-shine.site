<?php

namespace App\Form;

use App\Entity\Candidat;
use Omines\DataTablesBundle\Column\TextColumn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname')
            ->add('lastname')
            ->add('dossard')
            ->add('description',TextareaType::class,[
                'label'=>"Decription de la candidate"
            ])
            ->add('projet')
            ->add('descriptionprojet',TextareaType::class,[
                'label'=>"Decrivez le projet de la candidate"
            ])
            ->add('edition')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Candidat::class,
        ]);
    }
}
