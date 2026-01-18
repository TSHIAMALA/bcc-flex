<?php

namespace App\Form;

use App\Entity\Indicateur;
use App\Entity\RegleIntervention;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegleInterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('seuilAlerte')
            ->add('seuilIntervention')
            ->add('sens')
            ->add('poids')
            ->add('actif')
            ->add('indicateur', EntityType::class, [
                'class' => Indicateur::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegleIntervention::class,
        ]);
    }
}
