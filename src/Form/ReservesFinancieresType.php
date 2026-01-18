<?php

namespace App\Form;

use App\Entity\ConjonctureJour;
use App\Entity\ReservesFinancieres;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservesFinancieresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reserves_internationales_usd')
            ->add('avoirs_externes_usd')
            ->add('reserves_banques_cdf')
            ->add('avoirs_libres_cdf')
            ->add('conjoncture', EntityType::class, [
                'class' => ConjonctureJour::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservesFinancieres::class,
        ]);
    }
}
