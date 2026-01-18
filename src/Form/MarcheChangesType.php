<?php

namespace App\Form;

use App\Entity\ConjonctureJour;
use App\Entity\MarcheChanges;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarcheChangesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cours_indicatif')
            ->add('parallele_achat')
            ->add('parallele_vente')
            ->add('ecart_indic_parallele')
            ->add('conjoncture', EntityType::class, [
                'class' => ConjonctureJour::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarcheChanges::class,
        ]);
    }
}
