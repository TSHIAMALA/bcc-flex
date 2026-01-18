<?php

namespace App\Form;

use App\Entity\ConjonctureJour;
use App\Entity\FinancesPubliques;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FinancesPubliquesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recettes_totales')
            ->add('recettes_fiscales')
            ->add('autres_recettes')
            ->add('depenses_totales')
            ->add('solde')
            ->add('conjoncture', EntityType::class, [
                'class' => ConjonctureJour::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FinancesPubliques::class,
        ]);
    }
}
