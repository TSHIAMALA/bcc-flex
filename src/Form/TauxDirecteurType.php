<?php

namespace App\Form;

use App\Entity\TauxDirecteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TauxDirecteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateApplication', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'Application',
                'attr' => ['class' => 'form-control']
            ])
            ->add('valeur', NumberType::class, [
                'label' => 'Taux (%)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01'
                ]
            ])
            ->add('commentaire', TextType::class, [
                'label' => 'Commentaire / Motif',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Compte rendu CPM n°...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TauxDirecteur::class,
        ]);
    }
}
