<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('Titre', null, [
        'attr' => [
            'class' => 'form-control', // Bootstrap class for form controls
            'placeholder' => 'Enter the title', // Optional placeholder text
        ],
    ])
    ->add('submit', SubmitType::class, [
        'label' => 'Ajouter',
        'attr' => [
            'class' => 'btn btn-primary', // Bootstrap class for buttons
        ],
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
        ]);
    }
}
