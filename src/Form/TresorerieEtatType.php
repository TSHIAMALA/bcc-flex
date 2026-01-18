<?php

namespace App\Form;

use App\Entity\ConjonctureJour;
use App\Entity\TresorerieEtat;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TresorerieEtatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('solde_avant_fin')
            ->add('solde_apres_fin')
            ->add('solde_cumule_annee')
            ->add('conjoncture', EntityType::class, [
                'class' => ConjonctureJour::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TresorerieEtat::class,
        ]);
    }
}
